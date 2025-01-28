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

$callback = function ($msg) {
    $userData = json_decode($msg->getBody(), true);

    if ($userData) {
        echo " [x] Processing data for: " . $userData['email'] . "\n";

        $data = new UserData();

        $response = [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ];

        try {
            if ($data->userExists($userData['email'])) {
                $response['status'] = 'error';
                $response['message'] = 'Já existe um cadastro com esse email.';
            } else {
                $data->insertUser($userData);
                $response['status'] = 'success';
                $response['message'] = 'Usuário cadastrado com sucesso.';
            }
        } catch (Exception $e) {
            $response['status'] = 'error';
            $response['message'] = 'Erro ao processar dados: ' . $e->getMessage();
        }

        $responseConnection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
        $responseChannel = $responseConnection->channel();
        $responseChannel->queue_declare('response_data', false, false, false, false);

        $responseMessage = new AMQPMessage(json_encode($response));
        $responseChannel->basic_publish($responseMessage, '', 'response_data');

        echo " [x] Resposta enviada para response_data.\n";

        $responseChannel->close();
        $responseConnection->close();
    } else {
        echo "Dados inválidos recebidos.\n";
    }
};

// Configuração da conexão RabbitMQ para o consumidor
$connection = new AMQPStreamConnection('localhost', 5672, 'admin', 'admin');
$channel = $connection->channel();

// Declaração da fila de processamento
$channel->queue_declare('data_process', false, false, false, false);

// Configurando o consumidor para escutar a fila
echo " [*] Aguardando mensagens na fila 'data_process'. Para sair, pressione CTRL+C\n";
$channel->basic_consume('data_process', '', false, true, false, false, $callback);

// Loop para manter o consumidor ativo
while ($channel->is_consuming()) {
    $channel->wait();
}
