<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
$channel = $connection->channel();

// Declara as filas
$channel->queue_declare('user_data', false, false, false, false);
$channel->queue_declare('processed_user_data', false, true, false, false);

echo " [*] Waiting for messages in user_data queue. To exit press CTRL+C\n";

$callback = function($msg) use ($channel) {
    $userData = json_decode($msg->getBody(), true);

    if ($userData) {
        echo " [x] Processing user data:\n";
        echo "     Name: " . $userData['name'] . "\n";
        echo "     Email: " . $userData['email'] . "\n";
        echo "     Birthdate: " . $userData['birthdate'] . "\n";

        // Publica na nova fila processed_user_data
        $processedMessage = new AMQPMessage(json_encode($userData), ['delivery_mode' => 2]);
        $channel->basic_publish($processedMessage, '', 'processed_user_data');
        echo " [x] Data forwarded to processed_user_data queue.\n";
    } else {
        echo " [!] Invalid data received.\n";
    }
};

$channel->basic_consume('user_data', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
