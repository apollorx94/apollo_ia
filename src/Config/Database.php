<?php

/**
 * ============================================================================
 * Database — conexão única (Singleton) com o PostgreSQL via PDO
 * ============================================================================
 *
 * PDO (PHP Data Objects) é a camada nativa do PHP para acesso a bancos de
 * dados. Ela permite usar prepared statements (proteção contra SQL
 * Injection) e funciona com vários bancos diferentes através do mesmo
 * conjunto de métodos.
 *
 * Por que Singleton? Para garantir que, durante toda a execução de uma
 * mesma requisição HTTP, exista APENAS UMA conexão aberta com o banco,
 * evitando desperdício de recursos ao criar conexões repetidas.
 * ============================================================================
 */

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    /**
     * Guarda a única instância de PDO criada nesta execução.
     * `static` = compartilhada entre todas as chamadas da classe.
     * `?PDO`   = pode ser PDO ou null (começa null, antes de conectar).
     */
    private static ?PDO $instance = null;

    /**
     * Retorna a conexão PDO ativa. Se ainda não existir, cria uma nova
     * (na primeira chamada); nas chamadas seguintes, devolve a mesma
     * conexão já aberta.
     *
     * Suporta dois formatos de configuração:
     *  - DATABASE_URL: usado por serviços gerenciados em produção (Render).
     *    Nesse caso, SSL é OBRIGATÓRIO (sslmode=require).
     *  - DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD:
     *    usado em desenvolvimento local e no CI (GitHub Actions). Nesse
     *    caso, SSL é OPCIONAL (sslmode=prefer), porque o Postgres local
     *    do WSL e o container de teste do CI não têm SSL configurado —
     *    exigir SSL nesses ambientes faz a conexão falhar.
     *
     * @return PDO a conexão ativa com o banco
     * @throws PDOException se a conexão falhar (credenciais erradas,
     *                       banco fora do ar, etc.)
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

            if ($databaseUrl !== null) {
                // Formato de produção: postgres://usuario:senha@host:porta/banco
                // parse_url() decompõe a URL nas partes que precisamos.
                $partes = parse_url($databaseUrl);
                $host     = $partes['host'];
                $port     = $partes['port'] ?? 5432;
                $database = ltrim($partes['path'], '/'); // remove a barra inicial do path
                $username = $partes['user'];
                $password = $partes['pass'];

                // Bancos gerenciados na nuvem (Render, etc.) exigem
                // conexão criptografada.
                $sslmode = 'require';
            } else {
                // Formato de desenvolvimento local / CI: variáveis separadas no .env
                $host     = $_ENV['DB_HOST'];
                $port     = $_ENV['DB_PORT'];
                $database = $_ENV['DB_DATABASE'];
                $username = $_ENV['DB_USERNAME'];
                $password = $_ENV['DB_PASSWORD'];

                // 'prefer' = usa SSL se o servidor oferecer, mas não
                // recusa a conexão se ele não suportar. Evita o erro
                // "server does not support SSL, but SSL was required"
                // no Postgres local e no container de teste do CI.
                $sslmode = 'prefer';
            }

            // DSN (Data Source Name): string que diz ao PDO qual driver usar
            // (pgsql) e como se conectar.
            $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";

            try {
                self::$instance = new PDO($dsn, $username, $password, [
                    // Sem isso, erros de SQL falham SILENCIOSAMENTE (retornam
                    // `false`). Com isso, qualquer erro vira uma exceção que
                    // conseguimos capturar e tratar corretamente.
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                    // Faz com que todo resultado de SELECT venha como array
                    // associativo (['id' => 1, 'pergunta' => '...']), em vez
                    // do formato misto padrão do PDO.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                // Relança com uma mensagem mais descritiva, mantendo o
                // código de erro original para quem for depurar depois.
                throw new PDOException(
                    'Falha ao conectar ao banco de dados: ' . $e->getMessage(),
                    (int) $e->getCode()
                );
            }
        }

        return self::$instance;
    }
}