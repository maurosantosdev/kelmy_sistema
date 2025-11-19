<?php
// Linhas para forçar a exibição de qualquer erro do PHP. Essencial para diagnóstico.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';

// Inicia a sessão para verificar autenticação do administrador
session_start();

header('Content-Type: application/json');

error_log('BANCO - Iniciando change_client_password.php - Session ID: ' . session_id());

// Verificar detalhes da sessão
error_log('BANCO - admin_logged_in: ' . (isset($_SESSION['admin_logged_in']) ? $_SESSION['admin_logged_in'] : 'não definido'));
error_log('BANCO - admin_id: ' . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'não definido'));
error_log('BANCO - admin_usuario: ' . (isset($_SESSION['admin_usuario']) ? $_SESSION['admin_usuario'] : 'não definido'));

// --- Bloco de Segurança: Verifica se o Admin está logado ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log('BANCO - Tentativa de acesso não autorizado a change_client_password.php');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_cliente = $_POST['email_cliente'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';

    error_log('BANCO - Recebido pedido para alterar senha do cliente: ' . $email_cliente);

    if (empty($email_cliente)) {
        $response['message'] = 'O email do cliente é obrigatório.';
    } elseif (empty($nova_senha)) {
        $response['message'] = 'A nova senha é obrigatória.';
    } elseif (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Email inválido.';
    } elseif (strlen($nova_senha) < 6) {
        $response['message'] = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        // Criptografa a nova senha usando o mesmo método do cadastro de clientes (bcrypt)
        $senha_hashed = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Prepara a query para atualizar a senha do cliente
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $stmt->bind_param("ss", $senha_hashed, $email_cliente);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Senha atualizada com sucesso para o cliente: ' . $email_cliente;
                error_log('BANCO - Senha atualizada com sucesso para o cliente: ' . $email_cliente);
            } else {
                $response['message'] = 'Nenhum cliente encontrado com este email: ' . $email_cliente;
                error_log('BANCO - Nenhum cliente encontrado com o email: ' . $email_cliente);
            }
        } else {
            $response['message'] = 'Erro ao atualizar a senha: ' . $stmt->error;
            error_log('BANCO - Erro ao atualizar senha: ' . $stmt->error);
        }
        
        $stmt->close();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
    error_log('BANCO - Método de requisição inválido para change_client_password.php');
}

echo json_encode($response);
?>