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

# Actual n8n LangChain format: content is flat, not nested under data
SAMPLE_MESSAGES = [
    {"id": 1, "session_id": "abc123", "message": {"type": "human", "content": "Halo", "additional_kwargs": {}, "response_metadata": {}}},
    {"id": 2, "session_id": "abc123", "message": {"type": "ai", "content": "Ada yang bisa dibantu?", "additional_kwargs": {}, "response_metadata": {}}},
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
async def test_get_chat_log_filters_tool_and_tool_call_messages(client, monkeypatch):
    """tool messages (Qdrant) and ai messages with tool_calls are filtered out."""
    mixed_messages = [
        {"id": 1, "session_id": "s1", "message": {"type": "human", "content": "Halo"}},
        # AI invoking Qdrant — should be filtered
        {"id": 2, "session_id": "s1", "message": {"type": "ai", "content": "Calling tool", "tool_calls": [{"id": "x"}]}},
        # Qdrant tool result — should be filtered
        {"id": 3, "session_id": "s1", "message": {"type": "tool", "content": "[{...}]", "name": "Qdrant_Vector_Store"}},
        # Final AI response — should appear
        {"id": 4, "session_id": "s1", "message": {"type": "ai", "content": "Produk LIVI tersedia!"}},
    ]
    mock_conn = MagicMock()
    mock_conn.fetch = AsyncMock(return_value=mixed_messages)
    mock_conn.fetchrow = AsyncMock(return_value=None)
    make_client_with_conn(monkeypatch, mock_conn)

    response = await client.get("/api/chat-logs/s1", headers=HEADERS)
    assert response.status_code == 200
    msgs = response.json()["messages"]
    # Only human message + final AI response survive filtering
    assert len(msgs) == 2
    assert msgs[0]["role"] == "user"
    assert msgs[0]["content"] == "Halo"
    assert msgs[1]["role"] == "oliv"
    assert msgs[1]["content"] == "Produk LIVI tersedia!"
