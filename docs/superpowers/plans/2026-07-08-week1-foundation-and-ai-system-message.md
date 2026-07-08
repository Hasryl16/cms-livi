# Week 1: Foundation + AI System Message Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the 3 Postgres tables on the AI VPS, stand up a FastAPI service with API key auth and config endpoints, then build the Filament CMS settings page so admins can edit the chatbot's system prompt, welcome message, and lead trigger keywords live — with n8n fetching the prompt dynamically on every request.

**Architecture:** A new FastAPI service (`livi-ai-api`) runs on the AI VPS (103.77.107.162) alongside n8n and Postgres. It owns all AI infra data access. The CMS talks to it exclusively over HTTP using a shared API key. The Filament settings page calls a `AiApiService` HTTP client class — it never touches the AI Postgres directly.

**Tech Stack:** FastAPI · asyncpg · psycopg2-binary · Python 3.11 · Laravel 13 · Filament v4 · PHPUnit/Pest · pytest · httpx

---

## File Map

**New — AI VPS (`/home/hasryl/livi/livi-ai-api/`)**
- `main.py` — FastAPI app, mounts all routers
- `auth.py` — `verify_api_key` dependency, reads `CMS_API_KEY` from env
- `db.py` — asyncpg connection pool, `get_db` dependency
- `schemas.py` — Pydantic models for all request/response bodies
- `routers/config.py` — `GET /api/config` and `PUT /api/config`
- `requirements.txt`
- `.env.example`
- `tests/conftest.py` — pytest fixtures: async test client, test DB
- `tests/test_auth.py` — verifies unauthenticated requests are rejected
- `tests/test_config.py` — verifies config CRUD round-trip

**New — migrations (run manually on AI VPS Postgres)**
- `livi-ai-api/migrations/001_chatbot_config.sql`
- `livi-ai-api/migrations/002_knowledge_base_documents.sql`
- `livi-ai-api/migrations/003_conversation_analytics.sql`

**New — CMS (`cms-livi/`)**
- `config/ai-api.php` — `url` and `key` pulled from `.env`
- `app/Services/AiApiService.php` — `getConfig()`, `setConfig()` methods
- `app/Filament/Admin/Pages/ChatbotConfig.php` — Filament settings page
- `tests/Feature/Services/AiApiServiceTest.php` — HTTP mock tests

**Modified — CMS**
- `cms-livi/.env` — add `AI_API_URL` and `AI_API_KEY`

---

## Task 1: Create Postgres Migrations

**Files:**
- Create: `livi-ai-api/migrations/001_chatbot_config.sql`
- Create: `livi-ai-api/migrations/002_knowledge_base_documents.sql`
- Create: `livi-ai-api/migrations/003_conversation_analytics.sql`

- [ ] **Step 1.1: Create the migrations directory and 001 file**

```bash
mkdir -p /home/hasryl/livi/livi-ai-api/migrations
```

Write `livi-ai-api/migrations/001_chatbot_config.sql`:

```sql
CREATE TABLE IF NOT EXISTS chatbot_config (
    id          SERIAL PRIMARY KEY,
    key         VARCHAR(100) UNIQUE NOT NULL,
    value       TEXT NOT NULL,
    updated_by  VARCHAR(100) NOT NULL,
    updated_at  TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

INSERT INTO chatbot_config (key, value, updated_by) VALUES
    ('system_prompt',           'Kamu adalah OLIV, asisten virtual LIVI dari Sinarmas Group.', 'system'),
    ('welcome_message',         'Halo! Ada yang bisa dibantu hari ini?', 'system'),
    ('lead_trigger_keywords',   'beli,order,harga,quotation', 'system')
ON CONFLICT (key) DO NOTHING;
```

- [ ] **Step 1.2: Create 002 file**

Write `livi-ai-api/migrations/002_knowledge_base_documents.sql`:

```sql
CREATE TABLE IF NOT EXISTS knowledge_base_documents (
    id              SERIAL PRIMARY KEY,
    filename        VARCHAR(500) NOT NULL,
    file_type       VARCHAR(20) NOT NULL CHECK (file_type IN ('pdf', 'docx', 'xlsx')),
    status          VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (status IN ('pending', 'processing', 'indexed', 'failed', 'superseded')),
    version         INTEGER NOT NULL DEFAULT 1,
    supersedes_id   INTEGER REFERENCES knowledge_base_documents(id),
    chunk_count     INTEGER,
    uploaded_by     VARCHAR(100) NOT NULL,
    uploaded_at     TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    indexed_at      TIMESTAMP WITH TIME ZONE,
    error_message   TEXT
);

CREATE INDEX idx_kbd_filename ON knowledge_base_documents(filename);
CREATE INDEX idx_kbd_status   ON knowledge_base_documents(status);
```

- [ ] **Step 1.3: Create 003 file**

Write `livi-ai-api/migrations/003_conversation_analytics.sql`:

```sql
CREATE TABLE IF NOT EXISTS conversation_analytics (
    id                      SERIAL PRIMARY KEY,
    session_id              VARCHAR(200) NOT NULL,
    token_usage             INTEGER,
    latency_ms              INTEGER,
    model                   VARCHAR(100),
    fallback_triggered      BOOLEAN DEFAULT FALSE,
    qdrant_relevance_score  FLOAT,
    intent_tag              VARCHAR(100),
    csat_score              INTEGER,
    session_abandoned       BOOLEAN DEFAULT FALSE,
    conversation_length     INTEGER,
    created_at              TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_ca_session_id  ON conversation_analytics(session_id);
CREATE INDEX idx_ca_created_at  ON conversation_analytics(created_at);
```

- [ ] **Step 1.4: Run migrations on AI VPS Postgres**

```bash
# SSH into AI VPS then run:
psql -U postgres -d <your_db_name> -f /path/to/livi-ai-api/migrations/001_chatbot_config.sql
psql -U postgres -d <your_db_name> -f /path/to/livi-ai-api/migrations/002_knowledge_base_documents.sql
psql -U postgres -d <your_db_name> -f /path/to/livi-ai-api/migrations/003_conversation_analytics.sql
```

Expected output for each: `CREATE TABLE` and `INSERT 0 3` (for 001).

Verify:
```bash
psql -U postgres -d <your_db_name> -c "\dt"
# Should show: chatbot_config, knowledge_base_documents, conversation_analytics
psql -U postgres -d <your_db_name> -c "SELECT key, value FROM chatbot_config;"
# Should show 3 rows
```

- [ ] **Step 1.5: Commit**

```bash
cd /home/hasryl/livi/livi-ai-api
git init
git add migrations/
git commit -m "feat: add postgres migrations for chatbot_config, knowledge_base_documents, conversation_analytics"
```

---

## Task 2: FastAPI Project Setup + Auth

**Files:**
- Create: `livi-ai-api/requirements.txt`
- Create: `livi-ai-api/.env.example`
- Create: `livi-ai-api/auth.py`
- Create: `livi-ai-api/db.py`
- Create: `livi-ai-api/schemas.py`
- Create: `livi-ai-api/main.py`
- Create: `livi-ai-api/tests/conftest.py`
- Create: `livi-ai-api/tests/test_auth.py`

- [ ] **Step 2.1: Write requirements.txt and .env.example**

`livi-ai-api/requirements.txt`:
```
fastapi==0.115.0
uvicorn[standard]==0.32.0
asyncpg==0.30.0
python-dotenv==1.0.1
pydantic==2.9.0
httpx==0.27.0
pytest==8.3.0
pytest-asyncio==0.24.0
```

`livi-ai-api/.env.example`:
```
DATABASE_URL=postgresql://user:password@localhost:5432/livi_db
CMS_API_KEY=change-me-to-a-long-random-secret
```

- [ ] **Step 2.2: Write the failing auth test**

`livi-ai-api/tests/test_auth.py`:
```python
import pytest
from httpx import AsyncClient, ASGITransport


@pytest.mark.asyncio
async def test_missing_api_key_returns_401(client: AsyncClient):
    response = await client.get("/api/config")
    assert response.status_code == 401


@pytest.mark.asyncio
async def test_wrong_api_key_returns_403(client: AsyncClient):
    response = await client.get("/api/config", headers={"X-API-Key": "wrong-key"})
    assert response.status_code == 403


@pytest.mark.asyncio
async def test_correct_api_key_passes(client: AsyncClient):
    response = await client.get("/api/config", headers={"X-API-Key": "test-secret"})
    assert response.status_code == 200
```

- [ ] **Step 2.3: Write conftest.py**

`livi-ai-api/tests/conftest.py`:
```python
import os
import pytest
import pytest_asyncio
from httpx import AsyncClient, ASGITransport

os.environ["CMS_API_KEY"] = "test-secret"
os.environ["DATABASE_URL"] = "postgresql://postgres:postgres@localhost:5432/livi_test"

from main import app


@pytest_asyncio.fixture
async def client():
    async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as ac:
        yield ac
```

- [ ] **Step 2.4: Run test to confirm it fails**

```bash
cd /home/hasryl/livi/livi-ai-api
python -m pytest tests/test_auth.py -v
```

Expected: `ImportError: No module named 'main'` — confirms tests are wired but app doesn't exist yet.

- [ ] **Step 2.5: Write auth.py, db.py, schemas.py, main.py**

`livi-ai-api/auth.py`:
```python
import os
from fastapi import Header, HTTPException


async def verify_api_key(x_api_key: str = Header(None)):
    if x_api_key is None:
        raise HTTPException(status_code=401, detail="X-API-Key header required")
    if x_api_key != os.getenv("CMS_API_KEY"):
        raise HTTPException(status_code=403, detail="Invalid API key")
```

`livi-ai-api/db.py`:
```python
import os
import asyncpg
from contextlib import asynccontextmanager
from fastapi import FastAPI

_pool = None


async def get_pool():
    global _pool
    if _pool is None:
        _pool = await asyncpg.create_pool(os.getenv("DATABASE_URL"))
    return _pool


async def get_db():
    pool = await get_pool()
    async with pool.acquire() as conn:
        yield conn


@asynccontextmanager
async def lifespan(app: FastAPI):
    await get_pool()
    yield
    if _pool:
        await _pool.close()
```

`livi-ai-api/schemas.py`:
```python
from pydantic import BaseModel


class ConfigValue(BaseModel):
    value: str
    updated_by: str
    updated_at: str


class ConfigResponse(BaseModel):
    system_prompt: ConfigValue | None = None
    welcome_message: ConfigValue | None = None
    lead_trigger_keywords: ConfigValue | None = None


class ConfigUpdateRequest(BaseModel):
    key: str
    value: str
    updated_by: str
```

`livi-ai-api/main.py`:
```python
from dotenv import load_dotenv
load_dotenv()

from fastapi import FastAPI, Depends
from db import lifespan
from auth import verify_api_key
from routers import config as config_router

app = FastAPI(title="LIVI AI API", lifespan=lifespan)

app.include_router(
    config_router.router,
    prefix="/api",
    dependencies=[Depends(verify_api_key)],
)


@app.get("/health")
async def health():
    return {"status": "ok"}
```

Create empty routers package so main.py imports work:
```bash
mkdir -p /home/hasryl/livi/livi-ai-api/routers
touch /home/hasryl/livi/livi-ai-api/routers/__init__.py
```

Write a stub `livi-ai-api/routers/config.py` (just enough for auth tests to pass):
```python
from fastapi import APIRouter

router = APIRouter()


@router.get("/config")
async def get_config():
    return {}
```

- [ ] **Step 2.6: Run auth tests — they should pass**

```bash
cd /home/hasryl/livi/livi-ai-api
pip install -r requirements.txt
python -m pytest tests/test_auth.py -v
```

Expected:
```
PASSED tests/test_auth.py::test_missing_api_key_returns_401
PASSED tests/test_auth.py::test_wrong_api_key_returns_403
PASSED tests/test_auth.py::test_correct_api_key_passes
```

- [ ] **Step 2.7: Commit**

```bash
cd /home/hasryl/livi/livi-ai-api
git add requirements.txt .env.example auth.py db.py schemas.py main.py routers/ tests/
git commit -m "feat: FastAPI skeleton with API key auth middleware"
```

---

## Task 3: Config Endpoints (GET + PUT /api/config)

**Files:**
- Modify: `livi-ai-api/routers/config.py`
- Create: `livi-ai-api/tests/test_config.py`

- [ ] **Step 3.1: Write failing config tests**

`livi-ai-api/tests/test_config.py`:
```python
import pytest
from httpx import AsyncClient

HEADERS = {"X-API-Key": "test-secret"}


@pytest.mark.asyncio
async def test_get_config_returns_all_keys(client: AsyncClient):
    response = await client.get("/api/config", headers=HEADERS)
    assert response.status_code == 200
    data = response.json()
    assert "system_prompt" in data
    assert "welcome_message" in data
    assert "lead_trigger_keywords" in data
    assert "value" in data["system_prompt"]
    assert "updated_by" in data["system_prompt"]


@pytest.mark.asyncio
async def test_put_config_updates_value(client: AsyncClient):
    new_value = "Kamu adalah OLIV versi baru."
    response = await client.put(
        "/api/config",
        headers=HEADERS,
        json={"key": "system_prompt", "value": new_value, "updated_by": "hasryl"},
    )
    assert response.status_code == 200
    assert response.json()["key"] == "system_prompt"
    assert response.json()["updated_by"] == "hasryl"


@pytest.mark.asyncio
async def test_put_config_rejects_unknown_key(client: AsyncClient):
    response = await client.put(
        "/api/config",
        headers=HEADERS,
        json={"key": "unknown_key", "value": "x", "updated_by": "hasryl"},
    )
    assert response.status_code == 422


@pytest.mark.asyncio
async def test_put_config_requires_updated_by(client: AsyncClient):
    response = await client.put(
        "/api/config",
        headers=HEADERS,
        json={"key": "system_prompt", "value": "x"},
    )
    assert response.status_code == 422
```

- [ ] **Step 3.2: Run tests to confirm they fail**

```bash
python -m pytest tests/test_config.py -v
```

Expected: `FAILED` — config endpoints return `{}` stubs.

- [ ] **Step 3.3: Implement config router**

Replace `livi-ai-api/routers/config.py`:

```python
from fastapi import APIRouter, Depends, HTTPException
from asyncpg import Connection
from db import get_db
from schemas import ConfigUpdateRequest

router = APIRouter()

ALLOWED_KEYS = {"system_prompt", "welcome_message", "lead_trigger_keywords"}


@router.get("/config")
async def get_config(db: Connection = Depends(get_db)):
    rows = await db.fetch(
        "SELECT key, value, updated_by, updated_at::text FROM chatbot_config"
    )
    return {
        row["key"]: {
            "value": row["value"],
            "updated_by": row["updated_by"],
            "updated_at": row["updated_at"],
        }
        for row in rows
    }


@router.put("/config")
async def update_config(
    payload: ConfigUpdateRequest,
    db: Connection = Depends(get_db),
):
    if payload.key not in ALLOWED_KEYS:
        raise HTTPException(status_code=422, detail=f"Unknown config key: {payload.key}")

    row = await db.fetchrow(
        """
        UPDATE chatbot_config
        SET value = $1, updated_by = $2, updated_at = NOW()
        WHERE key = $3
        RETURNING key, value, updated_by, updated_at::text
        """,
        payload.value,
        payload.updated_by,
        payload.key,
    )

    if row is None:
        raise HTTPException(status_code=404, detail=f"Config key not found: {payload.key}")

    return {
        "key": row["key"],
        "value": row["value"],
        "updated_by": row["updated_by"],
        "updated_at": row["updated_at"],
    }
```

- [ ] **Step 3.4: Run tests — they should pass**

```bash
python -m pytest tests/ -v
```

Expected: all 6 tests pass (3 auth + 4 config — note: test_config needs a real test DB with the seeded rows from migration 001. If running locally without a test DB, mock `get_db`; otherwise run against the seeded test database).

- [ ] **Step 3.5: Manual smoke test**

```bash
uvicorn main:app --reload --port 8000
# In another terminal:
curl -H "X-API-Key: your-key" http://localhost:8000/api/config
# Expected: {"system_prompt": {"value": "Kamu adalah OLIV...", ...}, ...}

curl -X PUT -H "X-API-Key: your-key" -H "Content-Type: application/json" \
  -d '{"key":"welcome_message","value":"Selamat datang!","updated_by":"hasryl"}' \
  http://localhost:8000/api/config
# Expected: {"key": "welcome_message", "value": "Selamat datang!", ...}
```

- [ ] **Step 3.6: Commit**

```bash
cd /home/hasryl/livi/livi-ai-api
git add routers/config.py tests/test_config.py
git commit -m "feat: add GET /api/config and PUT /api/config endpoints"
```

---

## Task 4: Laravel Config + AiApiService

**Files:**
- Create: `cms-livi/config/ai-api.php`
- Modify: `cms-livi/.env` (add 2 vars)
- Create: `cms-livi/app/Services/AiApiService.php`
- Create: `cms-livi/tests/Feature/Services/AiApiServiceTest.php`

- [ ] **Step 4.1: Add env vars to .env**

Append to `cms-livi/.env`:
```
AI_API_URL=http://103.77.107.162:8000
AI_API_KEY=your-shared-secret-here
```

- [ ] **Step 4.2: Create config file**

`cms-livi/config/ai-api.php`:
```php
<?php

return [
    'url' => env('AI_API_URL', 'http://localhost:8000'),
    'key' => env('AI_API_KEY', ''),
];
```

- [ ] **Step 4.3: Write failing service test**

`cms-livi/tests/Feature/Services/AiApiServiceTest.php`:
```php
<?php

namespace Tests\Feature\Services;

use App\Services\AiApiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiApiServiceTest extends TestCase
{
    public function test_get_config_returns_all_keys(): void
    {
        Http::fake([
            '*/api/config' => Http::response([
                'system_prompt' => ['value' => 'Kamu adalah OLIV', 'updated_by' => 'hasryl', 'updated_at' => '2026-07-08'],
                'welcome_message' => ['value' => 'Halo!', 'updated_by' => 'hasryl', 'updated_at' => '2026-07-08'],
                'lead_trigger_keywords' => ['value' => 'beli,order', 'updated_by' => 'hasryl', 'updated_at' => '2026-07-08'],
            ], 200),
        ]);

        $service = new AiApiService();
        $config = $service->getConfig();

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('X-API-Key')
                && str_ends_with($request->url(), '/api/config');
        });

        $this->assertArrayHasKey('system_prompt', $config);
        $this->assertEquals('Kamu adalah OLIV', $config['system_prompt']['value']);
    }

    public function test_set_config_sends_put_with_correct_payload(): void
    {
        Http::fake([
            '*/api/config' => Http::response([
                'key' => 'system_prompt',
                'value' => 'New prompt',
                'updated_by' => 'hasryl',
                'updated_at' => '2026-07-08',
            ], 200),
        ]);

        $service = new AiApiService();
        $service->setConfig('system_prompt', 'New prompt', 'hasryl');

        Http::assertSent(function (Request $request) {
            return $request->method() === 'PUT'
                && $request['key'] === 'system_prompt'
                && $request['value'] === 'New prompt'
                && $request['updated_by'] === 'hasryl';
        });
    }

    public function test_get_config_throws_on_api_error(): void
    {
        Http::fake([
            '*/api/config' => Http::response([], 500),
        ]);

        $this->expectException(\RuntimeException::class);

        $service = new AiApiService();
        $service->getConfig();
    }
}
```

- [ ] **Step 4.4: Run test to confirm it fails**

```bash
cd /home/hasryl/livi/cms-livi
php artisan test tests/Feature/Services/AiApiServiceTest.php
```

Expected: `Error: Class "App\Services\AiApiService" not found`

- [ ] **Step 4.5: Implement AiApiService**

`cms-livi/app/Services/AiApiService.php`:
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai-api.url'), '/');
        $this->apiKey  = config('ai-api.key');
    }

    public function getConfig(): array
    {
        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout(10)
            ->get("{$this->baseUrl}/api/config");

        if ($response->failed()) {
            throw new RuntimeException("AI API error: {$response->status()}");
        }

        return $response->json();
    }

    public function setConfig(string $key, string $value, string $updatedBy): array
    {
        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->timeout(10)
            ->put("{$this->baseUrl}/api/config", [
                'key'        => $key,
                'value'      => $value,
                'updated_by' => $updatedBy,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("AI API error updating {$key}: {$response->status()}");
        }

        return $response->json();
    }
}
```

- [ ] **Step 4.6: Run tests — they should pass**

```bash
php artisan test tests/Feature/Services/AiApiServiceTest.php
```

Expected:
```
PASS  Tests\Feature\Services\AiApiServiceTest
✓ get config returns all keys
✓ set config sends put with correct payload
✓ get config throws on api error
```

- [ ] **Step 4.7: Commit**

```bash
cd /home/hasryl/livi/cms-livi
git add config/ai-api.php app/Services/AiApiService.php tests/Feature/Services/AiApiServiceTest.php .env.example
git commit -m "feat: add AiApiService HTTP client and ai-api config"
```

---

## Task 5: Filament ChatbotConfig Settings Page

**Files:**
- Create: `cms-livi/app/Filament/Admin/Pages/ChatbotConfig.php`
- Create: `cms-livi/tests/Feature/Filament/ChatbotConfigPageTest.php`

- [ ] **Step 5.1: Write failing Filament page test**

`cms-livi/tests/Feature/Filament/ChatbotConfigPageTest.php`:
```php
<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\ChatbotConfig;
use App\Models\User;
use App\Services\AiApiService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ChatbotConfigPageTest extends TestCase
{
    private function mockConfig(): array
    {
        return [
            'system_prompt'          => ['value' => 'Kamu adalah OLIV', 'updated_by' => 'hasryl', 'updated_at' => '2026-07-08T10:00:00Z'],
            'welcome_message'        => ['value' => 'Halo!', 'updated_by' => 'hasryl', 'updated_at' => '2026-07-08T10:00:00Z'],
            'lead_trigger_keywords'  => ['value' => 'beli,order,harga', 'updated_by' => 'hasryl', 'updated_at' => '2026-07-08T10:00:00Z'],
        ];
    }

    public function test_page_loads_and_shows_current_config(): void
    {
        $this->mock(AiApiService::class, function ($mock) {
            $mock->shouldReceive('getConfig')->once()->andReturn($this->mockConfig());
        });

        $admin = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ChatbotConfig::class)
            ->assertSee('Kamu adalah OLIV')
            ->assertSee('Halo!');
    }

    public function test_save_calls_set_config_for_each_changed_field(): void
    {
        $this->mock(AiApiService::class, function ($mock) {
            $mock->shouldReceive('getConfig')->once()->andReturn($this->mockConfig());
            $mock->shouldReceive('setConfig')
                ->with('system_prompt', 'Updated prompt', \Mockery::any())
                ->once();
            $mock->shouldReceive('setConfig')
                ->with('welcome_message', 'Halo!', \Mockery::any())
                ->once();
            $mock->shouldReceive('setConfig')
                ->with('lead_trigger_keywords', 'beli,order,harga', \Mockery::any())
                ->once();
        });

        $admin = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ChatbotConfig::class)
            ->set('data.system_prompt', 'Updated prompt')
            ->callAction('save')
            ->assertHasNoErrors();
    }
}
```

- [ ] **Step 5.2: Run test to confirm it fails**

```bash
php artisan test tests/Feature/Filament/ChatbotConfigPageTest.php
```

Expected: `Error: Class "App\Filament\Admin\Pages\ChatbotConfig" not found`

- [ ] **Step 5.3: Implement the Filament page**

`cms-livi/app/Filament/Admin/Pages/ChatbotConfig.php`:
```php
<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiApiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ChatbotConfig extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'System Configuration';
    protected static string|null $navigationGroup = 'AI CHATBOT';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.admin.pages.chatbot-config';

    public ?array $data = [];

    private string $lastUpdatedBy = '';
    private string $lastUpdatedAt = '';

    public function mount(): void
    {
        $config = app(AiApiService::class)->getConfig();

        $keywords = array_filter(
            explode(',', $config['lead_trigger_keywords']['value'] ?? '')
        );

        $this->data = [
            'system_prompt'         => $config['system_prompt']['value'] ?? '',
            'welcome_message'       => $config['welcome_message']['value'] ?? '',
            'lead_trigger_keywords' => array_values($keywords),
        ];

        $this->lastUpdatedBy = $config['system_prompt']['updated_by'] ?? '';
        $this->lastUpdatedAt = $config['system_prompt']['updated_at'] ?? '';

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('AI Configuration')
                    ->description('Changes take effect on the next chatbot request — no deployment needed.')
                    ->schema([
                        Textarea::make('system_prompt')
                            ->label('System Prompt')
                            ->required()
                            ->rows(6)
                            ->helperText('Main persona and behavior rules for OLIV.')
                            ->columnSpanFull(),

                        TextInput::make('welcome_message')
                            ->label('Welcome Message')
                            ->required()
                            ->helperText('First message shown to the user when the chatbot opens.')
                            ->columnSpanFull(),

                        TagsInput::make('lead_trigger_keywords')
                            ->label('Lead Trigger Keywords')
                            ->helperText('Words that trigger lead capture in n8n. Press Enter to add.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $updatedBy = auth()->user()->name ?? auth()->user()->email;

        $api = app(AiApiService::class);

        $api->setConfig('system_prompt', $data['system_prompt'], $updatedBy);
        $api->setConfig('welcome_message', $data['welcome_message'], $updatedBy);
        $api->setConfig(
            'lead_trigger_keywords',
            implode(',', $data['lead_trigger_keywords'] ?? []),
            $updatedBy
        );

        Notification::make()
            ->title('Configuration saved')
            ->body('Changes will take effect on the next chatbot request.')
            ->success()
            ->send();
    }

    public function getLastUpdatedBy(): string
    {
        return $this->lastUpdatedBy;
    }

    public function getLastUpdatedAt(): string
    {
        return $this->lastUpdatedAt;
    }
}
```

- [ ] **Step 5.4: Create the Blade view**

Create directory and file:
```bash
mkdir -p /home/hasryl/livi/cms-livi/resources/views/filament/admin/pages
```

`cms-livi/resources/views/filament/admin/pages/chatbot-config.blade.php`:
```blade
<x-filament-panels::page>

    @if ($this->getLastUpdatedBy())
        <div class="text-sm text-gray-500 dark:text-gray-400 -mt-2 mb-4">
            Last updated by <strong>{{ $this->getLastUpdatedBy() }}</strong>
            · {{ $this->getLastUpdatedAt() }}
        </div>
    @endif

    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
    </x-filament-panels::form>

</x-filament-panels::page>
```

- [ ] **Step 5.5: Run tests**

```bash
php artisan test tests/Feature/Filament/ChatbotConfigPageTest.php
```

Expected:
```
PASS  Tests\Feature\Filament\ChatbotConfigPageTest
✓ page loads and shows current config
✓ save calls set config for each changed field
```

- [ ] **Step 5.6: Manual browser test**

```bash
php artisan serve
# Open http://localhost:8000/admin
# Navigate to AI Chatbot › System Configuration
# Verify: 3 fields load with values from the API
# Edit system prompt → click Save Changes
# Verify: success notification appears
```

- [ ] **Step 5.7: Commit**

```bash
cd /home/hasryl/livi/cms-livi
git add app/Filament/Admin/Pages/ChatbotConfig.php \
        resources/views/filament/admin/pages/chatbot-config.blade.php \
        tests/Feature/Filament/ChatbotConfigPageTest.php
git commit -m "feat: add ChatbotConfig Filament settings page for AI system message"
```

---

## Task 6: Update n8n Workflow to Fetch System Prompt Dynamically

**This task is manual — done in the n8n UI on the AI VPS.**

- [ ] **Step 6.1: Open the LIVI chatbot workflow in n8n**

Navigate to n8n → open the `livi_chaatbot` workflow.

- [ ] **Step 6.2: Add a Postgres node at the very start of the workflow**

Before the AI Agent node, add a new **Postgres** node:
- Operation: `Execute Query`
- Query:
```sql
SELECT value FROM chatbot_config WHERE key = 'system_prompt' LIMIT 1;
```
- Credential: use the existing Postgres credential connected to the AI VPS DB
- Name the node: `Fetch System Prompt`

Connect it: `Webhook → Fetch System Prompt → AI Agent`

- [ ] **Step 6.3: Wire the system prompt into the AI Agent node**

In the AI Agent node, change the **System Message** field from a hardcoded string to an expression:
```
{{ $('Fetch System Prompt').item.json.value }}
```

- [ ] **Step 6.4: Test the workflow end-to-end**

Send a test message to the chatbot webhook:
```bash
curl -X POST https://dev-n8n.wellmagicteam.com/webhook/b082404f-119e-44d1-9207-5bf977dce48a \
  -H "Content-Type: application/json" \
  -d '{"chatInput": "Halo, apa produk LIVI?", "sessionId": "test-session-001"}'
```

Expected: chatbot responds using the system prompt from `chatbot_config` table.

- [ ] **Step 6.5: Change prompt via CMS and re-test**

1. Go to CMS → AI Chatbot → System Configuration
2. Append "Selalu jawab dalam Bahasa Indonesia." to the system prompt
3. Save Changes
4. Re-send the curl command above
5. Verify the bot behavior reflects the updated prompt

- [ ] **Step 6.6: Export and save the updated n8n workflow JSON**

In n8n → Export workflow → Save as `livi_chaatbot_v2.json` in `/home/hasryl/livi/`:

```bash
cd /home/hasryl/livi
git add livi_chaatbot_v2.json
git commit -m "feat: update n8n chatbot workflow to fetch system_prompt from chatbot_config table"
```

---

## Week 1 Complete ✓

At the end of these 6 tasks you have:
- 3 Postgres tables created and seeded on the AI VPS
- FastAPI service running with API key auth, health endpoint, and config endpoints
- Laravel `AiApiService` HTTP client, fully tested with HTTP mocks
- Filament settings page at **AI Chatbot › System Configuration** — admins can edit system prompt, welcome message, and lead trigger keywords live
- n8n workflow fetching system prompt dynamically — no more hardcoded prompts

**Next plan:** `2026-07-08-week2-chat-log-history.md` — Filament chat log viewer with filters, keyword search, and session detail view.
