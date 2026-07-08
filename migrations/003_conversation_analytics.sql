CREATE TABLE IF NOT EXISTS conversation_analytics (
    id                      SERIAL PRIMARY KEY,
    session_id              VARCHAR(200) NOT NULL,
    token_usage             INTEGER,
    latency_ms              INTEGER,
    model                   VARCHAR(100),
    fallback_triggered      BOOLEAN DEFAULT FALSE,
    qdrant_relevance_score  FLOAT,
    intent_tag              VARCHAR(100),
    csat_score              INTEGER,
    session_abandoned       BOOLEAN DEFAULT FALSE,
    conversation_length     INTEGER,
    created_at              TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX idx_ca_session_id  ON conversation_analytics(session_id);
CREATE INDEX idx_ca_created_at  ON conversation_analytics(created_at);
