<?php

/**
 * ============================================================================
 * PerguntaRepository — camada de acesso a dados (padrão Repository)
 * ============================================================================
 *
 * TODO o SQL relacionado à tabela `perguntas` vive exclusivamente aqui.
 * Nenhuma outra classe do sistema (Controller, Service, View) deve montar
 * queries diretamente — assim, se um dia trocarmos de banco de dados, só
 * esta classe precisa mudar.
 *
 * Todas as queries usam PREPARED STATEMENTS (:pergunta, :id, etc.) — o
 * banco recebe a estrutura da query e os dados separadamente, o que
 * previne ataques de SQL Injection.
 * ============================================================================
 */

declare(strict_types=1);

namespace App\Repository;

use App\Model\Pergunta;
use App\Config\Database;
use PDO;

class PerguntaRepository
{
    private PDO $pdo;

    public function __construct()
    {
        // Reaproveita a conexão única (Singleton) definida em Database.
        $this->pdo = Database::getConnection();
    }

    /**
     * Insere uma nova pergunta no banco (geralmente ainda sem resposta)
     * e devolve um novo objeto Pergunta já com o `id` e `criado_em`
     * gerados pelo próprio PostgreSQL.
     */
    public function salvar(Pergunta $pergunta): Pergunta
    {
        // RETURNING é um recurso do PostgreSQL: devolve, no mesmo INSERT,
        // valores gerados automaticamente pelo banco (id via SERIAL,
        // criado_em via DEFAULT NOW()) — sem precisar de um segundo SELECT.
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

        // Monta um NOVO objeto Pergunta (imutável), agora com o id/data
        // reais que vieram do banco.
        return new Pergunta(
            id: (int) $linha['id'],
            pergunta: $pergunta->getPergunta(),
            resposta: $pergunta->getResposta(),
            modeloIa: $pergunta->getModeloIa(),
            criadoEm: $linha['criado_em'],
        );
    }

    /**
     * Atualiza apenas o campo `resposta` de uma pergunta já existente,
     * identificada pelo seu `id`. Usado depois que a IA já respondeu.
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
     * Retorna TODAS as perguntas cadastradas, da mais recente para a
     * mais antiga (ORDER BY criado_em DESC).
     *
     * @return Pergunta[] lista de objetos Pergunta
     */
    public function listarTodas(): array
    {
        $sql = "SELECT id, pergunta, resposta, modelo_ia, criado_em
                FROM perguntas
                ORDER BY criado_em DESC";

        $stmt = $this->pdo->query($sql);
        $linhas = $stmt->fetchAll();

        // array_map() aplica a função abaixo a CADA linha vinda do banco,
        // transformando cada array associativo em um objeto Pergunta.
        // `fn (...) => ...` é uma arrow function (PHP 7.4+), uma forma
        // compacta de função anônima.
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
     * Busca uma única pergunta pelo `id`. Retorna `null` (em vez de
     * lançar erro) quando não encontra nada — assim quem chamar decide
     * como tratar o "não encontrado" (ex: devolver 404 na API).
     */
    public function buscarPorId(int $id): ?Pergunta
    {
        $sql = "SELECT id, pergunta, resposta, modelo_ia, criado_em
                FROM perguntas
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        // fetch() retorna `false` (não `null`) quando não encontra nada.
        // Convertendo para `null` aqui, deixamos o restante do código
        // mais idiomático em PHP moderno.
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

    /**
     * Remove uma pergunta do banco pelo `id`.
     *
     * @return bool `true` se alguma linha foi de fato removida, `false`
     *              se o `id` não existia (nada foi afetado).
     */
    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM perguntas WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // rowCount() diz quantas linhas foram afetadas pelo último comando.
        return $stmt->rowCount() > 0;
    }
}
