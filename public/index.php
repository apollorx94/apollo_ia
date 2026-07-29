<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $pdo = Database::getConnection();
    echo 'Conexão com o PostgreSQL estabelecida com sucesso!';
} catch (\PDOException $e) {
    http_response_code(500);
    echo 'Erro ao conectar ao banco de dados. Verifique os logs.';
    error_log($e->getMessage());
}
