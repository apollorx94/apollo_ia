<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Pergunta;
use App\Repository\PerguntaRepository;
use App\Service\GeminiService;
use RuntimeException;

/**
 * Orquestra as requisições relacionadas a perguntas:
 * recebe dados da requisição HTTP, chama o Service (IA) e o
 * Repository (banco), e devolve arrays prontos para virar JSON.
 */
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
     * Cria uma nova pergunta: salva, chama a IA, atualiza a resposta.
     * Retorna um array pronto para ser convertido em JSON.
     */
    public function criar(string $textoPergunta): array
    {
        $textoPergunta = trim($textoPergunta);

        if ($textoPergunta === '') {
            throw new RuntimeException('O campo "pergunta" não pode estar vazio.');
        }

        $modeloAtual = $_ENV['GEMINI_MODEL'] ?? 'gemini-2.5-flash';

        // 1. Salva a pergunta primeiro, sem resposta ainda.
        //    Assim, mesmo que a API da IA falhe, não perdemos o registro.
        $pergunta = new Pergunta(
            id: null,
            pergunta: $textoPergunta,
            resposta: null,
            modeloIa: $modeloAtual,
        );
        $perguntaSalva = $this->repository->salvar($pergunta);

        // 2. Chama a IA para gerar a resposta.
        $textoResposta = $this->gemini->perguntar($textoPergunta);

        // 3. Atualiza o registro já salvo com a resposta obtida.
        $this->repository->atualizarResposta($perguntaSalva->getId(), $textoResposta);

        // 4. Busca o registro atualizado para devolver completo.
        $perguntaFinal = $this->repository->buscarPorId($perguntaSalva->getId());

        return $perguntaFinal->toArray();
    }

    /**
     * Retorna todas as perguntas cadastradas.
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
     * Busca uma pergunta específica pelo ID.
     */
    public function buscar(int $id): ?array
    {
        $pergunta = $this->repository->buscarPorId($id);

        return $pergunta?->toArray();
    }

    /**
     * Remove uma pergunta do histórico.
     */
    public function deletar(int $id): bool
    {
        return $this->repository->deletar($id);
    }
}