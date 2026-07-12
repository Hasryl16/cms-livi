# LIVI AI API — Architecture Overview

## System Map

```
┌─────────────────────────────────────────────────────────────────────┐
│  User's Browser                                                      │
│  (WhatsApp-style chat widget on website-livi)                        │
└──────────────────────────┬──────────────────────────────────────────┘
                           │ HTTPS
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│  n8n Automation (VPS 103.77.107.162)                                 │
│  - Receives chat messages                                            │
│  - Runs AI agent workflow                                            │
│  - Calls Qdrant for RAG retrieval                                    │
│  - Calls Groq (LLM inference)                                        │
│  - Stores history in n8n_chat_histories                              │
│  - Captures leads in leads table                                     │
└────────────────┬────────────────────────────────────────────────────┘
                 │ reads/writes
                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PostgreSQL (same VPS)                                               │
│  Tables:                                                             │
│  ├── n8n_chat_histories   (session chat history, LangChain format)   │
│  ├── leads                (captured lead records)                    │
│  ├── chatbot_config       (system prompt, welcome msg, keywords)     │
│  └── knowledge_base_documents (ingestion status + metadata)          │
└────────────────┬────────────────────────────────────────────────────┘
                 │ asyncpg
                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│  livi-ai-api  (FastAPI, port 8001)              ← THIS SERVICE       │
│  Routers:                                                            │
│  ├── /api/config          (chatbot system prompt management)         │
│  ├── /api/chat-logs       (paginated session list + transcript)      │
│  ├── /api/knowledge-base  (document ingestion + search)              │
│  └── /api/analytics       (4-metric dashboard data)                  │
│                                                                      │
│  Workers:                                                            │
│  └── ingestion.py         (background: extract → chunk → embed → store)│
└────────────────┬────────────────────────────────────────────────────┘
                 │ HTTP  X-API-Key
                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│  cms-livi  (Laravel 11 / Filament 4, port 8000)                      │
│  Admin CMS — internal use only                                       │
│  Pages:                                                              │
│  ├── System Configuration   (edit system prompt + keywords)          │
│  ├── Chat Logs              (browse + search conversations)          │
│  ├── Knowledge Base         (upload PDFs, track ingestion status)    │
│  └── Analytics              (Chart.js dashboard with 4 metric sets)  │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow: Document Ingestion

```
CMS uploads file
       │
       ▼
POST /api/knowledge-base/ingest
  1. Validate type (pdf/docx/xlsx) + size (≤50 MB)
  2. Insert DB row  status='processing'
  3. Save file to uploads/{doc_id}_{filename}
  4. Return {doc_id, status, version}  ← HTTP response ends here
       │
       ▼ (background task)
workers/ingestion.py
  1. Extract text   (pdfplumber / python-docx / pandas)
  2. Chunk text     (500 words, 50-word overlap)
  3. Embed chunks   (Cohere embed-multilingual-v3.0, 1024 dims)
  4. Delete old vectors for doc_id from Qdrant
  5. Upsert new vectors to Qdrant with payload {doc_id, filename, version, chunk_index, text}
  6. Update DB row  status='indexed', chunk_count=N, indexed_at=NOW()
```

## Data Flow: RAG Search (n8n)

```
User message
       │
       ▼
n8n AI Agent
  1. Embed query (Cohere)
  2. Search Qdrant → top-k chunks
  3. Inject chunks into LLM context
  4. LLM (Groq) generates response
  5. Store human + AI messages in n8n_chat_histories
```

## Authentication

Single shared secret between the CMS and this API:

```
CMS sends:  X-API-Key: <CMS_API_KEY>
API checks: os.getenv("CMS_API_KEY")
```

This is set in both `.env` files:
- `livi-ai-api/.env` → `CMS_API_KEY=...`
- `cms-livi/.env`    → `AI_API_KEY=...`

## Key Design Decisions

**Why a separate FastAPI service?**
The CMS (Laravel) lives outside the AI VPS. Rather than exposing PostgreSQL directly, livi-ai-api acts as a controlled gateway — the only way into the AI database from the CMS.

**Why asyncpg directly instead of SQLAlchemy/ORM?**
n8n writes `n8n_chat_histories` in a format owned by LangChain. The schema is irregular (JSONB messages, variable columns). Raw SQL with asyncpg gives full control without fighting an ORM.

**Why Cohere for embeddings?**
Cohere `embed-multilingual-v3.0` handles Indonesian + English in the same vector space, which matches LIVI's bilingual customer base.

**Why the socket patch for Qdrant?**
The Qdrant hostname resolves through Cloudflare, which blocks non-browser HTTP clients. The patch routes the hostname directly to the VPS IP, bypassing Cloudflare, using plain HTTP on port 80 (Cloudflare terminates TLS, so HTTP to the VPS IP is correct).

## Directory Structure

```
livi-ai-api/
├── main.py                        # FastAPI app + router registration
├── auth.py                        # X-API-Key verification dependency
├── db.py                          # asyncpg connection pool + lifespan
├── schemas.py                     # Pydantic models
│
├── routers/
│   ├── config.py                  # GET + PUT /api/config
│   ├── chat_logs.py               # GET /api/chat-logs + /{session_id}
│   ├── knowledge_base.py          # /api/knowledge-base/*
│   └── analytics.py               # GET /api/analytics
│
├── workers/
│   └── ingestion.py               # Background doc ingestion pipeline
│
├── utils/
│   └── qdrant_socket_patch.py     # Cloudflare bypass for Qdrant
│
├── migrations/
│   ├── 001_chatbot_config.sql     # chatbot_config table + seed data
│   ├── 002_knowledge_base_documents.sql
│   └── 003_conversation_analytics.sql
│
├── uploads/                       # Uploaded files stored here
├── tests/                         # pytest suite
├── docs/                          # This documentation
│   ├── API.md
│   ├── SETUP.md
│   └── ARCHITECTURE.md
│
├── requirements.txt
├── .env.example
└── README.md
```
