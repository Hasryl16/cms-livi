CREATE TABLE IF NOT EXISTS chatbot_config (
    id          SERIAL PRIMARY KEY,
    key         VARCHAR(100) UNIQUE NOT NULL,
    value       TEXT NOT NULL,
    updated_by  VARCHAR(100) NOT NULL,
    updated_at  TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

INSERT INTO chatbot_config (key, value, updated_by) VALUES
    ('system_prompt',           'Kamu adalah OLIV, asisten virtual LIVI dari Sinarmas Group.', 'system'),
    ('welcome_message',         'Halo! Ada yang bisa dibantu hari ini?', 'system'),
    ('lead_trigger_keywords',   'beli,order,harga,quotation', 'system')
ON CONFLICT (key) DO NOTHING;
