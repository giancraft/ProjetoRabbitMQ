<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Usuários</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <header class="text-center mb-5">
            <h1 class="display-4 text-primary fw-bold">Cadastro de Usuários</h1>
            <p class="lead text-muted">Sistema de gerenciamento de usuários com RabbitMQ</p>
        </header>

        <!-- Formulário -->
        <div class="card shadow-lg mb-5">
            <div class="card-body">
                <form id="userForm" class="needs-validation" novalidate>
                    <div class="form-vertical">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome completo</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Por favor insira um nome válido.</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Por favor insira um e-mail válido.</div>
                        </div>

                        <div class="mb-3">
                            <label for="birthdate" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                            <div class="invalid-feedback">Por favor insira uma data válida.</div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send-check me-2"></i>Enviar Dados
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mensagens -->
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0"><i class="bi bi-chat-left-text me-2"></i>Últimas Mensagens</h2>
            </div>
            <div class="card-body">
                <div id="messages" class="message-box">
                    <p class="text-muted mb-0">Aguardando mensagens...</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Função para enviar o formulário via AJAX
        document.getElementById('userForm').addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            this.classList.add('was-validated');

            if (!this.checkValidity()) {
                return;
            }

            // Cria um objeto FormData com os dados do formulário
            const formData = new FormData(this);

            // Cria uma requisição AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'send.php', true);

            // Define a função de callback quando a requisição for completada
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Exibe a resposta do servidor (pode ser uma mensagem de sucesso)
                    alert('Dados enviados com sucesso!');

                    // Limpa os campos do formulário
                    document.getElementById('userForm').reset();

                    // Atualiza a seção de mensagens após o envio
                    updateMessages();
                } else {
                    alert('Erro: ' + xhr.status + ' - ' + xhr.statusText + '\n' + xhr.responseText);
                }
            };

            xhr.onerror = function () {
                alert('Erro de conexão. Verifique sua rede.');
            };

            // Envia os dados do formulário
            xhr.send(formData);
        });

        // Variável para armazenar a última mensagem recebida
        let lastMessages = ''; // Variável para armazenar o histórico de mensagens

        // Função para atualizar a seção de mensagens
        function updateMessages() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'consume.php', true); // Arquivo responsável por consumir a fila
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const newMessages = xhr.responseText.trim();

                    // Atualiza a exibição apenas se houver novas mensagens
                    if (newMessages !== lastMessages) {
                        lastMessages = newMessages;
                        document.getElementById('messages').innerHTML = newMessages;
                    }
                }
            };
            xhr.send();
        }

        // Atualiza a seção de mensagens periodicamente
        setInterval(updateMessages, 5000); // Atualiza a cada 5 segundos



    </script>
</body>

</html>