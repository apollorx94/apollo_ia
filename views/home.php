<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apollo IA - Perguntas e Respostas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 700px;">
        <h1 class="mb-4 text-center">🤖 Apollo IA</h1>
        <p class="text-muted text-center mb-4">Faça sua pergunta e receba uma resposta gerada por IA</p>

        <form id="form-pergunta" class="mb-4">
            <div class="mb-3">
                <textarea
                    id="campo-pergunta"
                    class="form-control"
                    rows="3"
                    placeholder="Digite sua pergunta..."
                    required
                ></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="btn-enviar">
                Perguntar
            </button>
        </form>

        <div id="carregando" class="text-center mb-4 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Consultando a IA...</p>
        </div>

        <div id="alerta-erro" class="alert alert-danger d-none" role="alert"></div>

        <h2 class="h5 mb-3">Histórico</h2>
        <div id="lista-historico"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
