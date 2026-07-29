<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Representa uma pergunta feita pelo usuário e sua respectiva
 * resposta gerada pela IA. Não contém nenhuma lógica de banco de dados.
 */
class Pergunta
{
    public function __construct(
        private ?int $id,
        private string $pergunta,
        private ?string $resposta,
        private string $modeloIa,
        private ?string $criadoEm = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPergunta(): string
    {
        return $this->pergunta;
    }

    public function getResposta(): ?string
    {
        return $this->resposta;
    }

    public function getModeloIa(): string
    {
        return $this->modeloIa;
    }

    public function getCriadoEm(): ?string
    {
        return $this->criadoEm;
    }

    public function setResposta(string $resposta): void
    {
        $this->resposta = $resposta;
    }

    /**
     * Converte o objeto em array associativo, útil para
     * serializar em JSON nas respostas da API.
     */
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'pergunta'  => $this->pergunta,
            'resposta'  => $this->resposta,
            'modelo_ia' => $this->modeloIa,
            'criado_em' => $this->criadoEm,
        ];
    }
}
