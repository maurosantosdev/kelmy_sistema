<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obter dados do formulário
$senha_atual = $_POST['senha_atual'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

// Validação dos dados
if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

if ($nova_senha !== $confirmar_senha) {
    echo json_encode(['success' => false, 'message' => 'A nova senha e a confirmação de senha não coincidem']);
    exit;
}

// Verificar se a senha atual está correta
$stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    exit;
}

$user = $result->fetch_assoc();
$senha_armazenada = $user['senha'];

// Verificar se a senha atual está correta
if (!password_verify($senha_atual, $senha_armazenada)) {
    echo json_encode(['success' => false, 'message' => 'A senha atual está incorreta']);
    exit;
}

// Validar força da nova senha
if (strlen($nova_senha) < 6) {
    echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres']);
    exit;
}

// Criptografar a nova senha
$nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

// Atualizar a senha no banco de dados
$update_stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
$update_stmt->bind_param("si", $nova_senha_hash, $user_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao alterar senha']);
}

$stmt->close();
$update_stmt->close();
$conn->close();
?>