<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

/**
 * Classe responsável por fornecer uma única conexão PDO
 * com o PostgreSQL durante o ciclo de vida da requisição.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Retorna a conexão PDO existente, ou cria uma nova
     * caso ainda não exista (padrão Singleton).
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = $_ENV['DB_HOST'];
            $port     = $_ENV['DB_PORT'];
            $database = $_ENV['DB_DATABASE'];
            $username = $_ENV['DB_USERNAME'];
            $password = $_ENV['DB_PASSWORD'];

            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

            try {
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                throw new PDOException(
                    'Falha ao conectar ao banco de dados: ' . $e->getMessage(),
                    (int) $e->getCode()
                );
            }
        }

        return self::$instance;
    }
}
