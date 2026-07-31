<?php

/**
 * ============================================================================
 * PerguntaController — orquestração das requisições
 * ============================================================================
 *
 * O Controller é o "gerente": ele não sabe SQL (isso é do Repository) nem
 * sabe montar requisições HTTP para a IA (isso é do Service). Ele apenas
 * ORQUESTRA essas duas peças na ordem certa, aplica validações simples de
 * entrada, e devolve arrays prontos para virar JSON na API.
 * ============================================================================
 */

declare(strict_types=1);

namespace App\Controller;

use App\Model\Pergunta;
use App\Repository\PerguntaRepository;
use App\Service\GeminiService;
use RuntimeException;

class PerguntaController
{
    private PerguntaRepository $repository;
    private GeminiService $gemini;

    public function __construct()
    {
        $this->repository = new PerguntaRepository();
        $this->gemini     = new GeminiService();
    }

    /**
     * Cria uma nova pergunta de ponta a ponta:
     *   1. valida o texto recebido
     *   2. salva a pergunta no banco (AINDA sem resposta)
     *   3. consulta a IA
     *   4. atualiza o registro com a resposta obtida
     *   5. devolve o registro completo, já pronto para virar JSON
     *
     * @throws RuntimeException se o texto vier vazio, ou se a chamada à
     *                          IA falhar (erro repassado pelo GeminiService)
     */
    public function criar(string $textoPergunta): array
    {
        $textoPergunta = trim($textoPergunta);

        // Validação de entrada: nunca confiamos em dados vindos direto
        // do usuário/frontend sem checar antes.
        if ($textoPergunta === '') {
            throw new RuntimeException('O campo "pergunta" não pode estar vazio.');
        }

        // O modelo de IA em uso vem do .env no momento da criação —
        // por isso cada pergunta grava o modelo que REALMENTE a
        // respondeu, mesmo que o .env seja trocado no futuro.
        $modeloAtual = $_ENV['GEMINI_MODEL'] ?? 'gemini-2.5-flash';

        // 1) Salva a pergunta ANTES de chamar a IA. Assim, se a chamada
        //    à IA falhar (rede caiu, chave inválida...), a pergunta não
        //    se perde — fica salva no banco sem resposta, podendo ser
        //    reprocessada depois.
        $pergunta = new Pergunta(
            id: null,
            pergunta: $textoPergunta,
            resposta: null,
            modeloIa: $modeloAtual,
        );
        $perguntaSalva = $this->repository->salvar($pergunta);

        // 2) Consulta a IA — pode lançar RuntimeException, que sobe
        //    naturalmente para quem chamou este método (o roteador da
        //    API, que a transforma em uma resposta HTTP 400).
        $textoResposta = $this->gemini->perguntar($textoPergunta);

        // 3) Atualiza o registro já salvo com a resposta obtida.
        $this->repository->atualizarResposta($perguntaSalva->getId(), $textoResposta);

        // 4) Busca o registro atualizado para devolver o estado final
        //    completo (com a resposta já preenchida).
        $perguntaFinal = $this->repository->buscarPorId($perguntaSalva->getId());

        return $perguntaFinal->toArray();
    }

    /**
     * Lista todas as perguntas já cadastradas, convertidas para arrays
     * (prontos para json_encode).
     */
    public function listar(): array
    {
        $perguntas = $this->repository->listarTodas();

        return array_map(
            fn (Pergunta $p) => $p->toArray(),
            $perguntas
        );
    }

    /**
     * Busca uma pergunta específica pelo id.
     *
     * @return array|null `null` se não existir (o roteador da API
     *                      transforma isso em uma resposta 404)
     */
    public function buscar(int $id): ?array
    {
        $pergunta = $this->repository->buscarPorId($id);

        // Nullsafe operator (?->) do PHP 8: se $pergunta for null, a
        // expressão inteira já retorna null, sem lançar erro — evita
        // um `if ($pergunta !== null) { ... }` explícito.
        return $pergunta?->toArray();
    }

    /**
     * Remove uma pergunta do histórico.
     *
     * @return bool `true` se removida com sucesso, `false` se o id não existia
     */
    public function deletar(int $id): bool
    {
        return $this->repository->deletar($id);
    }
}
