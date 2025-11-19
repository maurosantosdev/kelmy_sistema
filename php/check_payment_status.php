<?php
require 'db_connect.php';

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
    $stmt = $conn->prepare("SELECT status, created_at FROM reservas WHERE id = ?");
    $stmt->bind_param("s", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'cancelled', 'message' => 'Reserva não encontrada']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $status = $row['status'];
    $created_at = $row['created_at'];
    
    // Verificar se a reserva pendente expirou (5 minutos)
    $created_time = strtotime($created_at);
    $current_time = time();
    $time_diff = ($current_time - $created_time) / 60; // diferença em minutos
    
    if ($status === 'confirmado') {
        echo json_encode(['status' => 'confirmed', 'message' => 'Pagamento confirmado']);
    } elseif ($status === 'pendente') {
        if ($time_diff > 5) {
            // Atualizar status para cancelado devido ao tempo expirado
            $updateStmt = $conn->prepare("UPDATE reservas SET status = 'cancelado' WHERE id = ?");
            $updateStmt->bind_param("s", $reservation_id);
            $updateStmt->execute();
            
            // Libertar a data na agenda
            $reservaDataStmt = $conn->prepare("SELECT data FROM reservas WHERE id = ?");
            $reservaDataStmt->bind_param("s", $reservation_id);
            $reservaDataStmt->execute();
            $reservaDataResult = $reservaDataStmt->get_result();
            if ($reservaDataRow = $reservaDataResult->fetch_assoc()) {
                $updateAgenda = $conn->prepare("UPDATE agenda SET status = 'ativo' WHERE data = ?");
                $updateAgenda->bind_param("s", $reservaDataRow['data']);
                $updateAgenda->execute();
            }
            
            echo json_encode(['status' => 'cancelled', 'message' => 'Tempo limite atingido']);
        } else {
            echo json_encode(['status' => 'pending', 'message' => 'Aguardando pagamento']);
        }
    } else {
        echo json_encode(['status' => 'cancelled', 'message' => 'Reserva cancelada']);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>