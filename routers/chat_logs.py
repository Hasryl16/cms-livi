import asyncpg
from fastapi import APIRouter, Depends, HTTPException, Query
from asyncpg import Connection
from db import get_db
from typing import Optional

router = APIRouter()

# Standard n8n Postgres Chat Memory schema:
#   session_id TEXT, message JSONB, created_at TIMESTAMPTZ
# Content lives at: message->'data'->>'content'
# Role lives at: message->>'type'  ("human" | "ai")
_N8N_CONTENT = "h.message->'data'->>'content'"
_N8N_TS = "h.created_at"


def _base_cte(date_cond: str, date_to_cond: str, search_cond: str,
              with_leads: bool) -> str:
    if with_leads:
        return f"""
            SELECT
                h.session_id,
                MIN({_N8N_CONTENT}) AS first_message,
                COUNT(*) AS msg_count,
                l.id AS lead_id,
                l.name AS lead_name,
                MIN({_N8N_TS})::text AS started_at
            FROM n8n_chat_histories h
            LEFT JOIN leads l ON l.session_id = h.session_id
            WHERE {date_cond} {date_to_cond} {search_cond}
            GROUP BY h.session_id, l.id, l.name
        """
    return f"""
        SELECT
            h.session_id,
            MIN({_N8N_CONTENT}) AS first_message,
            COUNT(*) AS msg_count,
            NULL::integer AS lead_id,
            NULL::text AS lead_name,
            MIN({_N8N_TS})::text AS started_at
        FROM n8n_chat_histories h
        WHERE {date_cond} {date_to_cond} {search_cond}
        GROUP BY h.session_id
    """


async def _fetch_sessions(db: Connection, base: str, outer_filter: str,
                          filter_params: list, per_page: int, offset: int):
    """Run count + data queries. filter_params must NOT include limit/offset."""
    n = len(filter_params)
    count_q = f"SELECT COUNT(*) FROM ({base}) sub {outer_filter}"
    total_row = await db.fetchrow(count_q, *filter_params)
    total = total_row[0] if total_row else 0

    data_q = (
        f"SELECT * FROM ({base} ORDER BY started_at DESC) sub "
        f"{outer_filter} LIMIT ${n + 1} OFFSET ${n + 2}"
    )
    rows = await db.fetch(data_q, *filter_params, per_page, offset)
    return rows, total


@router.get("/chat-logs")
async def list_chat_logs(
    date_from: Optional[str] = None,
    date_to: Optional[str] = None,
    lead_status: Optional[str] = None,
    search: Optional[str] = None,
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    db: Connection = Depends(get_db),
):
    filter_params: list = []

    def p(val) -> str:
        filter_params.append(val)
        return f"${len(filter_params)}"

    date_cond = (f"{_N8N_TS} >= {p(date_from)}::timestamp" if date_from
                 else f"{_N8N_TS} >= NOW() - INTERVAL '30 days'")
    date_to_cond = f"AND {_N8N_TS} <= {p(date_to)}::timestamp" if date_to else ""
    search_cond = f"AND {_N8N_CONTENT} ILIKE {p(f'%{search}%')}" if search else ""

    if lead_status == "lead_captured":
        outer_filter = "WHERE lead_id IS NOT NULL"
    elif lead_status == "no_lead":
        outer_filter = "WHERE lead_id IS NULL AND msg_count > 1"
    elif lead_status == "abandoned":
        outer_filter = "WHERE lead_id IS NULL AND msg_count = 1"
    else:
        outer_filter = ""

    offset = (page - 1) * per_page

    try:
        base = _base_cte(date_cond, date_to_cond, search_cond, with_leads=True)
        rows, total = await _fetch_sessions(db, base, outer_filter, list(filter_params), per_page, offset)
    except (asyncpg.UndefinedTableError, asyncpg.UndefinedColumnError):
        # leads table/column absent — retry without the join
        try:
            base = _base_cte(date_cond, date_to_cond, search_cond, with_leads=False)
            rows, total = await _fetch_sessions(db, base, outer_filter, list(filter_params), per_page, offset)
        except asyncpg.PostgresError as e:
            raise HTTPException(status_code=503, detail=str(e))
    except asyncpg.PostgresError as e:
        raise HTTPException(status_code=503, detail=str(e))

    return {
        "data": [dict(r) for r in rows],
        "pagination": {
            "page": page,
            "per_page": per_page,
            "total": total,
            "total_pages": -(-total // per_page),
        },
    }


@router.get("/chat-logs/{session_id}")
async def get_chat_log(
    session_id: str,
    db: Connection = Depends(get_db),
):
    try:
        rows = await db.fetch(
            f"""
            SELECT id, session_id, message, {_N8N_TS}::text AS created_at
            FROM n8n_chat_histories
            WHERE session_id = $1
            ORDER BY {_N8N_TS} ASC
            """,
            session_id,
        )
    except asyncpg.PostgresError as e:
        raise HTTPException(status_code=503, detail=str(e))

    if not rows:
        raise HTTPException(status_code=404, detail="Session not found")

    messages = []
    for row in rows:
        r = dict(row)
        msg = r.get("message")
        if isinstance(msg, dict):
            role = "user" if msg.get("type") == "human" else "oliv"
            content = msg.get("data", {}).get("content", "")
        else:
            role = r.get("role", "user")
            content = r.get("content", "")

        messages.append({
            "id": r.get("id"),
            "role": role,
            "content": content,
            "created_at": r.get("created_at", ""),
        })

    try:
        lead = await db.fetchrow(
            "SELECT id, name, phone, email, company, created_at::text FROM leads WHERE session_id = $1 LIMIT 1",
            session_id,
        )
    except asyncpg.PostgresError:
        lead = None

    return {
        "session_id": session_id,
        "messages": messages,
        "lead": dict(lead) if lead else None,
        "message_count": len(messages),
    }
