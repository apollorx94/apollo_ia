<?php

/**
 * ============================================================================
 * GeminiService — integração com a API Gemini (Google AI)
 * ============================================================================
 *
 * Responsável por TODA a comunicação HTTP com a API do Gemini. Nenhuma
 * outra classe do sistema deve montar requisições HTTP para a IA
 * diretamente — assim, se um dia trocarmos de provedor de IA, só esta
 * classe (e o Controller que a chama) precisam mudar.
 *
 * Usa a extensão cURL nativa do PHP (sem dependências externas) para
 * enviar a pergunta e interpretar a resposta.
 *
 * As variáveis de configuração são lidas via App\Config\Env::get() (veja
 * o comentário detalhado em Database.php sobre por que isso é necessário
 * para funcionar tanto local/CI quanto em produção na Render).
 * ============================================================================
 */

declare(strict_types=1);

namespace App\Service;

use App\Config\Env;
use RuntimeException;

class GeminiService
{
    private string $apiKey;
    private string $modelo;

    /** URL base da API. Guardada como propriedade para facilitar manutenção
     *  (ex: se a versão da API mudar de v1beta para v1). */
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = Env::get('GEMINI_API_KEY', '');
        $this->modelo = Env::get('GEMINI_MODEL', 'gemini-2.5-flash');

        // "Fail fast": se a chave não estiver configurada, falha
        // IMEDIATAMENTE com uma mensagem clara, em vez de deixar o erro
        // aparecer confuso mais tarde (ex: um 401 genérico da API).
        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY não configurada no .env');
        }
    }

    /**
     * Envia uma pergunta em texto livre para o Gemini e devolve o texto
     * da resposta gerada.
     *
     * @throws RuntimeException em caso de falha de rede ou erro retornado
     *                          pela própria API (chave inválida, limite
     *                          de uso excedido, formato inesperado, etc.)
     */
    public function perguntar(string $pergunta): string
    {
        $url = "{$this->baseUrl}/{$this->modelo}:generateContent";

        // Formato exigido pela API Gemini: um array de "contents", cada
        // um com "parts" (partes do conteúdo — aqui, só texto).
        $corpoRequisicao = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $pergunta],
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            // Sem isso, curl_exec() IMPRIME a resposta na tela em vez de
            // devolvê-la como string — sempre queremos capturar o retorno.
            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST           => true,

            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                // Forma atual (2026) de autenticar na API Gemini: header
                // dedicado, em vez do antigo parâmetro ?key= na URL.
                'x-goog-api-key: ' . $this->apiKey,
            ],

            // JSON_UNESCAPED_UNICODE evita que acentos (ã, ç, é) virem
            // sequências de escape (\u00e3) no corpo da requisição.
            CURLOPT_POSTFIELDS     => json_encode($corpoRequisicao, JSON_UNESCAPED_UNICODE),

            // Sem timeout, uma falha na API do Gemini travaria a
            // aplicação PHP indefinidamente, esperando resposta.
            CURLOPT_TIMEOUT        => 30,
        ]);

        $respostaBruta = curl_exec($ch);
        $codigoHttp    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl      = curl_error($ch);

        curl_close($ch);

        // curl_exec() retorna `false` em falhas de REDE (sem internet,
        // DNS não resolvido, timeout estourado) — diferente de erros
        // retornados pela própria API.
        if ($respostaBruta === false) {
            throw new RuntimeException("Erro de conexão com a API Gemini: {$erroCurl}");
        }

        $dados = json_decode($respostaBruta, true);

        // Qualquer código HTTP diferente de 200 indica erro DA API (ex:
        // 401 chave inválida, 429 limite de uso excedido, 400 requisição
        // malformada). O operador ?? usa uma mensagem padrão se a API
        // não devolver o campo error.message esperado.
        if ($codigoHttp !== 200) {
            $mensagemErro = $dados['error']['message'] ?? 'Erro desconhecido da API Gemini';
            throw new RuntimeException("Erro da API Gemini (HTTP {$codigoHttp}): {$mensagemErro}");
        }

        // Navega pela estrutura aninhada da resposta:
        // { candidates: [ { content: { parts: [ { text: "..." } ] } } ] }
        // Cada nível usa ?? para evitar erro fatal caso algum não exista.
        $texto = $dados['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($texto === null) {
            throw new RuntimeException('Resposta da API Gemini veio em formato inesperado.');
        }

        return trim($texto);
    }
}
