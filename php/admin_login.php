<?php
// Inclui o arquivo de conexão
require 'db_connect.php';

// Inicia a sessão para armazenar informações de login
session_start();

// Define o cabeçalho como JSON para a resposta do JAVASCRIPT
header('Content-Type: application/json');

// Prepara um objeto de resposta
$response = ['success' => false, 'message' => ''];

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        $response['message'] = 'Usuário e senha são obrigatórios.';
    } else {
        // Criptografa a senha recebida com SHA-512 para comparar com a do banco
        $senha_hashed = hash('sha512', $senha);

        // Prepara a query para evitar SQL Injection
        $stmt = $conn->prepare("SELECT id, usuario FROM administrador WHERE usuario = ? AND senha = ?");
        $stmt->bind_param("ss", $usuario, $senha_hashed);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Login bem-sucedido
            $admin = $result->fetch_assoc();
            
            // Armazena informações na sessão
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_usuario'] = $admin['usuario'];

            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso!';
        } else {
            // Credenciais inválidas
            $response['message'] = 'Usuário ou senha inválidos.';
        }
        $stmt->close();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

$conn->close();

// Retorna a resposta em formato JSON
echo json_encode($response);
?>