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
    response = await client.put(
        "/api/config",
        headers=HEADERS,
        json={"key": "system_prompt", "value": "Kamu adalah OLIV versi baru.", "updated_by": "hasryl"},
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
