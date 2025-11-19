<?php
require 'db_connect.php';

// Carregar configurações do Mercado Pago somente se necessário para este endpoint
// Neste caso, estamos apenas verificando o status no banco, não precisamos do SDK

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
    exit;
}

$reservation_id = $_POST['reservation_id'] ?? '';

if (empty($reservation_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID da reserva é obrigatório']);
    exit;
}

try {
    // Consultar status da reserva no banco
    error_log("BANCO - Consultando status da reserva: {$reservation_id}", 3, '/var/log/apache2/log_banco.log');
    
    $stmt = $conn->prepare("SELECT status FROM reservas WHERE id = ?");
    $stmt->bind_param("s", $reservation_id);
    $stmt->execute();
    
    error_log("BANCO - Query executada para reserva: {$reservation_id}", 3, '/var/log/apache2/log_banco.log');
    
    $result = $stmt->get_result();
    
    error_log("BANCO - Resultado da query para reserva {$reservation_id}: " . $result->num_rows . " linhas encontradas", 3, '/var/log/apache2/log_banco.log');
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'cancelled', 'message' => 'Reserva não encontrada']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $status = $row['status'];
    
    error_log("BANCO - Status encontrado para reserva {$reservation_id}: {$status}", 3, '/var/log/apache2/log_banco.log');
    
    if ($status === 'confirmado') {
        echo json_encode(['status' => 'confirmed', 'message' => 'Pagamento confirmado']);
    } elseif ($status === 'pendente') {
        echo json_encode(['status' => 'pending', 'message' => 'Aguardando pagamento']);
    } elseif ($status === 'cancelado') {
        echo json_encode(['status' => 'cancelled', 'message' => 'Reserva cancelada']);
    } else {
        echo json_encode(['status' => 'cancelled', 'message' => 'Reserva cancelada']);
    }
    
} catch (Exception $e) {
    error_log("BANCO - Erro na consulta da reserva {$reservation_id}: " . $e->getMessage(), 3, '/var/log/apache2/log_banco.log');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>