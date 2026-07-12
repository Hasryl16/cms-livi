# livi-ai-api

FastAPI service that acts as the API gateway between the LIVI CMS (Laravel/Filament) and the AI infrastructure (PostgreSQL, Qdrant, n8n).

The CMS never touches the AI database directly — all reads and writes go through this service, authenticated with a shared API key (`X-API-Key` header).

## Quick Start

```bash
python3 -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env   # fill in all 7 variables
uvicorn main:app --reload --port 8001
curl http://localhost:8001/health
```

See [`docs/SETUP.md`](docs/SETUP.md) for full setup and production deployment instructions.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check (no auth) |
| GET | `/api/config` | Get chatbot config (system prompt, keywords, welcome msg) |
| PUT | `/api/config` | Update a config value |
| GET | `/api/chat-logs` | List sessions (paginated, filterable by date/lead/search) |
| GET | `/api/chat-logs/{session_id}` | Full transcript for a session |
| GET | `/api/knowledge-base/documents` | List all documents + ingestion status |
| POST | `/api/knowledge-base/ingest` | Upload PDF/DOCX/XLSX for ingestion |
| POST | `/api/knowledge-base/{doc_id}/rollback` | Roll back to previous document version |
| POST | `/api/knowledge-base/{doc_id}/retry` | Retry a failed ingestion |
| POST | `/api/knowledge-base/search` | Semantic search in knowledge base |
| DELETE | `/api/knowledge-base/{doc_id}` | Delete document and its Qdrant vectors |
| GET | `/api/analytics` | 4 metric datasets for the analytics dashboard |

Full request/response schemas: [`docs/API.md`](docs/API.md)

## Environment Variables

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | PostgreSQL connection string |
| `CMS_API_KEY` | Shared secret — must match `AI_API_KEY` in cms-livi `.env` |
| `COHERE_API_KEY` | For document embeddings and search queries |
| `QDRANT_URL` | Qdrant base URL (use `http://`, not `https://`) |
| `QDRANT_API_KEY` | Qdrant authentication key |
| `QDRANT_COLLECTION` | Collection name (default: `livi_cohere`) |
| `QDRANT_DIRECT_IP` | VPS IP for Cloudflare bypass — routes Qdrant traffic directly |

## Documentation

- [`docs/API.md`](docs/API.md) — full endpoint reference with request/response examples
- [`docs/SETUP.md`](docs/SETUP.md) — local setup and production deployment
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — system architecture and design decisions

## Tests

```bash
python3 -m pytest tests/ -v
```
