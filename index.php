<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Cadastro de Usuários</h1>

    <!-- Formulário de Cadastro -->
    <form id="userForm">
        <label for="name">Nome:</label>
        <input type="text" id="name" name="name" required><br><br>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        
        <label for="birthdate">Data de Nascimento:</label>
        <input type="date" id="birthdate" name="birthdate" required><br><br>
        
        <button type="submit">Enviar</button>
    </form>

    <!-- Exibição de mensagens -->
    <h2>Mensagens</h2>
    <div id="messages">
        <p>Aguardando mensagens...</p>
    </div>

    <script>
        // Função para enviar o formulário via AJAX
        document.getElementById('userForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o envio normal do formulário

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
                }
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
