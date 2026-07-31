# Decisões Técnicas — Apollo IA

## Por que essa stack

**PHP 8** foi escolhido por ser a linguagem de foco da disciplina e por ainda ser amplamente usada em produção no mercado brasileiro. A versão 8 trouxe recursos que usamos bastante no projeto — tipagem estrita (`declare(strict_types=1)`), constructor property promotion, named arguments e o nullsafe operator (`?->`) — que tornam o código mais seguro e legível do que seria em PHP 5/7.

**PostgreSQL** foi escolhido em vez de MySQL por ser open source, robusto e por oferecer recursos que usamos diretamente, como o `RETURNING` em `INSERT` (evita uma segunda consulta para obter o ID gerado) e suporte nativo a `COMMENT ON` para documentar colunas dentro do próprio banco.

**Arquitetura MVC própria, sem framework**, foi uma escolha didática: usar Laravel ou Symfony esconderia o funcionamento interno (roteamento, autoload, injeção de dependência) atrás de "mágica" pronta. Construindo a estrutura manualmente — Model, View, Controller, e as camadas extras Repository e Service — entendemos o *porquê* de cada peça existir antes de um dia usar um framework completo.

**Bootstrap 5 + JavaScript puro** (sem React/Vue) foi escolhido para manter o escopo do frontend simples e focado no backend, que era o objetivo central da disciplina. `fetch()` nativo já é suficiente para consumir a API REST sem a complexidade de um bundler.

**Google Gemini** foi a IA generativa definida pelo escopo do projeto. Integramos via cURL nativo (sem SDK) para entender o funcionamento real de uma chamada HTTP autenticada, em vez de depender de uma biblioteca que abstrai tudo.

**Docker + Render** foram escolhidos para o deploy porque a Render não oferece runtime nativo de PHP — isso nos forçou a aprender Docker do zero (Dockerfile, camadas, multi-stage build), uma habilidade de mercado que não estava no escopo original, mas que se mostrou necessária e valiosa.

**GitHub Actions** foi usado para CI por já estar integrado nativamente ao GitHub, sem precisar configurar uma conta em outro serviço externo.

## O que aprendemos

Além da parte técnica de cada tecnologia, os principais aprendizados foram: a importância de nunca versionar segredos (`.env`), como Prepared Statements previnem SQL Injection na prática, por que separar camadas (Model/Repository/Service/Controller) facilita testes e manutenção, como testes de integração podem usar transação + rollback para não sujar o banco, e como um pipeline de CI pega erros antes que cheguem à branch principal. Também aprendemos, na prática, que revisar código com atenção é essencial — durante a revisão final do projeto encontramos um bug real (endpoint `DELETE` posicionado depois do fallback de erro 404, tornando-o inacessível), o que reforçou a importância de testes automatizados cobrindo cada rota da API, não só a camada de Repository.
