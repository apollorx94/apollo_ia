<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Model\Pergunta;
use PHPUnit\Framework\TestCase;

class PerguntaTest extends TestCase
{
    public function test_cria_pergunta_com_dados_validos(): void
    {
        $pergunta = new Pergunta(
            id: 1,
            pergunta: 'O que é PostgreSQL?',
            resposta: null,
            modeloIa: 'gemini-2.5-flash',
        );

        $this->assertSame(1, $pergunta->getId());
        $this->assertSame('O que é PostgreSQL?', $pergunta->getPergunta());
        $this->assertNull($pergunta->getResposta());
        $this->assertSame('gemini-2.5-flash', $pergunta->getModeloIa());
    }

    public function test_atualiza_resposta_corretamente(): void
    {
        $pergunta = new Pergunta(
            id: 1,
            pergunta: 'O que é PostgreSQL?',
            resposta: null,
            modeloIa: 'gemini-2.5-flash',
        );

        $pergunta->setResposta('PostgreSQL é um banco de dados relacional open source.');

        $this->assertSame(
            'PostgreSQL é um banco de dados relacional open source.',
            $pergunta->getResposta()
        );
    }

    public function test_converte_para_array_corretamente(): void
    {
        $pergunta = new Pergunta(
            id: 5,
            pergunta: 'O que é Docker?',
            resposta: 'Uma ferramenta de containerização.',
            modeloIa: 'gemini-2.5-flash',
            criadoEm: '2026-07-29 10:00:00',
        );

        $esperado = [
            'id'        => 5,
            'pergunta'  => 'O que é Docker?',
            'resposta'  => 'Uma ferramenta de containerização.',
            'modelo_ia' => 'gemini-2.5-flash',
            'criado_em' => '2026-07-29 10:00:00',
        ];

        $this->assertSame($esperado, $pergunta->toArray());
    }
}
