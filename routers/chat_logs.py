import asyncpg
import re
from datetime import datetime
from fastapi import APIRouter, Depends, HTTPException, Query
from asyncpg import Connection
from db import get_db
from typing import Optional

_FUNC_TAG_RE = re.compile(r'<function=[^>]+>.*?</function>', re.DOTALL)

def _clean_content(text: str) -> str:
    return _FUNC_TAG_RE.sub('', text).strip()

router = APIRouter()

# Actual n8n_chat_histories schema (confirmed from DB):
#   id SERIAL, session_id VARCHAR(255), message JSONB
#   — NO timestamp column; use id (auto-increment) for ordering
#
# LangChain message format stored by n8n:
#   {"type": "human"|"ai"|"tool", "content": "...", "tool_calls": [...], ...}
#   content is directly at message->>'content', NOT nested under data
#   Filter: skip type="tool" (Qdrant results) and type="ai" with tool_calls
#
# leads schema: id, name, company, whatsapp, notes, lead_type, session_id, created_at
# After running migration add_created_at.sql, n8n_chat_histories also has created_at
_N8N_CONTENT = "h.message->>'content'"
_N8N_TS = "h.created_at"  # available after ALTER TABLE migration


def _base_cte(date_cond: str, date_to_cond: str, search_cond: str,
              with_leads: bool) -> str:
    n8n_part = f"""
        SELECT
            h.session_id,
            MIN({_N8N_CONTENT}) AS first_message,
            COUNT(*) AS msg_count,
            l.id AS lead_id,
            l.name AS lead_name,
            COALESCE(MIN({_N8N_TS}), MIN(l.created_at))::text AS started_at,
            MIN(h.id)::bigint AS min_id
        FROM n8n_chat_histories h
        LEFT JOIN leads l ON l.session_id = h.session_id
        WHERE 1=1 {date_cond} {date_to_cond} {search_cond}
        GROUP BY h.session_id, l.id, l.name
    """
    if not with_leads:
        return n8n_part

    # Lead-only sessions: quick-reply flows that never sent a message to n8n AI
    lead_only = f"""
        SELECT
            l.session_id,
            '[Lead form submitted]'::text AS first_message,
            0::bigint AS msg_count,
            l.id AS lead_id,
            l.name AS lead_name,
            l.created_at::text AS started_at,
            NULL::bigint AS min_id
        FROM leads l
        WHERE NOT EXISTS (
            SELECT 1 FROM n8n_chat_histories h WHERE h.session_id = l.session_id
        )
        {date_cond.replace('h.', 'l.')} {date_to_cond.replace('h.', 'l.')}
    """
    return f"({n8n_part} UNION ALL {lead_only})"


async def _fetch_sessions(db: Connection, base: str, outer_filter: str,
                          filter_params: list, per_page: int, offset: int):
    n = len(filter_params)
    count_q = f"SELECT COUNT(*) FROM ({base}) sub {outer_filter}"
    total_row = await db.fetchrow(count_q, *filter_params)
    total = total_row[0] if total_row else 0

    data_q = (
        f"SELECT * FROM ({base} ORDER BY started_at DESC NULLS LAST) sub "
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

    # Parse date strings to datetime — asyncpg requires datetime objects, not strings
    def parse_date(s: str) -> datetime:
        return datetime.strptime(s, "%Y-%m-%d")

    date_cond = f"AND {_N8N_TS} >= {p(parse_date(date_from))}" if date_from else ""
    date_to_cond = f"AND {_N8N_TS} <= {p(parse_date(date_to))}" if date_to else ""
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
        try:
            base = _base_cte("", "", search_cond, with_leads=False)
            no_date_params = [p for p in filter_params if search and p == f"%{search}%"] if search else []
            rows, total = await _fetch_sessions(db, base, outer_filter, no_date_params, per_page, offset)
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
            """
            SELECT id, session_id, message,
                   created_at::text AS created_at
            FROM n8n_chat_histories
            WHERE session_id = $1
            ORDER BY id ASC
            """,
            session_id,
        )
    except asyncpg.UndefinedColumnError:
        # created_at column not yet added — run the migration SQL to add it
        rows = await db.fetch(
            "SELECT id, session_id, message FROM n8n_chat_histories WHERE session_id = $1 ORDER BY id ASC",
            session_id,
        )
    except asyncpg.PostgresError as e:
        raise HTTPException(status_code=503, detail=str(e))

    messages = []
    for row in rows:
        r = dict(row)
        msg = r.get("message")
        if not isinstance(msg, dict):
            continue

        msg_type = msg.get("type", "")
        content = msg.get("content", "")

        # Skip tool result messages (Qdrant responses) and AI tool-call invocations
        if msg_type == "tool":
            continue
        if msg_type == "ai" and msg.get("tool_calls"):
            continue
        if not content or not str(content).strip():
            continue

        clean = _clean_content(str(content))
        if not clean:
            continue

        messages.append({
            "id": r.get("id"),
            "role": "user" if msg_type == "human" else "oliv",
            "content": clean,
            "created_at": r.get("created_at"),
        })

    try:
        lead = await db.fetchrow(
            """
            SELECT id, name, company, whatsapp, notes, lead_type,
                   session_id, created_at::text
            FROM leads WHERE session_id = $1 LIMIT 1
            """,
            session_id,
        )
    except asyncpg.PostgresError:
        lead = None

    # 404 only if no n8n messages AND no lead record
    if not messages and not lead:
        raise HTTPException(status_code=404, detail="Session not found")

    # started_at: prefer first message's timestamp, fall back to lead.created_at
    started_at = (messages[0].get("created_at") if messages else None) \
                 or (dict(lead).get("created_at") if lead else None)

    return {
        "session_id": session_id,
        "messages": messages,
        "lead": dict(lead) if lead else None,
        "message_count": len(messages),
        "started_at": started_at,
    }
