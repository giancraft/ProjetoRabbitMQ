<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Carrega as variáveis do .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class UserData {
    private $pdo;

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
        // Verifica se já existe um usuário com o mesmo email
        if ($this->userExists($userData['email'])) {
            echo "Já existe um cadastro com esse email: " . $userData['email'] . "\n";
            return;
        }

        // Se o usuário não existir, insere no banco
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
