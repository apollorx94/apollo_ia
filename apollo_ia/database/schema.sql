-- ============================================================================
-- schema.sql — script de criação do banco de dados do Apollo IA
-- ============================================================================
--
-- Execução:
--   psql -h HOST -U USUARIO -d apollo_ia_db -f database/schema.sql
--
-- Este script é a "planta baixa" do banco: é usado tanto em desenvolvimento
-- local (WSL) quanto no pipeline de CI (GitHub Actions) e em produção
-- (Render) — sempre a mesma fonte de verdade para a estrutura do banco.
-- ============================================================================

-- IF NOT EXISTS: torna o script IDEMPOTENTE — pode ser executado várias
-- vezes sem erro, mesmo que a tabela já exista.
CREATE TABLE IF NOT EXISTS perguntas (
    -- Chave primária com autoincremento (internamente cria uma SEQUENCE
    -- chamada perguntas_id_seq, usada via nextval() a cada INSERT).
    id          SERIAL PRIMARY KEY,

    -- Texto da pergunta. NOT NULL: toda linha precisa ter uma pergunta.
    pergunta    TEXT NOT NULL,

    -- Texto da resposta da IA. Pode ser NULL: a pergunta é salva ANTES
    -- de a IA responder, para não perder o registro caso a chamada falhe.
    resposta    TEXT,

    -- Nome do modelo de IA que gerou (ou vai gerar) a resposta. Guardado
    -- por linha para dar RASTREABILIDADE: se o modelo padrão mudar no
    -- futuro, registros antigos continuam mostrando fielmente qual
    -- modelo realmente os respondeu.
    modelo_ia   VARCHAR(50) NOT NULL DEFAULT 'gemini-2.5-flash',

    -- Data/hora de criação, preenchida automaticamente pelo banco.
    criado_em   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Comentários armazenados nos METADADOS do próprio banco (visíveis via
-- "\d+ perguntas" no psql, ou em ferramentas como DBeaver/pgAdmin) —
-- documentação que vive junto com o dado, não apenas no arquivo .sql.
COMMENT ON TABLE perguntas IS 'Histórico de perguntas e respostas geradas pela IA';
COMMENT ON COLUMN perguntas.resposta IS 'Pode ser NULL temporariamente até a IA responder';
COMMENT ON COLUMN perguntas.modelo_ia IS 'Modelo de IA que gerou a resposta (rastreabilidade histórica)';
