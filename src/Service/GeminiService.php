<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * Responsável por toda a comunicação com a API Gemini (Google).
 * Nenhuma outra classe do sistema deve montar requisições HTTP
 * para a IA diretamente.
 */
class GeminiService
{
    private string $apiKey;
    private string $modelo;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        $this->modelo = $_ENV['GEMINI_MODEL'] ?? 'gemini-2.5-flash';

        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY não configurada no .env');
        }
    }

    /**
     * Envia uma pergunta para o Gemini e retorna o texto da resposta.
     */
    public function perguntar(string $pergunta): string
    {
        $url = "{$this->baseUrl}/{$this->modelo}:generateContent";

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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($corpoRequisicao, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 30,
        ]);

        $respostaBruta = curl_exec($ch);
        $codigoHttp    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl      = curl_error($ch);

        curl_close($ch);

        if ($respostaBruta === false) {
            throw new RuntimeException("Erro de conexão com a API Gemini: {$erroCurl}");
        }

        $dados = json_decode($respostaBruta, true);

        if ($codigoHttp !== 200) {
            $mensagemErro = $dados['error']['message'] ?? 'Erro desconhecido da API Gemini';
            throw new RuntimeException("Erro da API Gemini (HTTP {$codigoHttp}): {$mensagemErro}");
        }

        $texto = $dados['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($texto === null) {
            throw new RuntimeException('Resposta da API Gemini veio em formato inesperado.');
        }

        return trim($texto);
    }
}
