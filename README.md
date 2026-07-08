# livi-ai-api

FastAPI service that acts as the API gateway between the LIVI CMS (Laravel/Filament) and the AI infrastructure (PostgreSQL, Qdrant, n8n) on the AI VPS.

The CMS never touches the AI database directly — all reads and writes go through this service, authenticated with a shared API key.

---

## Requirements

- Python 3.11+
- PostgreSQL 14+ (the same instance used by n8n)

---

## Local Setup

### 1. Clone and create a virtual environment

```bash
cd /home/hasryl/livi/livi-ai-api
python3 -m venv .venv
source .venv/bin/activate
```

### 2. Install dependencies

```bash
pip install -r requirements.txt
```

> If `asyncpg` fails to build, install it without isolation:
> ```bash
> pip install asyncpg --no-build-isolation
> ```

### 3. Create your `.env`

```bash
cp .env.example .env
```

Edit `.env`:

```env
DATABASE_URL=postgresql://postgres:password@localhost:5432/livi_db
CMS_API_KEY=local-dev-secret
```

Use the same PostgreSQL database that n8n is connected to (or a local test DB — see step 4).

### 4. Run the database migrations

```bash
psql $DATABASE_URL -f migrations/001_chatbot_config.sql
psql $DATABASE_URL -f migrations/002_knowledge_base_documents.sql
psql $DATABASE_URL -f migrations/003_conversation_analytics.sql
```

This creates the 3 tables and seeds `chatbot_config` with default values.

### 5. Start the server

```bash
uvicorn main:app --reload --port 8000
```

The API is now running at `http://localhost:8000`.

---

## Verify it's working

```bash
# Health check (no auth needed)
curl http://localhost:8000/health

# Get all chatbot config (requires API key)
curl http://localhost:8000/api/config \
  -H "X-API-Key: local-dev-secret"

# Update a config value
curl -X PUT http://localhost:8000/api/config \
  -H "X-API-Key: local-dev-secret" \
  -H "Content-Type: application/json" \
  -d '{"key": "system_prompt", "value": "Kamu adalah OLIV...", "updated_by": "hasryl"}'
```

---

## Run tests

Tests use mocked DB — no real Postgres needed.

```bash
python3 -m pytest tests/ -v
```

Expected output: **7 passed**

---

## API Reference

All endpoints (except `/health`) require the `X-API-Key` header.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/health` | Health check |
| `GET` | `/api/config` | Get all chatbot config values |
| `PUT` | `/api/config` | Update a config value |

### PUT /api/config

Request body:

```json
{
  "key": "system_prompt",
  "value": "Kamu adalah OLIV...",
  "updated_by": "hasryl"
}
```

Valid keys: `system_prompt`, `welcome_message`, `lead_trigger_keywords`

---

## Project Structure

```
livi-ai-api/
├── main.py                  # FastAPI app entry point
├── auth.py                  # X-API-Key header verification
├── db.py                    # asyncpg connection pool
├── schemas.py               # Pydantic request/response models
├── routers/
│   └── config.py            # GET + PUT /api/config
├── migrations/
│   ├── 001_chatbot_config.sql
│   ├── 002_knowledge_base_documents.sql
│   └── 003_conversation_analytics.sql
├── tests/
│   ├── conftest.py          # Mock DB fixture
│   ├── test_auth.py         # Auth tests (401/403)
│   └── test_config.py       # Config endpoint tests
├── requirements.txt
└── .env.example
```

---

## Deployment (AI VPS — 103.77.107.162)

```bash
# Copy files to VPS
scp -r . user@103.77.107.162:/opt/livi-ai-api

# SSH in
ssh user@103.77.107.162
cd /opt/livi-ai-api

# Setup
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt --no-build-isolation

# Configure
cp .env.example .env
nano .env  # set real DATABASE_URL and CMS_API_KEY

# Run migrations
psql $DATABASE_URL -f migrations/001_chatbot_config.sql
psql $DATABASE_URL -f migrations/002_knowledge_base_documents.sql
psql $DATABASE_URL -f migrations/003_conversation_analytics.sql

# Start (production)
uvicorn main:app --host 0.0.0.0 --port 8000
```

For production, run behind a process manager (systemd or PM2) and optionally proxy through nginx.
