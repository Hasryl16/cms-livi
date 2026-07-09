import json
import os
import asyncpg
from contextlib import asynccontextmanager
from fastapi import FastAPI

_pool = None


async def _init_conn(conn: asyncpg.Connection) -> None:
    # asyncpg returns jsonb as raw text by default — decode to Python dicts
    await conn.set_type_codec(
        'jsonb',
        encoder=json.dumps,
        decoder=json.loads,
        schema='pg_catalog',
    )
    await conn.set_type_codec(
        'json',
        encoder=json.dumps,
        decoder=json.loads,
        schema='pg_catalog',
    )


async def get_pool():
    global _pool
    if _pool is None:
        _pool = await asyncpg.create_pool(
            os.getenv("DATABASE_URL"),
            init=_init_conn,
        )
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
