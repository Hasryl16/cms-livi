import asyncpg
from fastapi import APIRouter, Depends
from asyncpg import Connection
from db import get_db

router = APIRouter()


@router.get("/analytics")
async def get_analytics(db: Connection = Depends(get_db)):
    """Return all 4 metric datasets for the CMS analytics dashboard."""

    # ── 1. VOLUME: daily sessions + messages for last 30 days ───────────────
    daily_rows = await db.fetch("""
        SELECT
            DATE(created_at AT TIME ZONE 'Asia/Jakarta')::text AS date,
            COUNT(DISTINCT session_id)                          AS sessions,
            COUNT(*) FILTER (WHERE message->>'type' = 'human') AS messages
        FROM n8n_chat_histories
        WHERE created_at >= NOW() - INTERVAL '30 days'
        GROUP BY 1
        ORDER BY 1
    """)

    hourly_rows = await db.fetch("""
        SELECT
            EXTRACT(HOUR FROM created_at AT TIME ZONE 'Asia/Jakarta')::int AS hour,
            COUNT(DISTINCT session_id) AS sessions
        FROM n8n_chat_histories
        GROUP BY 1
        ORDER BY 1
    """)

    dow_rows = await db.fetch("""
        SELECT
            EXTRACT(DOW FROM created_at AT TIME ZONE 'Asia/Jakarta')::int AS dow,
            TO_CHAR(created_at AT TIME ZONE 'Asia/Jakarta', 'Dy')         AS label,
            COUNT(DISTINCT session_id)                                     AS sessions
        FROM n8n_chat_histories
        GROUP BY 1, 2
        ORDER BY 1
    """)

    # ── 2. FUNNEL ────────────────────────────────────────────────────────────
    total_sessions = await db.fetchval(
        "SELECT COUNT(DISTINCT session_id) FROM n8n_chat_histories"
    )
    sessions_with_msgs = await db.fetchval("""
        SELECT COUNT(DISTINCT session_id)
        FROM n8n_chat_histories
        WHERE message->>'type' = 'human'
          AND TRIM(message->>'content') <> ''
    """)
    sessions_with_leads = await db.fetchval(
        "SELECT COUNT(DISTINCT session_id) FROM leads"
    )
    total_leads = await db.fetchval("SELECT COUNT(*) FROM leads")

    lead_type_rows = await db.fetch("""
        SELECT COALESCE(lead_type, 'unknown') AS lead_type, COUNT(*) AS cnt
        FROM leads
        GROUP BY 1
        ORDER BY 2 DESC
    """)

    # ── 3. TOPICS (keyword-based intent detection) ───────────────────────────
    topic_rows = await db.fetch("""
        SELECT
            CASE
                WHEN lower(message->>'content') SIMILAR TO
                     '%%(harga|price|biaya|cost|tarif|quotation|rfq)%%' THEN 'Harga / Pricing'
                WHEN lower(message->>'content') SIMILAR TO
                     '%%(partner|mitra|distributor|reseller|agen|kerjasama)%%' THEN 'Kemitraan'
                WHEN lower(message->>'content') SIMILAR TO
                     '%%(after sales|servis|service|garansi|warranty|claim|klaim)%%' THEN 'After Sales'
                WHEN lower(message->>'content') SIMILAR TO
                     '%%(order|pesan|beli|purchase|beli|indent)%%' THEN 'Pemesanan'
                WHEN lower(message->>'content') SIMILAR TO
                     '%%(produk|product|barang|tissue|dispenser|item|katalog)%%' THEN 'Informasi Produk'
                ELSE 'Lainnya'
            END AS topic,
            COUNT(DISTINCT session_id) AS session_count
        FROM n8n_chat_histories
        WHERE message->>'type' = 'human'
          AND TRIM(message->>'content') <> ''
          AND message->>'content' NOT LIKE 'ACTION:%%'
        GROUP BY 1
        ORDER BY 2 DESC
    """)

    # ── 4. AI PERFORMANCE ────────────────────────────────────────────────────
    perf = await db.fetchrow("""
        WITH per_session AS (
            SELECT
                session_id,
                COUNT(*) FILTER (WHERE message->>'type' = 'human') AS human_msgs,
                COUNT(*) FILTER (WHERE message->>'type' = 'ai'
                                   AND message->>'content' <> ''
                                   AND message->>'content' NOT LIKE 'Calling %%') AS ai_msgs,
                COUNT(*) FILTER (WHERE message->>'type' = 'ai'
                                   AND lower(message->>'content') SIMILAR TO
                                       '%%(maaf|tidak dapat|tidak bisa|tidak tahu|i don''t|sorry|belum bisa)%%'
                                   AND message->>'content' NOT LIKE 'Calling %%') AS fallbacks,
                COUNT(*) FILTER (WHERE message->>'type' = 'ai'
                                   AND message->>'content' LIKE 'Calling Qdrant%%') AS qdrant_calls
            FROM n8n_chat_histories
            GROUP BY session_id
        )
        SELECT
            ROUND(AVG(human_msgs + ai_msgs), 1)                     AS avg_msgs_per_session,
            SUM(fallbacks)                                           AS total_fallbacks,
            SUM(ai_msgs)                                             AS total_ai_msgs,
            CASE WHEN SUM(ai_msgs) > 0
                 THEN ROUND(SUM(fallbacks)::numeric / SUM(ai_msgs) * 100, 1)
                 ELSE 0 END                                          AS fallback_rate_pct,
            ROUND(AVG(qdrant_calls), 1)                              AS avg_qdrant_calls_per_session
        FROM per_session
    """)

    # 30-day summary counts
    summary_30d = await db.fetchrow("""
        SELECT
            COUNT(DISTINCT session_id) AS sessions_30d,
            COUNT(*) FILTER (WHERE message->>'type' = 'human') AS messages_30d
        FROM n8n_chat_histories
        WHERE created_at >= NOW() - INTERVAL '30 days'
    """)

    return {
        "volume": {
            "daily":  [dict(r) for r in daily_rows],
            "hourly": [dict(r) for r in hourly_rows],
            "dow":    [dict(r) for r in dow_rows],
            "summary_30d": {
                "sessions":  summary_30d["sessions_30d"],
                "messages":  summary_30d["messages_30d"],
            },
        },
        "funnel": {
            "total_sessions":       total_sessions,
            "sessions_with_msgs":   sessions_with_msgs,
            "sessions_with_leads":  sessions_with_leads,
            "total_leads":          total_leads,
            "lead_types":           [dict(r) for r in lead_type_rows],
        },
        "topics": [dict(r) for r in topic_rows],
        "performance": {
            "avg_msgs_per_session":        float(perf["avg_msgs_per_session"] or 0),
            "fallback_rate_pct":           float(perf["fallback_rate_pct"] or 0),
            "avg_qdrant_calls_per_session": float(perf["avg_qdrant_calls_per_session"] or 0),
            "total_fallbacks":             perf["total_fallbacks"] or 0,
            "total_ai_msgs":               perf["total_ai_msgs"] or 0,
        },
    }
