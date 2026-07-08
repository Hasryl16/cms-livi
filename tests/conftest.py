import os
import pytest
import pytest_asyncio
from httpx import AsyncClient, ASGITransport
from unittest.mock import AsyncMock, MagicMock

os.environ["CMS_API_KEY"] = "test-secret"
os.environ["DATABASE_URL"] = "postgresql://postgres:postgres@localhost:5432/livi_test"

from main import app
import db as db_module


@pytest_asyncio.fixture
async def client(monkeypatch):
    # Mock the DB pool so tests don't need a real Postgres connection
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=[
        {"key": "system_prompt", "value": "Kamu adalah OLIV", "updated_by": "system", "updated_at": "2026-07-08T00:00:00+00:00"},
        {"key": "welcome_message", "value": "Halo!", "updated_by": "system", "updated_at": "2026-07-08T00:00:00+00:00"},
        {"key": "lead_trigger_keywords", "value": "beli,order", "updated_by": "system", "updated_at": "2026-07-08T00:00:00+00:00"},
    ])
    mock_conn.fetchrow = AsyncMock(return_value={
        "key": "system_prompt", "value": "New value", "updated_by": "hasryl", "updated_at": "2026-07-08T00:00:00+00:00"
    })

    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock()
    mock_pool.acquire.return_value.__aenter__ = AsyncMock(return_value=mock_conn)
    mock_pool.acquire.return_value.__aexit__ = AsyncMock(return_value=False)

    monkeypatch.setattr(db_module, "_pool", mock_pool)

    async with AsyncClient(transport=ASGITransport(app=app), base_url="http://test") as ac:
        yield ac
