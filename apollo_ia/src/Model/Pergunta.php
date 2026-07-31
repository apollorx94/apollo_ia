<?php

/**
 * ============================================================================
 * Pergunta — Model (entidade de domínio)
 * ============================================================================
 *
 * Representa UMA pergunta feita por um usuário, junto com a resposta que a
 * IA gerou (se já tiver sido gerada). Esta classe é "burra" de propósito:
 * ela NÃO sabe nada sobre banco de dados, HTTP ou a API do Gemini — apenas
 * guarda os dados e oferece formas seguras de ler/alterar esses dados.
 *
 * Quem sabe salvar/buscar isso no banco é a classe PerguntaRepository.
 * ============================================================================
 */

declare(strict_types=1);

namespace App\Model;

class Pergunta
{
    /**
     * Constructor Property Promotion (recurso do PHP 8): declara e
     * inicializa as propriedades diretamente na assinatura do construtor,
     * sem precisar repetir `private $x; $this->x = $x;` para cada campo.
     *
     * @param int|null    $id        Identificador no banco. É `null` antes
     *                                de a pergunta ser salva (o banco quem
     *                                gera o ID, via SERIAL).
     * @param string      $pergunta  Texto da pergunta feita pelo usuário.
     * @param string|null $resposta  Texto da resposta da IA. É `null`
     *                                enquanto a IA ainda não respondeu.
     * @param string      $modeloIa  Nome do modelo de IA usado (ex:
     *                                "gemini-2.5-flash"). Guardado por
     *                                registro para dar rastreabilidade.
     * @param string|null $criadoEm  Data/hora de criação, gerada pelo banco
     *                                (coluna criado_em, DEFAULT NOW()).
     */
    public function __construct(
        private ?int $id,
        private string $pergunta,
        private ?string $resposta,
        private string $modeloIa,
        private ?string $criadoEm = null,
    ) {
    }

    // ------------------------------------------------------------------
    // Getters: como as propriedades acima são `private`, nenhum código
    // fora desta classe pode ler `$pergunta->id` diretamente — precisa
    // passar por estes métodos. Isso é ENCAPSULAMENTO: a classe controla
    // como seus dados são expostos.
    // ------------------------------------------------------------------

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

    /**
     * Único "setter" da classe — faz sentido existir porque a resposta é
     * o único campo que muda DEPOIS de a pergunta já ter sido criada
     * (ela chega depois, quando a IA responde). Os demais campos são
     * imutáveis após a criação do objeto.
     */
    public function setResposta(string $resposta): void
    {
        $this->resposta = $resposta;
    }

    /**
     * Converte o objeto em um array associativo simples. Usado sempre que
     * precisamos transformar a Pergunta em JSON para devolver na API
     * (json_encode trabalha bem com arrays, não com objetos customizados).
     *
     * @return array{id: ?int, pergunta: string, resposta: ?string, modelo_ia: string, criado_em: ?string}
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
