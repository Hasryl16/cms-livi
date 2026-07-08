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
