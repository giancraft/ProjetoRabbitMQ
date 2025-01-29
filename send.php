<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
    ];

    if (empty($userData['name']) || empty($userData['email']) || empty($userData['birthdate'])) {
        http_response_code(400);
        die(json_encode(["message" => "Todos os campos são obrigatórios."]));
    }

    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        die(json_encode(["message" => "E-mail inválido"]));
    }

    try {
        $connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
        $channel = $connection->channel();

        $channel->queue_declare('user_data', false, false, false, false);

        $msg = new AMQPMessage(json_encode($userData), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($msg, '', 'user_data');

        echo json_encode(["message" => "Dados enviados para a fila com sucesso!"]);

        $channel->close();
        $connection->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao conectar com o RabbitMQ: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Método não permitido. Use POST."]);
}
?>