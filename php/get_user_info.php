<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Retornar erro se não estiver autenticado
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Conectar ao banco de dados
require_once 'db_connect.php';

// Obter informações do usuário
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT nome, email, telefone, cpf, rg, data_nascimento, estado_civil, rua, numero, bairro, cep, cidade, created_at FROM usuarios WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = null;
if ($user_row = $user_result->fetch_assoc()) {
    $user_info = $user_row;
    // Armazenar nome e email na sessão para uso futuro
    $_SESSION['user_name'] = $user_row['nome'];
    $_SESSION['user_email'] = $user_row['email'];
}
$user_stmt->close();
$conn->close();

if ($user_info) {
    // Retornar informações completas do usuário
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'user_name' => $user_info['nome'],
        'user' => [
            'nome' => $user_info['nome'],
            'email' => $user_info['email'],
            'telefone' => $user_info['telefone'],
            'cpf' => $user_info['cpf'],
            'rg' => $user_info['rg'],
            'data_nascimento' => $user_info['data_nascimento'],
            'estado_civil' => $user_info['estado_civil'],
            'rua' => $user_info['rua'],
            'numero' => $user_info['numero'],
            'bairro' => $user_info['bairro'],
            'cep' => $user_info['cep'],
            'cidade' => $user_info['cidade'],
            'created_at' => $user_info['created_at']
        ]
    ]);
} else {
    // Retorna erro se o usuário não for encontrado
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não encontrado'
    ]);
}
?>