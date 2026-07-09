import pytest
from unittest.mock import AsyncMock, MagicMock
import db as db_module


def make_client_with_conn(monkeypatch, mock_conn):
    """Helper to patch DB and return a preconfigured client fixture context."""
    mock_pool = MagicMock()
    mock_pool.acquire = MagicMock()
    mock_pool.acquire.return_value.__aenter__ = AsyncMock(return_value=mock_conn)
    mock_pool.acquire.return_value.__aexit__ = AsyncMock(return_value=False)
    monkeypatch.setattr(db_module, "_pool", mock_pool)


HEADERS = {"X-API-Key": "test-secret"}

SAMPLE_SESSIONS = [
    {
        "session_id": "abc123",
        "first_message": "Halo, saya ingin tahu produk",
        "msg_count": 5,
        "lead_id": 1,
        "lead_name": "Budi",
        "started_at": "2026-07-08T10:00:00",
    }
]

SAMPLE_MESSAGES = [
    {"id": 1, "session_id": "abc123", "content": "Halo", "created_at": "2026-07-08T10:00:00"},
    {"id": 2, "session_id": "abc123", "content": "Ada yang bisa dibantu?", "created_at": "2026-07-08T10:00:05"},
]

SAMPLE_LEAD = {
    "id": 1,
    "name": "Budi",
    "phone": "08123456789",
    "email": "budi@example.com",
    "company": "PT Maju",
    "created_at": "2026-07-08T10:05:00",
}


@pytest.mark.asyncio
async def test_list_chat_logs_returns_paginated_data(client, monkeypatch):
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=SAMPLE_SESSIONS)
    mock_conn.fetchrow = AsyncMock(return_value=(1,))
    make_client_with_conn(monkeypatch, mock_conn)

    response = await client.get("/api/chat-logs", headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert "data" in body
    assert "pagination" in body
    assert body["pagination"]["page"] == 1
    assert body["pagination"]["per_page"] == 20


@pytest.mark.asyncio
async def test_list_chat_logs_requires_api_key(client):
    response = await client.get("/api/chat-logs")
    assert response.status_code == 401


@pytest.mark.asyncio
async def test_get_chat_log_detail_returns_messages_and_lead(client, monkeypatch):
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=SAMPLE_MESSAGES)
    mock_conn.fetchrow = AsyncMock(return_value=SAMPLE_LEAD)
    make_client_with_conn(monkeypatch, mock_conn)

    response = await client.get("/api/chat-logs/abc123", headers=HEADERS)
    assert response.status_code == 200
    body = response.json()
    assert body["session_id"] == "abc123"
    assert len(body["messages"]) == 2
    assert body["message_count"] == 2
    assert body["lead"]["name"] == "Budi"


@pytest.mark.asyncio
async def test_get_chat_log_detail_404_when_no_messages(client, monkeypatch):
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=[])
    mock_conn.fetchrow = AsyncMock(return_value=None)
    make_client_with_conn(monkeypatch, mock_conn)

    response = await client.get("/api/chat-logs/unknown-session", headers=HEADERS)
    assert response.status_code == 404


@pytest.mark.asyncio
async def test_get_chat_log_detail_no_lead_returns_none(client, monkeypatch):
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=SAMPLE_MESSAGES)
    mock_conn.fetchrow = AsyncMock(return_value=None)
    make_client_with_conn(monkeypatch, mock_conn)

    response = await client.get("/api/chat-logs/abc123", headers=HEADERS)
    assert response.status_code == 200
    assert response.json()["lead"] is None


@pytest.mark.asyncio
async def test_get_chat_log_normalises_jsonb_message(client, monkeypatch):
    """Handles n8n's JSONB message column format: {type, data: {content}}"""
    jsonb_messages = [
        {
            "id": 1,
            "session_id": "s1",
            "message": {"type": "human", "data": {"content": "Halo"}},
            "content": None,
            "role": None,
            "created_at": "2026-07-08T10:00:00",
        }
    ]
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=jsonb_messages)
    mock_conn.fetchrow = AsyncMock(return_value=None)
    make_client_with_conn(monkeypatch, mock_conn)

    response = await client.get("/api/chat-logs/s1", headers=HEADERS)
    assert response.status_code == 200
    msg = response.json()["messages"][0]
    assert msg["role"] == "user"
    assert msg["content"] == "Halo"
