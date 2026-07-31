<?php

/**
 * ============================================================================
 * index.php — Front Controller da PÁGINA (não da API)
 * ============================================================================
 *
 * Este é o arquivo que o navegador carrega quando o usuário acessa a URL
 * raiz do projeto. Sua única responsabilidade é carregar o autoload do
 * Composer e entregar o HTML da aplicação (views/home.php).
 *
 * Repare como este arquivo é enxuto: toda a lógica de dados (banco, IA)
 * vive em public/api.php — o JavaScript de home.php é quem busca os
 * dados dinamicamente depois, via fetch().
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../views/home.php';
