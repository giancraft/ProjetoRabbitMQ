<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Função para validar os dados
function validateUserData($userData) {
    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        return "Email inválido.";
    }
    if (empty($userData['name']) || empty($userData['birthdate'])) {
        return "Nome e data de nascimento são obrigatórios.";
    }
    return true;
}

// Configuração da conexão RabbitMQ
$connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
$channel = $connection->channel();

// Declara as filas necessárias
$channel->queue_declare('user_data', false, false, false, false);
$channel->queue_declare('data_process', false, false, false, false); // Garante que a fila existe

echo " [*] Waiting for user data. To exit press CTRL+C\n";

$callback = function($msg) use ($connection) {
    $userData = json_decode($msg->getBody(), true);

    if ($userData) {
        echo " [x] Received user data:\n";
        echo "     Name: " . $userData['name'] . "\n";
        echo "     Email: " . $userData['email'] . "\n";
        echo "     Birthdate: " . $userData['birthdate'] . "\n";

        // Validação dos dados recebidos
        $validationResult = validateUserData($userData);
        if ($validationResult === true) {
            // Usa a conexão existente para criar um canal de publicação
            $publishChannel = $connection->channel();
            $publishChannel->queue_declare('data_process', false, false, false, false);

            $dataMessage = new AMQPMessage(json_encode($userData));
            $publishChannel->basic_publish($dataMessage, '', 'data_process');

            echo " [x] Dados enviados para a fila data_process.\n";

            $publishChannel->close();
        } else {
            echo "Erro de validação: " . $validationResult . "\n";
        }
    } else {
        echo "Dados inválidos recebidos.\n";
    }
};

$channel->basic_consume('user_data', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();