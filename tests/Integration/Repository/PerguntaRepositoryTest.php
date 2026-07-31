<?php

/**
 * ============================================================================
 * Testes de INTEGRAÇÃO do PerguntaRepository
 * ============================================================================
 *
 * "Integração" significa: testa contra o BANCO DE DADOS REAL (PostgreSQL),
 * diferente dos testes unitários. Para não deixar dados de teste
 * acumulando na tabela `perguntas`, cada teste roda dentro de uma
 * TRANSAÇÃO que é sempre desfeita (rollback) ao final — veja setUp()
 * e tearDown() abaixo.
 *
 * Não testamos o GeminiService aqui de propósito: chamar uma API externa
 * real dentro de testes automatizados os deixaria lentos, instáveis e
 * potencialmente custosos. Essa parte segue validada manualmente.
 * ============================================================================
 */

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Config\Database;
use App\Model\Pergunta;
use App\Repository\PerguntaRepository;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class PerguntaRepositoryTest extends TestCase
{
    private PerguntaRepository $repository;

    /**
     * Executado antes de CADA teste desta classe (ciclo de vida do PHPUnit).
     */
    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../..');
        $dotenv->load();

        $this->repository = new PerguntaRepository();

        // Inicia uma transação — nada do que os testes fizerem a seguir
        // será realmente persistido no banco.
        Database::getConnection()->beginTransaction();
    }

    /**
     * Executado depois de CADA teste, mesmo se ele falhar (garantia do PHPUnit).
     */
    protected function tearDown(): void
    {
        // Desfaz tudo o que o teste fez, mantendo o banco sempre limpo
        // para o próximo teste (e para você, revisando o projeto depois).
        Database::getConnection()->rollBack();
    }

    /** Confirma que salvar() gera um id positivo e uma data de criação. */
    public function test_salva_pergunta_e_gera_id_automaticamente(): void
    {
        $pergunta = new Pergunta(
            id: null,
            pergunta: 'Pergunta de teste automatizado',
            resposta: null,
            modeloIa: 'gemini-2.5-flash',
        );

        $perguntaSalva = $this->repository->salvar($pergunta);

        $this->assertNotNull($perguntaSalva->getId());
        $this->assertGreaterThan(0, $perguntaSalva->getId());
        $this->assertNotNull($perguntaSalva->getCriadoEm());
    }

    /** Confirma que atualizarResposta() realmente grava o novo texto no banco. */
    public function test_atualiza_resposta_de_pergunta_existente(): void
    {
        $pergunta = new Pergunta(
            id: null,
            pergunta: 'Outra pergunta de teste',
            resposta: null,
            modeloIa: 'gemini-2.5-flash',
        );
        $perguntaSalva = $this->repository->salvar($pergunta);

        $this->repository->atualizarResposta(
            $perguntaSalva->getId(),
            'Resposta gerada no teste'
        );

        $perguntaAtualizada = $this->repository->buscarPorId($perguntaSalva->getId());

        $this->assertSame('Resposta gerada no teste', $perguntaAtualizada->getResposta());
    }

    /** Confirma que buscarPorId() devolve null (não erro) para id inexistente. */
    public function test_retorna_null_ao_buscar_id_inexistente(): void
    {
        $resultado = $this->repository->buscarPorId(999999999);

        $this->assertNull($resultado);
    }

    /** Confirma que deletar() remove de fato o registro do banco. */
    public function test_deleta_pergunta_existente(): void
    {
        $pergunta = new Pergunta(
            id: null,
            pergunta: 'Pergunta a ser deletada',
            resposta: null,
            modeloIa: 'gemini-2.5-flash',
        );
        $perguntaSalva = $this->repository->salvar($pergunta);

        $resultado = $this->repository->deletar($perguntaSalva->getId());

        $this->assertTrue($resultado);
        $this->assertNull($this->repository->buscarPorId($perguntaSalva->getId()));
    }

    /** Confirma que deletar() devolve false quando o id não existe. */
    public function test_deletar_id_inexistente_retorna_false(): void
    {
        $resultado = $this->repository->deletar(999999999);

        $this->assertFalse($resultado);
    }
}