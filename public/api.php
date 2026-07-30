<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Controller\PerguntaController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json; charset=utf-8');

// Permite que o JavaScript do frontend (mesma origem, por enquanto) consuma esta API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$metodo  = $_SERVER['REQUEST_METHOD'];
$recurso = $_GET['recurso'] ?? null;

try {
    $controller = new PerguntaController();

    // Endpoint 1: GET /api.php?recurso=perguntas → lista o histórico
    if ($metodo === 'GET' && $recurso === 'perguntas' && !isset($_GET['id'])) {
        echo json_encode($controller->listar(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Endpoint 2 (bônus): GET /api.php?recurso=perguntas&id=1 → busca uma específica
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

    // Endpoint 3: POST /api.php?recurso=perguntas → cria uma nova pergunta
    if ($metodo === 'POST' && $recurso === 'perguntas') {
        $corpo = json_decode(file_get_contents('php://input'), true);
        $textoPergunta = $corpo['pergunta'] ?? '';

        $resultado = $controller->criar($textoPergunta);

        http_response_code(201);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Nenhuma rota encontrada
    http_response_code(404);
    echo json_encode(['erro' => 'Rota não encontrada.'], JSON_UNESCAPED_UNICODE);

    // Endpoint: DELETE /api.php?recurso=perguntas&id=1 → remove uma pergunta
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

} catch (\RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor.'], JSON_UNESCAPED_UNICODE);
    error_log($e->getMessage());
}