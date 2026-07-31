# Apollo IA

![Testes](https://github.com/apollorx94/apollo_ia/actions/workflows/testes.yml/badge.svg?branch=main)

Sistema de Perguntas e Respostas com IA Generativa (Google Gemini), construído com PHP 8, PostgreSQL, Bootstrap 5 e JavaScript puro. Projeto acadêmico desenvolvido com arquitetura MVC, testes automatizados e pipeline de CI/CD.

> ⚠️ O badge acima só fica **verde** depois do primeiro push com o arquivo `.github/workflows/testes.yml` na branch `main` de um repositório **público** (badges de repositórios privados não renderizam publicamente). Veja a seção [CI/CD](#cicd) abaixo.

## Links do projeto

| Item | Link |
|---|---|
| Repositório GitHub | `https://github.com/apollorx94/apollo_ia` |
| Aplicação em produção | *(preencher com a URL da Render após o deploy, ex: `https://apollo-ia.onrender.com`)* |

## Stack

- **Backend:** PHP 8.4 (sem framework, arquitetura MVC própria)
- **Banco de dados:** PostgreSQL 18
- **Frontend:** HTML5 + Bootstrap 5 + JavaScript (Fetch API)
- **IA Generativa:** Google Gemini API (`gemini-2.5-flash`)
- **Testes:** PHPUnit 11 (unitários e de integração)
- **CI/CD:** GitHub Actions
- **Deploy:** Docker + Render
- **Gerenciador de dependências:** Composer (PSR-4 autoload)

## Estrutura do projeto

```
apollo_ia/
├── public/                    # Único ponto de entrada acessível via navegador
│   ├── index.php              # Serve a página HTML (view)
│   ├── api.php                # Roteador da API REST (JSON)
│   └── assets/js/app.js       # JavaScript do frontend
├── src/
│   ├── Config/Database.php    # Conexão PDO (Singleton)
│   ├── Model/Pergunta.php     # Entidade de domínio
│   ├── Repository/            # Acesso a dados (SQL)
│   ├── Service/GeminiService.php  # Integração com a API Gemini
│   └── Controller/             # Orquestração das requisições
├── views/home.php             # Template HTML principal
├── database/schema.sql        # Script de criação do banco
├── tests/
│   ├── Unit/                  # Testes isolados, sem banco/rede
│   └── Integration/           # Testes com banco real (transação + rollback)
├── .github/workflows/testes.yml  # Pipeline de CI
├── Dockerfile                 # Imagem para deploy
├── DECISOES.md                 # Por que essa stack, e o que foi aprendido
├── .env.example                # Modelo de variáveis de ambiente
└── composer.json
```

## Pré-requisitos

- PHP >= 8.2 com extensões `pdo`, `pdo_pgsql`, `curl`, `mbstring`
- Composer 2.x
- PostgreSQL >= 15
- Uma API Key do Gemini ([aistudio.google.com/apikey](https://aistudio.google.com/apikey))

## Instalação local

```bash
git clone git@github.com:apollorx94/apollo_ia.git
cd apollo_ia
composer install
cp .env.example .env   # preencha com suas credenciais
psql -h 127.0.0.1 -U SEU_USUARIO -d apollo_ia_db -f database/schema.sql
```

Sirva o projeto com Nginx + PHP-FPM apontando o `root` para a pasta `public/`, ou, para teste rápido, use o servidor embutido do PHP:

```bash
php -S localhost:8000 -t public
```

Acesse `http://localhost:8000`.

## Variáveis de ambiente (`.env`)

| Variável | Descrição |
|---|---|
| `DB_HOST` | Host do PostgreSQL |
| `DB_PORT` | Porta (padrão `5432`) |
| `DB_DATABASE` | Nome do banco |
| `DB_USERNAME` / `DB_PASSWORD` | Credenciais |
| `DATABASE_URL` | Alternativa às variáveis acima, usada em produção (ex: Render) |
| `GEMINI_API_KEY` | Chave da API Gemini |
| `GEMINI_MODEL` | Modelo usado (ex: `gemini-2.5-flash`) |

## Endpoints da API

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api.php?recurso=perguntas` | Lista todo o histórico |
| `GET` | `/api.php?recurso=perguntas&id={id}` | Busca uma pergunta específica |
| `POST` | `/api.php?recurso=perguntas` | Cria uma pergunta e obtém resposta da IA. Corpo: `{"pergunta": "texto"}` |
| `DELETE` | `/api.php?recurso=perguntas&id={id}` | Remove uma pergunta do histórico |

## Testes

```bash
composer test              # roda tudo
composer test:unit         # só testes unitários
composer test:integration  # só testes de integração (exige banco configurado)
```

## CI/CD

Todo push ou Pull Request na branch `main` dispara automaticamente o workflow `.github/workflows/testes.yml`, que:
1. Sobe um PostgreSQL temporário (container de serviço)
2. Aplica `database/schema.sql`
3. Instala as dependências via Composer
4. Roda `composer test`

O badge no topo deste README reflete o resultado da última execução na `main`.

## Deploy

O projeto é containerizado via `Dockerfile` e publicado na [Render](https://render.com). Todo push na branch `main` dispara automaticamente:
1. Pipeline de testes (GitHub Actions)
2. Build da imagem Docker e deploy (Render)

## Roadmap

- [x] Arquitetura MVC + PostgreSQL + Gemini
- [x] Testes automatizados
- [x] CI/CD
- [x] Deploy em produção
- [ ] Autenticação de usuários (Fase 2)

## Licença

MIT
