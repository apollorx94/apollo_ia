/**
 * ============================================================================
 * app.js — Frontend da aplicação Apollo IA
 * ============================================================================
 *
 * Responsável por:
 *   - buscar o histórico de perguntas na API (GET) e desenhar na tela
 *   - enviar uma nova pergunta para a API (POST) e atualizar a lista
 *   - dar feedback visual ao usuário (spinner de carregamento, erros)
 *
 * Usa a API nativa `fetch()` do navegador para conversar com public/api.php.
 * ============================================================================
 */

// Referências aos elementos HTML que vamos manipular (definidos em home.php)
const formPergunta   = document.getElementById('form-pergunta');
const campoPergunta  = document.getElementById('campo-pergunta');
const btnEnviar      = document.getElementById('btn-enviar');
const carregando     = document.getElementById('carregando');
const alertaErro     = document.getElementById('alerta-erro');
const listaHistorico = document.getElementById('lista-historico');

/**
 * Converte o formato de data do PostgreSQL ("2026-07-29 14:02:53") em um
 * formato legível no padrão brasileiro ("29/07/2026 14:02").
 */
function formatarData(dataBruta) {
    // O PostgreSQL usa espaço entre data e hora; o construtor Date() do
    // JS exige "T" nesse lugar para reconhecer como data/hora ISO válida.
    const data = new Date(dataBruta.replace(' ', 'T'));
    return data.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

/**
 * Previne XSS (Cross-Site Scripting): se a pergunta ou resposta contiver
 * algo como "<script>...</script>", inserir isso direto no HTML executaria
 * o script. Usando `textContent` (trata tudo como texto puro) e lendo de
 * volta via `innerHTML`, os caracteres perigosos (<, >) são convertidos
 * automaticamente para suas versões seguras (&lt;, &gt;).
 */
function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

/**
 * Recebe a lista de perguntas (já em JSON) e desenha os cartões na tela.
 */
function renderizarHistorico(perguntas) {
    if (perguntas.length === 0) {
        listaHistorico.innerHTML = '<p class="text-muted">Nenhuma pergunta ainda.</p>';
        return;
    }

    // .map() transforma cada pergunta em um bloco de HTML (template
    // literal), e .join('') junta tudo em uma única string final.
    listaHistorico.innerHTML = perguntas.map((item) => `
        <div class="card mb-3">
            <div class="card-body">
                <p class="fw-bold mb-1">${escapeHtml(item.pergunta)}</p>
                <p class="mb-2">${escapeHtml(item.resposta ?? 'Aguardando resposta...')}</p>
                <small class="text-muted">
                    ${escapeHtml(item.modelo_ia)} · ${formatarData(item.criado_em)}
                </small>
            </div>
        </div>
    `).join('');
}

/**
 * Busca o histórico atualizado na API (GET) e manda renderizar na tela.
 * Chamada tanto no carregamento inicial da página quanto após cada
 * pergunta enviada com sucesso.
 */
async function carregarHistorico() {
    const resposta = await fetch('api.php?recurso=perguntas');
    const dados = await resposta.json();
    renderizarHistorico(dados);
}

/**
 * Envia uma nova pergunta para a API (POST) e devolve o registro criado.
 * Lança um erro (Error) se a API responder com um status diferente de 2xx.
 */
async function enviarPergunta(textoPergunta) {
    const resposta = await fetch('api.php?recurso=perguntas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pergunta: textoPergunta }),
    });

    const dados = await resposta.json();

    // fetch() só rejeita a Promise em falhas de REDE — um 400/500 ainda
    // é considerado "sucesso" pelo fetch. Por isso checamos `resposta.ok`
    // manualmente (true para status 200-299).
    if (!resposta.ok) {
        throw new Error(dados.erro ?? 'Erro desconhecido ao consultar a IA.');
    }

    return dados;
}

// Listener do evento de envio do formulário (clique no botão ou Enter)
formPergunta.addEventListener('submit', async (evento) => {
    // Impede o comportamento padrão do navegador (recarregar a página
    // ao submeter um <form>) — queremos controlar isso via JavaScript.
    evento.preventDefault();

    const texto = campoPergunta.value.trim();
    if (texto === '') return;

    // Prepara a interface para o estado "carregando"
    alertaErro.classList.add('d-none');
    carregando.classList.remove('d-none');
    btnEnviar.disabled = true;

    try {
        await enviarPergunta(texto);
        campoPergunta.value = '';
        await carregarHistorico();
    } catch (erro) {
        alertaErro.textContent = erro.message;
        alertaErro.classList.remove('d-none');
    } finally {
        // `finally` roda SEMPRE, com sucesso ou erro — garante que o
        // spinner some e o botão volte a ficar habilitado em qualquer caso.
        carregando.classList.add('d-none');
        btnEnviar.disabled = false;
    }
});

// Carrega o histórico assim que a página termina de abrir
carregarHistorico();
