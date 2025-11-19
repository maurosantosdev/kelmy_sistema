<?php
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

session_start();
if (!isset($_SESSION['user_id']) && !isset($_POST['admin_key']) && $_POST['admin_key'] !== 'admin_sync_2025') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'sync_agenda_for_date') {
        $date = $_POST['date'] ?? '';
        if (empty($date)) {
            throw new Exception('Data é obrigatória');
        }
        
        // Verificar se há alguma reserva ativa para esta data
        $check_stmt = $conn->prepare("SELECT status FROM reservas WHERE data = ? AND status IN ('pendente', 'confirmado') LIMIT 1");
        $check_stmt->bind_param("s", $date);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Nenhuma reserva ativa, restaurar para 'ativo'
            $update_agenda = $conn->prepare("UPDATE agenda SET status = 'ativo' WHERE data = ?");
            $update_agenda->bind_param("s", $date);
            $update_agenda->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => "Status da agenda para {$date} atualizado para 'ativo'"
            ]);
        } else {
            // Há reserva ativa, manter status atual
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true, 
                'message' => "A data {$date} tem reserva ativa (status: {$row['status']}), mantido status"
            ]);
        }
        
    } elseif ($action === 'sync_all_agenda') {
        // Restaurar status 'ativo' para datas que estão como 'pendente' ou 'reservado' 
        // mas que não têm reservas correspondentes
        $sql = "UPDATE agenda 
                SET status = 'ativo' 
                WHERE status IN ('pendente', 'reservado') 
                AND data NOT IN (
                    SELECT DISTINCT data 
                    FROM reservas 
                    WHERE status IN ('pendente', 'confirmado')
                )";

        if ($conn->query($sql)) {
            $affected_rows = $conn->affected_rows;
            echo json_encode([
                'success' => true, 
                'message' => "Sincronização concluída. {$affected_rows} registros atualizados."
            ]);
        } else {
            throw new Exception('Erro na sincronização: ' . $conn->error);
        }
    } else {
        throw new Exception('Ação desconhecida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>