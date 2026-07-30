const formPergunta   = document.getElementById('form-pergunta');
const campoPergunta  = document.getElementById('campo-pergunta');
const btnEnviar      = document.getElementById('btn-enviar');
const carregando     = document.getElementById('carregando');
const alertaErro     = document.getElementById('alerta-erro');
const listaHistorico = document.getElementById('lista-historico');

/**
 * Formata uma data ISO/timestamp do banco em formato legível brasileiro.
 */
function formatarData(dataBruta) {
    const data = new Date(dataBruta.replace(' ', 'T'));
    return data.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

/**
 * Renderiza a lista de perguntas na tela.
 */
function renderizarHistorico(perguntas) {
    if (perguntas.length === 0) {
        listaHistorico.innerHTML = '<p class="text-muted">Nenhuma pergunta ainda.</p>';
        return;
    }

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
 * Previne XSS ao inserir texto vindo do usuário/IA diretamente no HTML.
 */
function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

/**
 * Busca o histórico atualizado na API e renderiza na tela.
 */
async function carregarHistorico() {
    const resposta = await fetch('api.php?recurso=perguntas');
    const dados = await resposta.json();
    renderizarHistorico(dados);
}

/**
 * Envia uma nova pergunta para a API.
 */
async function enviarPergunta(textoPergunta) {
    const resposta = await fetch('api.php?recurso=perguntas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pergunta: textoPergunta }),
    });

    const dados = await resposta.json();

    if (!resposta.ok) {
        throw new Error(dados.erro ?? 'Erro desconhecido ao consultar a IA.');
    }

    return dados;
}

formPergunta.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const texto = campoPergunta.value.trim();
    if (texto === '') return;

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
        carregando.classList.add('d-none');
        btnEnviar.disabled = false;
    }
});

// Carrega o histórico assim que a página abre
carregarHistorico();
