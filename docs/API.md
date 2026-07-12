# LIVI AI API — Endpoint Reference

All endpoints except `/health` require the `X-API-Key` header.

```
X-API-Key: <CMS_API_KEY>
```

Base URL (local): `http://localhost:8001`
Base URL (production): `http://103.77.107.162:8001`

---

## Health

### `GET /health`

No authentication required.

**Response**
```json
{ "status": "ok" }
```

---

## Chatbot Config

### `GET /api/config`

Returns all three chatbot configuration values.

**Response**
```json
{
  "system_prompt": {
    "value": "Kamu adalah OLIV...",
    "updated_by": "admin",
    "updated_at": "2026-07-09 09:49:22.142324+00"
  },
  "welcome_message": {
    "value": "Halo! Ada yang bisa dibantu hari ini?",
    "updated_by": "admin",
    "updated_at": "2026-07-09 09:49:22.176311+00"
  },
  "lead_trigger_keywords": {
    "value": "beli,order,harga,quotation,distributor",
    "updated_by": "admin",
    "updated_at": "2026-07-09 09:49:22.187345+00"
  }
}
```

---

### `PUT /api/config`

Updates a single config value.

**Request body**
```json
{
  "key": "system_prompt",
  "value": "Kamu adalah OLIV, asisten virtual LIVI...",
  "updated_by": "admin"
}
```

Valid keys: `system_prompt`, `welcome_message`, `lead_trigger_keywords`

**Response** — the updated row
```json
{
  "key": "system_prompt",
  "value": "Kamu adalah OLIV...",
  "updated_by": "admin",
  "updated_at": "2026-07-10 12:00:00+00"
}
```

**Errors**
| Code | Meaning |
|------|---------|
| 422 | Unknown key (not in allowed list) |
| 404 | Key not in DB (run migrations) |

---

## Chat Logs

### `GET /api/chat-logs`

Paginated list of chat sessions. Each session is one row with metadata.

**Query params**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Rows per page (max 100) |
| `date_from` | string | — | Filter sessions starting from `YYYY-MM-DD` |
| `date_to` | string | — | Filter sessions up to `YYYY-MM-DD` |
| `lead_status` | string | — | `lead_captured`, `no_lead`, or `abandoned` |
| `search` | string | — | Full-text search in message content |

**Lead status values**
- `lead_captured` — session has a lead record
- `no_lead` — session has messages but no lead
- `abandoned` — session has only one message (user opened but barely chatted)

**Response**
```json
{
  "data": [
    {
      "session_id": "abc-123",
      "first_message": "Berapa harga tissue LIVI?",
      "msg_count": 8,
      "lead_id": 5,
      "lead_name": "Budi Santoso",
      "started_at": "2026-07-09 10:23:11+00",
      "min_id": 1042
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 142,
    "total_pages": 8
  }
}
```

---

### `GET /api/chat-logs/{session_id}`

Full message transcript for a single session.

**Path param**: `session_id` — the session UUID from the list endpoint

**Response**
```json
{
  "session_id": "abc-123",
  "messages": [
    {
      "id": 1042,
      "role": "user",
      "content": "Berapa harga tissue LIVI?",
      "created_at": "2026-07-09 10:23:11+00"
    },
    {
      "id": 1043,
      "role": "oliv",
      "content": "Halo! Untuk harga tissue LIVI...",
      "created_at": "2026-07-09 10:23:14+00"
    }
  ],
  "lead": {
    "id": 5,
    "name": "Budi Santoso",
    "company": "PT Sinar Jaya",
    "whatsapp": "08123456789",
    "notes": "Interested in bulk order",
    "lead_type": "distributor",
    "session_id": "abc-123",
    "created_at": "2026-07-09 10:25:00+00"
  },
  "message_count": 8,
  "started_at": "2026-07-09 10:23:11+00"
}
```

**Notes**
- `lead` is `null` if no lead was captured
- Tool messages (Qdrant results) and action tokens are stripped from the output
- `[ Formulir kontak ditampilkan ]` appears where the lead form was triggered

**Errors**
| Code | Meaning |
|------|---------|
| 404 | Session not found (no messages and no lead) |

---

## Knowledge Base

### `GET /api/knowledge-base/documents`

List all uploaded documents with ingestion status.

**Response**
```json
{
  "documents": [
    {
      "id": 3,
      "filename": "produk-livi.pdf",
      "file_type": "pdf",
      "status": "indexed",
      "version": 2,
      "supersedes_id": 1,
      "chunk_count": 47,
      "uploaded_by": "admin",
      "uploaded_at": "2026-07-10 08:00:00+00",
      "indexed_at": "2026-07-10 08:00:45+00",
      "error_message": null
    }
  ],
  "stats": {
    "total": 5,
    "processing": 0,
    "failed": 1,
    "indexed": 4
  }
}
```

**Status values**
| Status | Meaning |
|--------|---------|
| `processing` | Being chunked, embedded, and uploaded to Qdrant |
| `indexed` | Successfully ingested and searchable |
| `failed` | Ingestion failed — see `error_message` |
| `superseded` | Replaced by a newer version |

---

### `POST /api/knowledge-base/ingest`

Upload a new document for ingestion. Runs in the background after responding.

**Request**: `multipart/form-data`

| Field | Type | Description |
|-------|------|-------------|
| `file` | File | PDF, DOCX, or XLSX. Max 50 MB. |

**Versioning**: if a document with the same filename already exists and is `indexed`, it is marked `superseded` and the new upload becomes version N+1.

**Response**
```json
{
  "doc_id": 4,
  "status": "processing",
  "version": 2
}
```

**Errors**
| Code | Meaning |
|------|---------|
| 400 | Unsupported file type or file exceeds 50 MB |

---

### `POST /api/knowledge-base/{doc_id}/rollback`

Roll back to the previous version of a document. Re-triggers ingestion for the previous version.

**Path param**: `doc_id` — must be `indexed` and have a `supersedes_id`

**Response**
```json
{
  "message": "Rollback started",
  "restoring_version": 1
}
```

**Errors**
| Code | Meaning |
|------|---------|
| 404 | Document not found |
| 400 | Document is not `indexed`, or has no previous version |

---

### `POST /api/knowledge-base/{doc_id}/retry`

Retry ingestion for a `failed` document.

**Path param**: `doc_id` — must have status `failed`

**Response**
```json
{ "message": "Retry started" }
```

**Errors**
| Code | Meaning |
|------|---------|
| 404 | Document not found |
| 400 | Document is not in `failed` status |

---

### `POST /api/knowledge-base/search`

Embed a query with Cohere and return top-k matching chunks from Qdrant.

**Request body**
```json
{
  "query": "harga tissue LIVI premium",
  "top_k": 5
}
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `query` | string | required | The search query |
| `top_k` | int | 5 | Number of results (max 20) |

**Response**
```json
{
  "query": "harga tissue LIVI premium",
  "collection": "livi_cohere",
  "results": [
    {
      "score": 0.8923,
      "doc_id": 3,
      "filename": "produk-livi.pdf",
      "version": 2,
      "chunk_index": 12,
      "text": "Tissue LIVI Premium tersedia dalam kemasan..."
    }
  ]
}
```

**Errors**
| Code | Meaning |
|------|---------|
| 400 | `query` is empty |
| 500 | Cohere or Qdrant error |

---

### `DELETE /api/knowledge-base/{doc_id}`

Hard-delete a document record and remove its vectors from Qdrant.

**Path param**: `doc_id`

**Response**
```json
{ "message": "Document 3 deleted" }
```

**Errors**
| Code | Meaning |
|------|---------|
| 404 | Document not found |

---

## Analytics

### `GET /api/analytics`

Returns all 4 metric datasets for the CMS analytics dashboard.

**Response**
```json
{
  "volume": {
    "daily": [
      { "date": "2026-07-01", "sessions": 12, "messages": 48 }
    ],
    "hourly": [
      { "hour": 9, "sessions": 5 },
      { "hour": 10, "sessions": 8 }
    ],
    "dow": [
      { "dow": 1, "label": "Mon", "sessions": 22 },
      { "dow": 2, "label": "Tue", "sessions": 18 }
    ],
    "summary_30d": {
      "sessions": 142,
      "messages": 891
    }
  },
  "funnel": {
    "total_sessions": 198,
    "sessions_with_msgs": 165,
    "sessions_with_leads": 87,
    "total_leads": 91,
    "lead_types": [
      { "lead_type": "distributor", "cnt": 34 },
      { "lead_type": "retail", "cnt": 22 }
    ]
  },
  "topics": [
    { "topic": "Harga / Pricing", "session_count": 45 },
    { "topic": "Informasi Produk", "session_count": 38 },
    { "topic": "Kemitraan", "session_count": 21 },
    { "topic": "After Sales", "session_count": 12 },
    { "topic": "Pemesanan", "session_count": 9 },
    { "topic": "Lainnya", "session_count": 40 }
  ],
  "performance": {
    "avg_msgs_per_session": 6.2,
    "fallback_rate_pct": 5.1,
    "avg_qdrant_calls_per_session": 2.3,
    "total_fallbacks": 47,
    "total_ai_msgs": 921
  }
}
```

**Volume**
- `daily`: last 30 days, one row per day (WIB timezone)
- `hourly`: all-time, by hour of day (0–23)
- `dow`: all-time, by day of week (0 = Sunday)

**Topics** — keyword matching on human messages:
| Topic | Keywords |
|-------|---------|
| Harga / Pricing | harga, price, biaya, cost, tarif, quotation, rfq |
| Kemitraan | partner, mitra, distributor, reseller, agen, kerjasama |
| After Sales | after sales, servis, service, garansi, warranty, claim, klaim |
| Pemesanan | order, pesan, beli, purchase, indent |
| Informasi Produk | produk, product, barang, tissue, dispenser, item, katalog |
| Lainnya | everything else |

**Performance**
- `fallback_rate_pct`: % of AI messages containing apology/unable phrases
- `avg_qdrant_calls_per_session`: avg RAG lookups per session

---

## Summary Table

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check (no auth) |
| GET | `/api/config` | Get all chatbot config |
| PUT | `/api/config` | Update a config value |
| GET | `/api/chat-logs` | List sessions (paginated, filterable) |
| GET | `/api/chat-logs/{session_id}` | Full transcript for a session |
| GET | `/api/knowledge-base/documents` | List all documents |
| POST | `/api/knowledge-base/ingest` | Upload and ingest a document |
| POST | `/api/knowledge-base/{doc_id}/rollback` | Roll back to previous version |
| POST | `/api/knowledge-base/{doc_id}/retry` | Retry a failed document |
| POST | `/api/knowledge-base/search` | Semantic search in knowledge base |
| DELETE | `/api/knowledge-base/{doc_id}` | Delete document and its vectors |
| GET | `/api/analytics` | All 4 analytics metric datasets |
