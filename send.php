<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $userData = [
        'name' => $_POST['name'] ?? 'Nome não fornecido',
        'email' => $_POST['email'] ?? 'Email não fornecido',
        'birthdate' => $_POST['birthdate'] ?? 'Data de nascimento não fornecida',
    ];

    // Serializa os dados em JSON
    $jsonData = json_encode($userData);

    $connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
    $channel = $connection->channel();

    $channel->queue_declare('user_data', false, false, false, false);

    $msg = new AMQPMessage($jsonData);
    $channel->basic_publish($msg, '', 'user_data');

    echo " [x] Sent user data to queue\n";

    $channel->close();
    $connection->close();
} else {
    echo "Por favor, envie os dados através do formulário.";
}
?>
