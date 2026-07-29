-- Script de criação do schema do banco de dados apollo_ia_db
-- Execução: psql -h 127.0.0.1 -U "__lucas.cardoso" -d apollo_ia_db -f database/schema.sql

CREATE TABLE IF NOT EXISTS perguntas (
    id          SERIAL PRIMARY KEY,
    pergunta    TEXT NOT NULL,
    resposta    TEXT,
    modelo_ia   VARCHAR(50) NOT NULL DEFAULT 'gemini-2.0-flash',
    criado_em   TIMESTAMP NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE perguntas IS 'Armazena o histórico de perguntas feitas e respostas geradas pela IA';
COMMENT ON COLUMN perguntas.resposta IS 'Pode ser NULL temporariamente caso a chamada à API falhe antes de obter resposta';
