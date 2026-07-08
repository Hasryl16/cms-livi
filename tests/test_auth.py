import pytest
from httpx import AsyncClient


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
