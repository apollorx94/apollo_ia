<?php

/**
 * ============================================================================
 * Env — leitura robusta de variáveis de ambiente
 * ============================================================================
 *
 * POR QUE ESTA CLASSE EXISTE:
 *
 * Em desenvolvimento local e no CI (GitHub Actions), um arquivo .env real
 * existe em disco. A biblioteca phpdotenv lê esse arquivo e popula o
 * superglobal $_ENV com cada variável.
 *
 * Em produção (Render), NÃO existe arquivo .env — as variáveis são
 * configuradas direto na plataforma e injetadas como variáveis de
 * ambiente REAIS do sistema operacional. Dependendo da configuração do
 * PHP (diretiva `variables_order` do php.ini), o superglobal $_ENV pode
 * não ser populado automaticamente nesse cenário — mas a função nativa
 * `getenv()` SEMPRE funciona, independente dessa configuração, porque
 * ela consulta o sistema operacional diretamente.
 *
 * Por isso, Env::get() tenta $_ENV primeiro (mais rápido, cobre o caso
 * local/CI) e cai para getenv() como reforço (cobre produção).
 * ============================================================================
 */

declare(strict_types=1);

namespace App\Config;

class Env
{
    /**
     * Lê uma variável de ambiente, tentando $_ENV e depois getenv().
     *
     * @param string      $key     nome da variável (ex: "DATABASE_URL")
     * @param string|null $default valor devolvido se a variável não existir
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        $valor = getenv($key);

        if ($valor !== false && $valor !== '') {
            return $valor;
        }

        return $default;
    }
}
