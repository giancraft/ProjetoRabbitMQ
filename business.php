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

// Requer a classe UserData para trabalhar com o banco
require_once 'data.php';

$connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
$channel = $connection->channel();

$channel->queue_declare('user_data', false, false, false, false);

echo " [*] Waiting for user data. To exit press CTRL+C\n";

$callback = function($msg) {
    $userData = json_decode($msg->getBody(), true);

    if ($userData) {
        echo " [x] Received user data:\n";
        echo "     Name: " . $userData['name'] . "\n";
        echo "     Email: " . $userData['email'] . "\n";
        echo "     Birthdate: " . $userData['birthdate'] . "\n";

        // Validação dos dados recebidos
        $validationResult = validateUserData($userData);
        if ($validationResult === true) {
            // Publicar na fila data_process
            $connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
            $channel = $connection->channel();
            $channel->queue_declare('data_process', false, false, false, false);

            $dataMessage = new AMQPMessage(json_encode($userData));
            $channel->basic_publish($dataMessage, '', 'data_process');

            echo " [x] Dados enviados para a fila data_process.\n";

            $channel->close();
            $connection->close();
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
