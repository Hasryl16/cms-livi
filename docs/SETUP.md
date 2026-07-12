# LIVI AI API — Setup & Deployment Guide

## Local Development

### 1. Python environment

```bash
cd livi-ai-api
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

> If `asyncpg` fails to build on Fedora/RHEL:
> ```bash
> pip install asyncpg --no-build-isolation
> ```

### 2. Environment variables

```bash
cp .env.example .env
```

Edit `.env` — all 7 variables are required:

```env
# PostgreSQL (same DB used by n8n)
DATABASE_URL=postgresql://livi_aichatbot:PASSWORD@HOST:5432/livi_aichatbot

# Shared secret between this API and the CMS
CMS_API_KEY=your-secret-key-here

# Cohere — used for document embeddings and search queries
COHERE_API_KEY=your-cohere-key

# Qdrant — vector store for knowledge base
QDRANT_URL=http://dev-qdrant.wellmagicteam.com
QDRANT_API_KEY=your-qdrant-key
QDRANT_COLLECTION=livi_cohere

# Direct VPS IP — bypasses Cloudflare WAF for Qdrant connections
# Set this to the IP of the server running Qdrant (not the Cloudflare proxy IP)
QDRANT_DIRECT_IP=103.77.107.162
```

> **Why QDRANT_DIRECT_IP?**
> The Qdrant domain routes through Cloudflare, which blocks Python HTTP clients.
> Setting `QDRANT_DIRECT_IP` routes all Qdrant traffic directly to the VPS IP via HTTP on port 80,
> bypassing Cloudflare entirely. The socket patch (`utils/qdrant_socket_patch.py`) handles this.

### 3. Run database migrations

```bash
# Connect to your PostgreSQL instance
psql $DATABASE_URL -f migrations/001_chatbot_config.sql
psql $DATABASE_URL -f migrations/002_knowledge_base_documents.sql
psql $DATABASE_URL -f migrations/003_conversation_analytics.sql
```

Migration 001 also seeds `chatbot_config` with default values.

### 4. Start the server

```bash
uvicorn main:app --reload --port 8001
```

Verify:
```bash
curl http://localhost:8001/health
# → {"status":"ok"}

curl http://localhost:8001/api/config \
  -H "X-API-Key: your-secret-key-here"
```

### 5. Run tests

```bash
python3 -m pytest tests/ -v
```

---

## Production Deployment (VPS — 103.77.107.162)

### Option A — systemd service (recommended)

```bash
# 1. Copy project to VPS
scp -r . user@103.77.107.162:/opt/livi-ai-api

# 2. SSH in and set up
ssh user@103.77.107.162
cd /opt/livi-ai-api

python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt --no-build-isolation
```

Create `/etc/systemd/system/livi-ai-api.service`:

```ini
[Unit]
Description=LIVI AI API
After=network.target

[Service]
User=www-data
WorkingDirectory=/opt/livi-ai-api
EnvironmentFile=/opt/livi-ai-api/.env
ExecStart=/opt/livi-ai-api/.venv/bin/uvicorn main:app --host 127.0.0.1 --port 8001
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload
systemctl enable livi-ai-api
systemctl start livi-ai-api
systemctl status livi-ai-api
```

### Option B — run directly (quick start)

```bash
nohup /opt/livi-ai-api/.venv/bin/uvicorn main:app \
  --host 127.0.0.1 --port 8001 &
```

### Verify production

```bash
curl http://localhost:8001/health
ss -tlnp | grep 8001
```

---

## CMS Configuration

The Laravel CMS (Filament) connects to this API. In the CMS `.env`:

```env
AI_API_URL=http://localhost:8001
AI_API_KEY=<same value as CMS_API_KEY above>
```

> **Do not use port 8000.** livi-ai-api runs on **8001**.
> After editing `.env`, run `php artisan config:clear`.

---

## Qdrant Collection Setup

If the Qdrant collection does not exist yet, create it before ingesting documents:

```python
from qdrant_client import QdrantClient
from qdrant_client.models import VectorParams, Distance

qc = QdrantClient(url="http://dev-qdrant.wellmagicteam.com", api_key="...")
qc.create_collection(
    collection_name="livi_cohere",
    vectors_config=VectorParams(size=1024, distance=Distance.COSINE),
)
```

Cohere `embed-multilingual-v3.0` produces **1024-dimensional** float vectors.

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `403 Forbidden` from CMS | API key mismatch or trailing space in `.env` | Run `cat -A .env \| grep AI_API` and remove trailing spaces; `php artisan config:clear` |
| `Network is unreachable` (Qdrant) | IPv6 or Cloudflare blocking | Ensure `QDRANT_DIRECT_IP` is set and `QDRANT_URL` uses `http://` not `https://` |
| `Connection timed out` (CMS) | Wrong port in `AI_API_URL` | Change port from `8000` to `8001` in CMS `.env` |
| `AttributeError: qc.search` | qdrant-client ≥ 1.14 removed `.search()` | Use `qc.query_points()` — already done in this codebase |
| `UndefinedColumn: created_at` | `n8n_chat_histories` lacks the column | Run the `add_created_at.sql` migration (adds the column, API falls back gracefully) |
| Livewire Entangle errors | `mount()` threw (usually 403) — `$data` stays `{}` | Fix the underlying API error; Entangle errors resolve automatically |
