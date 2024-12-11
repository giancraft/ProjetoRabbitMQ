<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('user_data', false, false, false, false);

echo " [*] Waiting for user data. To exit press CTRL+C\n";

$callback = function ($msg) {
    // Decodifica o JSON recebido
    $userData = json_decode($msg->getBody(), true);

    if ($userData) {
        echo " [x] Received user data:\n";
        echo "     Name: " . $userData['name'] . "\n";
        echo "     Email: " . $userData['email'] . "\n";
        echo "     Birthdate: " . $userData['birthdate'] . "\n";
    } else {
        echo " [x] Received invalid JSON\n";
    }
};

$channel->basic_consume('user_data', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
