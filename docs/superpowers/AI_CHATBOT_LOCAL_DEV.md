# AI Chatbot CMS — Local Dev Guide

This covers running the full Week 1 stack locally: the FastAPI service (`livi-ai-api`) + the CMS (`cms-livi`) pointed at it.

---

## Architecture

```
Browser → cms-livi (Laravel :8000 or Valet)
               ↓ HTTP + X-API-Key
          livi-ai-api (FastAPI :8001)
               ↓
          PostgreSQL (local or n8n's DB)
```

---

## Step 1 — Start the FastAPI service

Follow the full setup in `/home/hasryl/livi/livi-ai-api/README.md`, then:

```bash
cd /home/hasryl/livi/livi-ai-api
source .venv/bin/activate
uvicorn main:app --reload --port 8001
```

Verify:

```bash
curl http://localhost:8001/health
# → {"status":"ok"}

curl http://localhost:8001/api/config -H "X-API-Key: local-dev-secret"
# → {"system_prompt": {...}, "welcome_message": {...}, ...}
```

---

## Step 2 — Configure the CMS

You're on branch `feature/week1-ai-chatbot-cms`. Make sure your `.env` has:

```env
AI_API_URL=http://localhost:8001
AI_API_KEY=local-dev-secret
```

> Use the **same** `CMS_API_KEY` value from the FastAPI `.env` as `AI_API_KEY` here.

---

## Step 3 — Start the CMS

```bash
cd /home/hasryl/livi/cms-livi/.worktrees/week1-ai-chatbot-cms

# Install deps if not already done
composer install
npm install && npm run build

# Start
php artisan serve --port=8000
```

Or if you use Laravel Valet / Herd, it picks up automatically.

---

## Step 4 — Open the Chatbot Config page

1. Go to `http://localhost:8000/admin`
2. Log in with your admin account
3. In the left sidebar, find **AI Chatbot → System Configuration**
4. Edit the 3 fields and click **Save Changes**

Changes take effect on the next n8n chatbot request (n8n reads `system_prompt` dynamically from the DB).

---

## Run Laravel tests

```bash
cd /home/hasryl/livi/cms-livi/.worktrees/week1-ai-chatbot-cms
php artisan test tests/Feature/Services/AiApiServiceTest.php
```

> Tests use `Http::fake()` — no running FastAPI needed.

---

## Troubleshooting

**"Could not load configuration" notification on the CMS page**
- FastAPI is not running, or `AI_API_URL` / `AI_API_KEY` are wrong in `.env`
- Check: `curl http://localhost:8001/health`

**401 Unauthorized from FastAPI**
- `X-API-Key` header missing — check `AI_API_KEY` in `.env`

**403 Forbidden from FastAPI**
- Key mismatch — `AI_API_KEY` in cms `.env` must match `CMS_API_KEY` in livi-ai-api `.env`

**TagsInput not rendering**
- Clear Filament caches: `php artisan filament:cache-components && php artisan view:clear`

**Page not showing in sidebar**
- Filament auto-discovers pages from `app/Filament/Admin/Pages/` — run `php artisan optimize:clear`

---

## What was built (Week 1)

| File | Purpose |
|------|---------|
| `config/ai-api.php` | Reads `AI_API_URL` and `AI_API_KEY` from `.env` |
| `app/Services/AiApiService.php` | HTTP client wrapper for FastAPI |
| `app/Filament/Admin/Pages/ChatbotConfig.php` | Filament v4 settings page |
| `resources/views/filament/admin/pages/chatbot-config.blade.php` | Blade view |
| `tests/Feature/Services/AiApiServiceTest.php` | 3 feature tests |
