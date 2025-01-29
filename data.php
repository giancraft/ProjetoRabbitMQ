<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Carrega as variáveis do .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class UserData {
    public $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD']
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erro de conexão com o banco de dados: " . $e->getMessage();
            exit;
        }
    }

    // Verificar se o usuário já existe
    public function userExists($email) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Inserir usuário no banco de dados
    public function insertUser($userData) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO users (name, email, birthdate)
                VALUES (:name, :email, :birthdate)
            ');

            $stmt->execute([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'birthdate' => $userData['birthdate']
            ]);

            echo "Usuário cadastrado com sucesso: " . $userData['name'] . "\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir dados: " . $e->getMessage();
        }
    }
}

// Configuração do RabbitMQ
try {
    $connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
    $channel = $connection->channel();
    echo " [*] Conectado ao RabbitMQ.\n";
} catch (Exception $e) {
    die("Erro ao conectar ao RabbitMQ: " . $e->getMessage());
}

// Declaração das filas
$channel->queue_declare('data_process', false, false, false, false);
$channel->queue_declare('response_data', false, false, false, false);

// Callback com tratamento de exceções
$callback = function ($msg) use ($channel) {
    try {
        $userData = json_decode($msg->getBody(), true);
        if (!$userData) {
            throw new Exception("Dados inválidos recebidos.");
        }

        echo " [x] Processando dados para: " . $userData['email'] . "\n";

        $data = new UserData();
        $response = [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ];

        if ($data->userExists($userData['email'])) {
            $response['status'] = 'error';
            $response['message'] = 'Já existe um cadastro com esse email.';
        } else {
            $data->insertUser($userData);
            $response['status'] = 'success';
            $response['message'] = 'Usuário cadastrado com sucesso.';
        }

        $responseMessage = new AMQPMessage(
            json_encode($response),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $channel->basic_publish($responseMessage, '', 'response_data');
        echo " [x] Resposta enviada para response_data.\n";

        $channel->basic_ack($msg->delivery_tag);
    } catch (Exception $e) {
        echo " [!] Erro: " . $e->getMessage() . "\n";
        $channel->basic_nack($msg->delivery_tag, false, true); // Requeue a mensagem em caso de erro
    }
};

// Configura o consumidor
$channel->basic_consume(
    'data_process',
    '',
    false,
    false,
    false,
    false,
    $callback
);

echo " [*] Aguardando mensagens na fila 'data_process'. Para sair, pressione CTRL+C\n";

try {
    while ($channel->is_consuming()) {
        $channel->wait();
    }
} catch (Exception $e) {
    echo "Erro no consumidor: " . $e->getMessage();
}

$channel->close();
$connection->close();
