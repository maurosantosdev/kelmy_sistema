<?php
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$response = ['success' => false, 'message' => ''];

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$reservation_id = $_POST['reservation_id'] ?? '';

if (empty($reservation_id)) {
    $response['message'] = 'ID da reserva é obrigatório';
    echo json_encode($response);
    exit;
}

// Começar transação
$conn->begin_transaction();

try {
    // Obter informações da reserva
    $stmt = $conn->prepare("SELECT user_id, data, status FROM reservas WHERE id = ? FOR UPDATE");
    $stmt->bind_param("s", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Reserva não encontrada');
    }
    
    $row = $result->fetch_assoc();
    
    // Verificar se o usuário é o dono da reserva
    if ($row['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Acesso não autorizado a esta reserva');
    }
    
    // Verificar se a reserva está confirmada
    if ($row['status'] !== 'confirmado') {
        throw new Exception('Esta reserva ainda não está confirmada');
    }
    
    // Confirmar que está tudo certo
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Reserva confirmada com sucesso';
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>