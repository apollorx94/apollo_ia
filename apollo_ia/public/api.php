<?php

/**
 * ============================================================================
 * ROTEADOR DA API (Front Controller da API REST)
 * ============================================================================
 *
 * Este arquivo é o ÚNICO ponto de entrada para todas as chamadas de API
 * do sistema. Ele funciona como um "roteador manual": olha o método HTTP
 * (GET, POST, DELETE...) e o parâmetro `recurso` da URL, e decide qual
 * método do Controller deve ser chamado.
 *
 * Endpoints disponíveis:
 *   GET    /api.php?recurso=perguntas            -> lista o histórico
 *   GET    /api.php?recurso=perguntas&id=1        -> busca uma pergunta específica
 *   POST   /api.php?recurso=perguntas             -> cria uma pergunta (chama a IA)
 *   DELETE /api.php?recurso=perguntas&id=1        -> remove uma pergunta
 *
 * IMPORTANTE: a ordem dos blocos `if` importa! Cada rota que gera uma
 * resposta termina com `exit;` para impedir que a execução continue e
 * "caia" acidentalmente no bloco de fallback (404) logo abaixo.
 * ============================================================================
 */

declare(strict_types=1);

// Carrega o autoloader do Composer — a partir daqui, qualquer `use App\...`
// ou `use Dotenv\...` é resolvido automaticamente (sem `require` manual).
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Controller\PerguntaController;

// Lê o arquivo .env (na raiz do projeto, um nível acima de public/) e
// popula a superglobal $_ENV com DB_HOST, GEMINI_API_KEY, etc.
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Toda resposta desta API é JSON, nunca HTML.
header('Content-Type: application/json; charset=utf-8');

// --- CORS (Cross-Origin Resource Sharing) ---
// Permite que JavaScript de outra origem (domínio/porta diferente) possa
// chamar esta API. Hoje frontend e backend estão na mesma origem, mas
// deixamos configurado pensando em cenários futuros (ex: frontend
// hospedado separadamente). Os métodos listados aqui devem SEMPRE
// incluir todos os métodos HTTP que a API realmente usa.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Método HTTP da requisição atual (GET, POST, DELETE...)
$metodo  = $_SERVER['REQUEST_METHOD'];
// Qual "recurso" o cliente quer acessar (vem de ?recurso=perguntas na URL)
$recurso = $_GET['recurso'] ?? null;

try {
    // O Controller já instancia, internamente, o Repository (banco) e o
    // Service (IA) de que precisa — ver PerguntaController::__construct().
    $controller = new PerguntaController();

    // ------------------------------------------------------------------
    // Endpoint 1: GET /api.php?recurso=perguntas
    // Lista todo o histórico de perguntas (sem filtrar por ID).
    // ------------------------------------------------------------------
    if ($metodo === 'GET' && $recurso === 'perguntas' && !isset($_GET['id'])) {
        echo json_encode($controller->listar(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------------
    // Endpoint 2: GET /api.php?recurso=perguntas&id=1
    // Busca uma única pergunta pelo ID.
    // ------------------------------------------------------------------
    if ($metodo === 'GET' && $recurso === 'perguntas' && isset($_GET['id'])) {
        $resultado = $controller->buscar((int) $_GET['id']);

        if ($resultado === null) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pergunta não encontrada.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------------
    // Endpoint 3: POST /api.php?recurso=perguntas
    // Cria uma nova pergunta: salva no banco, consulta a IA, atualiza
    // a resposta e devolve o registro completo.
    // ------------------------------------------------------------------
    if ($metodo === 'POST' && $recurso === 'perguntas') {
        // Requisições POST com corpo em JSON (não formulário tradicional)
        // não populam $_POST automaticamente. Por isso lemos o corpo bruto
        // da requisição através do stream especial "php://input".
        $corpo = json_decode(file_get_contents('php://input'), true);
        $textoPergunta = $corpo['pergunta'] ?? '';

        $resultado = $controller->criar($textoPergunta);

        // 201 Created: convenção HTTP para "um novo recurso foi criado com sucesso"
        http_response_code(201);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------------
    // Endpoint 4: DELETE /api.php?recurso=perguntas&id=1
    // Remove uma pergunta do histórico.
    //
    // ATENÇÃO: este bloco precisa vir ANTES do fallback de 404 abaixo.
    // Na versão anterior deste arquivo, ele estava depois do fallback,
    // o que o tornava código morto — nunca era executado, porque a
    // resposta 404 já era enviada primeiro. Corrigido nesta revisão.
    // ------------------------------------------------------------------
    if ($metodo === 'DELETE' && $recurso === 'perguntas' && isset($_GET['id'])) {
        $removido = $controller->deletar((int) $_GET['id']);

        if (!$removido) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pergunta não encontrada.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(200);
        echo json_encode(['mensagem' => 'Pergunta removida com sucesso.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------------
    // Fallback: nenhuma das rotas acima combinou com a requisição atual.
    // Precisa ser o ÚLTIMO bloco, depois de todas as rotas reais.
    // ------------------------------------------------------------------
    http_response_code(404);
    echo json_encode(['erro' => 'Rota não encontrada.'], JSON_UNESCAPED_UNICODE);

} catch (\RuntimeException $e) {
    // Exceções "esperadas": validação de entrada (ex: pergunta vazia) ou
    // erro conhecido da API Gemini (ex: chave inválida). Devolvemos a
    // mensagem real, pois ela não expõe detalhes sensíveis do sistema.
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    // Qualquer outro erro NÃO PREVISTO (ex: falha de conexão com o banco).
    // Nunca expomos a mensagem técnica real ao usuário final — só
    // registramos no log do servidor, para o desenvolvedor investigar.
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor.'], JSON_UNESCAPED_UNICODE);
    error_log($e->getMessage());
}
