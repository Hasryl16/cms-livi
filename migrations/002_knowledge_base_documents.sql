CREATE TABLE IF NOT EXISTS knowledge_base_documents (
    id              SERIAL PRIMARY KEY,
    filename        VARCHAR(500) NOT NULL,
    file_type       VARCHAR(20) NOT NULL CHECK (file_type IN ('pdf', 'docx', 'xlsx')),
    status          VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (status IN ('pending', 'processing', 'indexed', 'failed', 'superseded')),
    version         INTEGER NOT NULL DEFAULT 1,
    supersedes_id   INTEGER REFERENCES knowledge_base_documents(id),
    chunk_count     INTEGER,
    uploaded_by     VARCHAR(100) NOT NULL,
    uploaded_at     TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    indexed_at      TIMESTAMP WITH TIME ZONE,
    error_message   TEXT
);
CREATE INDEX idx_kbd_filename ON knowledge_base_documents(filename);
CREATE INDEX idx_kbd_status   ON knowledge_base_documents(status);
