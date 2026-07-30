<?php

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
     * Executado antes de CADA teste desta classe.
     */
    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../..');
        $dotenv->load();

        $this->repository = new PerguntaRepository();

        // Inicia uma transação — nada será realmente gravado no banco.
        Database::getConnection()->beginTransaction();
    }

    /**
     * Executado depois de CADA teste, mesmo se ele falhar.
     */
    protected function tearDown(): void
    {
        // Desfaz tudo o que o teste fez, mantendo o banco limpo.
        Database::getConnection()->rollBack();
    }

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

    public function test_retorna_null_ao_buscar_id_inexistente(): void
    {
        $resultado = $this->repository->buscarPorId(999999999);

        $this->assertNull($resultado);
    }

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

    public function test_deletar_id_inexistente_retorna_false(): void
    {
        $resultado = $this->repository->deletar(999999999);

        $this->assertFalse($resultado);
    }
}