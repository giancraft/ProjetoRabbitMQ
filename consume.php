<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
$channel = $connection->channel();
$channel->queue_declare('response_data', false, false, false, false);

$messages = ''; // Variável para armazenar a mensagem

$callback = function ($msg) use (&$messages) {
    $response = json_decode($msg->body, true);
    $messages = "<p>" . htmlspecialchars($response['message']) . "</p>"; // Atualiza a mensagem com a nova
};

$channel->basic_consume('response_data', '', false, true, false, false, $callback);

// Aguarda mensagens por um curto período
try {
    $channel->wait(null, false, 1); // Timeout de 1 segundo
} catch (Exception $e) {
    // Nenhuma mensagem recebida no tempo de espera
}

// Fecha a conexão e canal
$channel->close();
$connection->close();

// Retorna a última mensagem ou uma mensagem padrão se nada foi recebido
echo $messages ?: "<p>Aguardando novas mensagens...</p>";
