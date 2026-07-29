<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Pergunta;
use App\Config\Database;
use PDO;

/**
 * Responsável por toda a persistência (SQL) relacionada
 * à entidade Pergunta. Nenhuma outra classe do sistema
 * deve escrever SQL relacionado a esta tabela.
 */
class PerguntaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Insere uma nova pergunta (ainda sem resposta) e retorna
     * o Model já com o ID gerado pelo banco.
     */
    public function salvar(Pergunta $pergunta): Pergunta
    {
        $sql = "INSERT INTO perguntas (pergunta, resposta, modelo_ia)
                VALUES (:pergunta, :resposta, :modelo_ia)
                RETURNING id, criado_em";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'pergunta'  => $pergunta->getPergunta(),
            'resposta'  => $pergunta->getResposta(),
            'modelo_ia' => $pergunta->getModeloIa(),
        ]);

        $linha = $stmt->fetch();

        return new Pergunta(
            id: (int) $linha['id'],
            pergunta: $pergunta->getPergunta(),
            resposta: $pergunta->getResposta(),
            modeloIa: $pergunta->getModeloIa(),
            criadoEm: $linha['criado_em'],
        );
    }

    /**
     * Atualiza a resposta de uma pergunta já existente,
     * identificada pelo seu ID.
     */
    public function atualizarResposta(int $id, string $resposta): void
    {
        $sql = "UPDATE perguntas SET resposta = :resposta WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'resposta' => $resposta,
            'id'       => $id,
        ]);
    }

    /**
     * Retorna todas as perguntas cadastradas, da mais
     * recente para a mais antiga.
     *
     * @return Pergunta[]
     */
    public function listarTodas(): array
    {
        $sql = "SELECT id, pergunta, resposta, modelo_ia, criado_em
                FROM perguntas
                ORDER BY criado_em DESC";

        $stmt = $this->pdo->query($sql);
        $linhas = $stmt->fetchAll();

        return array_map(
            fn (array $linha) => new Pergunta(
                id: (int) $linha['id'],
                pergunta: $linha['pergunta'],
                resposta: $linha['resposta'],
                modeloIa: $linha['modelo_ia'],
                criadoEm: $linha['criado_em'],
            ),
            $linhas
        );
    }

    /**
     * Busca uma única pergunta pelo ID. Retorna null se não encontrar.
     */
    public function buscarPorId(int $id): ?Pergunta
    {
        $sql = "SELECT id, pergunta, resposta, modelo_ia, criado_em
                FROM perguntas
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        if ($linha === false) {
            return null;
        }

        return new Pergunta(
            id: (int) $linha['id'],
            pergunta: $linha['pergunta'],
            resposta: $linha['resposta'],
            modeloIa: $linha['modelo_ia'],
            criadoEm: $linha['criado_em'],
        );
    }
}
