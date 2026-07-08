# LIVI CMS — AI Chatbot Integration Design

**Date:** 2026-07-08  
**Project:** LIVI AI Chatbot CMS (Sinarmas Group)  
**Author:** Muhammad Hasryl Natawidjaya  
**Status:** Approved — ready for implementation planning

---

## Overview

Extend the existing `cms-livi` (Laravel/Filament) to give LIVI admins full control over the AI chatbot without touching n8n or any backend directly. All CMS actions communicate with the AI infra exclusively via a FastAPI service — no direct database or Qdrant access from the CMS.

**4 sections to build, in delivery order:**
1. AI System Message (Week 1) — highest immediate value
2. Chat Log History (Week 2) — reads existing data
3. Knowledge Base (Week 3–4) — requires Python ingestion worker
4. Analytics Dashboard (Week 5) — aggregates from existing tables

---

## Architecture

### Infrastructure Separation

The CMS and AI infra are on separate VPSes and must not touch each other's databases directly. All communication goes through HTTP API calls authenticated with a shared API key.

```
CMS VPS (Laravel/Filament)
        ↕ HTTP + X-API-Key header
AI VPS — 103.77.107.162 (FastAPI service)
        ↕ internal
n8n · PostgreSQL · Qdrant · Python worker
```

### Authentication

Every request from CMS to FastAPI includes an `X-API-Key: <secret>` header. The secret is stored in:
- `cms-livi/.env` → `AI_API_KEY=<secret>`
- FastAPI `.env` → `CMS_API_KEY=<secret>`

No OAuth for v1.

### New Database Tables (created on AI VPS PostgreSQL)

**chatbot_config**
```sql
id          SERIAL PRIMARY KEY
key         VARCHAR UNIQUE NOT NULL
value       TEXT NOT NULL
updated_by  VARCHAR NOT NULL
updated_at  TIMESTAMP DEFAULT NOW()
```
Seeded with 3 rows: `system_prompt`, `welcome_message`, `lead_trigger_keywords`.

**knowledge_base_documents**
```sql
id            SERIAL PRIMARY KEY
filename      VARCHAR NOT NULL
file_type     VARCHAR NOT NULL  -- pdf, docx, xlsx
status        VARCHAR NOT NULL  -- pending, processing, indexed, failed, superseded
version       INTEGER NOT NULL DEFAULT 1
supersedes_id INTEGER REFERENCES knowledge_base_documents(id)
chunk_count   INTEGER
uploaded_by   VARCHAR NOT NULL
uploaded_at   TIMESTAMP DEFAULT NOW()
indexed_at    TIMESTAMP
error_message TEXT
```

**conversation_analytics**
```sql
id                    SERIAL PRIMARY KEY
session_id            VARCHAR NOT NULL
token_usage           INTEGER
latency_ms            INTEGER
model                 VARCHAR
fallback_triggered    BOOLEAN DEFAULT FALSE
qdrant_relevance_score FLOAT
intent_tag            VARCHAR
csat_score            INTEGER  -- null until user rates
session_abandoned     BOOLEAN DEFAULT FALSE
conversation_length   INTEGER
created_at            TIMESTAMP DEFAULT NOW()
```

Also: add `status` and `assigned_to` columns to the existing `leads` table.

---

## FastAPI Service

Single Python FastAPI app deployed on the AI VPS. The Python ingestion worker runs as a background task within the same service.

### Endpoints

```
# Section 3 — AI System Message
GET  /api/config                         → returns all chatbot_config rows
PUT  /api/config                         → upserts { key, value }, records updated_by

# Section 2 — Chat Log History
GET  /api/chat-logs                      → paginated list, params: date_from, date_to, lead_status, search, page, per_page
GET  /api/chat-logs/{session_id}         → full message thread + lead info for one session

# Section 1 — Knowledge Base
POST /api/knowledge-base/ingest          → receives file, queues Python worker, returns document_id
GET  /api/knowledge-base/documents       → list of all documents with status, version, chunk_count
POST /api/knowledge-base/{id}/rollback   → marks current version superseded, restores previous version's vectors in Qdrant
POST /api/knowledge-base/{id}/retry      → re-queues a failed document for ingestion

# Section 4 — Analytics
GET  /api/analytics/summary              → all dashboard metrics in one response
```

---

## Section 3 — AI System Message (Week 1)

### CMS UI (Filament)
Single settings page under **AI Chatbot › System Configuration** with 3 fields:

| Field | Type | Description |
|---|---|---|
| System Prompt | Textarea (required) | Main persona & behavior rules |
| Welcome Message | Text input (required) | First message shown to user |
| Lead Trigger Keywords | Tag input | Comma-separated; n8n parses on trigger check |

Displays audit info: "Last updated by {user} · {time ago}"

### Data Flow
1. Admin saves → CMS calls `PUT /api/config` for each changed key
2. FastAPI upserts row in `chatbot_config`, records `updated_by` and `updated_at`
3. n8n workflow: add a Postgres query node at the top that fetches `system_prompt` dynamically before every AI Agent call — `SELECT value FROM chatbot_config WHERE key = 'system_prompt'`

Changes take effect on the next chatbot request. No n8n deployment needed.

---

## Section 2 — Chat Log History (Week 2)

### CMS UI (Filament)
Two views:

**Session List View**
- Filters: date range (default last 30 days), lead status (all / lead captured / no lead / abandoned), keyword search across message content
- Table columns: Session ID, First Message, Message Count, Lead Status badge, Date
- Paginated (20 per page)
- "View →" link opens Session Detail

**Session Detail View**
- Full message thread rendered as chat bubbles (user right, OLIV left)
- Lead trigger event highlighted inline ("🎯 Lead triggered — keyword 'order' detected")
- Metadata panel: Session ID, duration, message count, date, lead info (name, phone, trigger keyword) if captured

### Data Source
FastAPI queries `n8n_chat_histories` joined with `leads` on `session_id`. No new tables needed. 30-day retention window enforced at query level.

### FastAPI Queries
```sql
-- List view
SELECT h.session_id, MIN(h.content) as first_message,
       COUNT(*) as message_count, l.id as lead_id,
       MIN(h.created_at) as started_at
FROM n8n_chat_histories h
LEFT JOIN leads l ON l.session_id = h.session_id
WHERE h.created_at >= NOW() - INTERVAL '30 days'
  AND (search param applied to h.content if provided)
GROUP BY h.session_id, l.id
ORDER BY started_at DESC

-- Detail view
SELECT * FROM n8n_chat_histories
WHERE session_id = :session_id
ORDER BY created_at ASC
```

---

## Section 1 — Knowledge Base (Week 3–4)

### CMS UI (Filament)
Single page under **AI Chatbot › Knowledge Base**.

**Stats bar:** Total documents, total vectors in Qdrant, currently processing count, failed count.

**Document table columns:** Filename, File Type badge, Chunk Count, Version (v1 / v2 latest / superseded), Status badge, Uploaded by + date, Rollback / Retry action.

**Upload modal:** Drag-and-drop or click. Accepts PDF, DOCX, XLSX. Max 50MB. Optional document name field (auto-filled from filename).

**Status badges:** Pending → Processing → Indexed | Failed (with Retry) | Superseded (greyed out, with Restore)

### Versioning Behaviour
- Re-uploading a file with the same name creates a new version (v2, v3, …)
- Previous version is marked `superseded` in the DB and its vectors remain in Qdrant but are tagged as inactive
- Rollback deletes the current version's vectors from Qdrant and re-activates the previous version's vectors
- All versions remain visible in the document list (superseded rows greyed out)

### Python Ingestion Worker (runs inside FastAPI as background task)

**Libraries:**
- `pdfplumber` — PDF text extraction + embedded image extraction
- `python-docx` — DOCX text + embedded images
- `pandas` + `openpyxl` — Excel sheet parsing
- `pytesseract` — OCR on embedded images extracted from any format
- `cohere` SDK — `embed-multilingual-v3.0` embeddings
- `qdrant-client` — vector upsert

**Processing steps:**
1. Parse document → extract text chunks (500 tokens, 50 token overlap)
2. Extract embedded images → run pytesseract OCR → append OCR text to relevant chunks
3. Generate Cohere embeddings for all chunks (batch)
4. Upsert to Qdrant with payload: `{ doc_id, version, filename, chunk_index }`
5. Update `knowledge_base_documents`: set `status = indexed`, `chunk_count`, `indexed_at`
6. On failure: set `status = failed`, record `error_message`

---

## Section 4 — Analytics Dashboard (Week 5)

Single page under **AI Chatbot › Analytics** with 5 chart sections. All data from `GET /api/analytics/summary`.

### 1. Conversation Volume & Trends
Bar chart of daily/weekly/monthly sessions and messages. Date range toggle (Daily / Weekly / Monthly). Shows peak day-of-week and peak hour pulled from timestamp aggregation.

### 2. Lead Conversion Funnel
Horizontal funnel bars:
- Total sessions
- Sessions with ≥1 message (engagement rate)
- Sessions that captured a lead (conversion rate)
- Leads contacted/closed (requires `status` column on `leads` table)

### 3. Topic / Intent Breakdown
Horizontal bar chart of intent categories. Requires an extra LLM call in n8n at conversation end to tag each session with an intent (Product info / Pricing-RFQ / Order status / Complaint / Other) saved to `conversation_analytics.intent_tag`.

### 4. AI Performance & Failure Analysis
4 metric cards with sparklines:
- Average response latency (ms)
- Fallback rate (% of sessions where bot couldn't answer)
- Tokens per session + estimated cost
- RAG miss rate (% sessions with low Qdrant relevance score)

Sourced from `conversation_analytics`. Requires n8n to save `latency_ms`, `fallback_triggered`, `token_usage`, `qdrant_relevance_score` after each conversation.

### 5. Customer Satisfaction & Drop-off
- Abandonment rate (sessions with only 1 message) — donut chart
- Conversation length distribution — bar chart (1–2, 3–5, 6–10, 11+ messages)
- CSAT thumbs up/down — displayed if data exists; requires adding a rating prompt to `website-livi` chatbot UI (optional, can be added post-v1)

---

## Implementation Order & Dependencies

| Week | Work | Dependencies |
|---|---|---|
| 1 | Create 3 DB tables · FastAPI skeleton + auth · `GET/PUT /api/config` · n8n Postgres node · Section 3 Filament UI | None |
| 2 | `GET /api/chat-logs` + `/{session_id}` · Section 2 Filament UI | Week 1 FastAPI running |
| 3–4 | Python worker (pdfplumber, docx, pandas, pytesseract, Cohere, Qdrant) · `POST /api/knowledge-base/*` endpoints · Section 1 Filament UI | Week 1 DB tables |
| 5 | n8n updates (latency, fallback, token tracking, intent tagging) · `GET /api/analytics/summary` · Section 4 Filament UI | conversation_analytics table |

---

## Open Items (to confirm with client/PM)

| # | Question | Owner |
|---|---|---|
| 1 | File size limit for uploads — is 50MB sufficient? | PM + Client |
| 2 | CSAT thumbs up/down in chatbot UI — v1 or post-v1? | PM + Client |
| 3 | Intent tagging categories — confirm the 5 categories or adjust? | PM + Client |
| 4 | `leads` table — confirm OK to add `status` and `assigned_to` columns | Hasryl + PM |
| 5 | Qdrant collection name for versioned vectors — confirm naming convention | Hasryl |
