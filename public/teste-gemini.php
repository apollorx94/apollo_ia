<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Service\GeminiService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

try {
    $gemini = new GeminiService();
    $resposta = $gemini->perguntar('Explique o que é PHP em uma frase curta.');

    echo "Resposta da IA:\n{$resposta}\n";
} catch (\RuntimeException $e) {
    echo "Erro: {$e->getMessage()}\n";
}
