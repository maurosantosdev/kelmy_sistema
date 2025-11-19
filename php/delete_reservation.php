<?php
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se a sessão existe e o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$reserva_id = $_POST['reserva_id'] ?? null;

if (!$reserva_id) {
    echo json_encode(['success' => false, 'message' => 'ID da reserva é obrigatório']);
    exit;
}

try {
    // Verificar se a reserva existe e pertence ao usuário logado
    $stmt_check = $conn->prepare("SELECT r.id, r.status, r.user_id, r.data 
                                  FROM reservas r 
                                  WHERE r.id = ? AND r.user_id = ?");
    $stmt_check->bind_param("si", $reserva_id, $_SESSION['user_id']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reserva não encontrada ou não pertence ao usuário']);
        exit;
    }
    
    $reserva = $result_check->fetch_assoc();
    
    error_log("DELETE RESERVA - ID: {$reserva_id}, Status encontrado: {$reserva['status']}, User ID: {$_SESSION['user_id']}");
    
    // Verificar se a reserva está pendente
    if ($reserva['status'] !== 'pendente') {
        // Fazer uma verificação adicional para depuração
        $debug_stmt = $conn->prepare("SELECT id, status, payment_confirmed_at FROM reservas WHERE id = ? AND user_id = ?");
        $debug_stmt->bind_param("si", $reserva_id, $_SESSION['user_id']);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        $debug_row = $debug_result->fetch_assoc();
        
        error_log("DELETE RESERVA - VERIFICAÇÃO ADICIONAL - ID: {$debug_row['id']}, Status: {$debug_row['status']}, Payment Confirmed At: {$debug_row['payment_confirmed_at']}");
        
        echo json_encode(['success' => false, 'message' => 'Apenas reservas pendentes podem ser excluídas. Status atual: ' . $reserva['status']]);
        exit;
    }
    
    // Verificar se o contrato já foi assinado para esta reserva
    $stmt_contrato = $conn->prepare("SELECT id FROM contratos_assinados WHERE user_id = ? AND reserva_id = ?");
    $stmt_contrato->bind_param("si", $_SESSION['user_id'], $reserva_id);
    $stmt_contrato->execute();
    $result_contrato = $stmt_contrato->get_result();
    
    if ($result_contrato->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Não é possível excluir uma reserva com contrato já assinado']);
        exit;
    }
    
    // Excluir a reserva (transação para garantir consistência)
    $conn->begin_transaction();
    
    try {
        // Obter a data da reserva para atualizar a agenda
        $data_reserva = $reserva['data'];
        
        // Excluir a reserva
        $stmt_delete_reserva = $conn->prepare("DELETE FROM reservas WHERE id = ?");
        $stmt_delete_reserva->bind_param("s", $reserva_id);
        $stmt_delete_reserva->execute();
        
        if ($stmt_delete_reserva->affected_rows > 0) {
            // Atualizar o status da agenda para 'ativo' se a reserva foi excluída com sucesso
            $stmt_update_agenda = $conn->prepare("UPDATE agenda SET status = 'ativo' WHERE data = ? AND status = 'pendente'");
            $stmt_update_agenda->bind_param("s", $data_reserva);
            $stmt_update_agenda->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Reserva excluída com sucesso']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Nenhuma reserva foi excluída']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir a reserva: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na verificação da reserva: ' . $e->getMessage()]);
}

$conn->close();
?>