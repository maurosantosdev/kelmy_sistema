<?php
// Script para simular o webhook com os dados reais da nossa tabela
require 'php/db_connect.php';

echo "======= ATUALIZAÇÃO MANUAL DOS STATUS =======\n\n";

// Obter o pagamento mais recente da tabela de associação
$stmt = $conn->prepare("SELECT mp_payment_id, reservation_ids FROM mp_payment_links ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $payment_id = $row['mp_payment_id'];
    $reservation_ids = $row['reservation_ids'];
    
    echo "Pagamento encontrado na tabela de associação:\n";
    echo "- ID do pagamento: {$payment_id}\n";
    echo "- IDs das reservas: {$reservation_ids}\n\n";
    
    echo "Status antes da atualização:\n";
    $ids_array = explode(',', $reservation_ids);
    foreach ($ids_array as $id) {
        $id = trim($id);
        $stmt_check = $conn->prepare("SELECT id, status FROM reservas WHERE id = ?");
        $stmt_check->bind_param("s", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($r = $result_check->fetch_assoc()) {
            echo "- Reserva {$r['id']}: {$r['status']}\n";
        } else {
            echo "- Reserva {$id}: NÃO ENCONTRADA\n";
        }
        $stmt_check->close();
    }
    
    echo "\nAtualizando status para 'confirmado'...\n";
    
    // Atualizar status das reservas
    foreach ($ids_array as $id) {
        $id = trim($id);
        $stmt_update = $conn->prepare("UPDATE reservas SET status = 'confirmado', payment_confirmed_at = NOW() WHERE id = ?");
        $stmt_update->bind_param("s", $id);
        if ($stmt_update->execute()) {
            echo "- Reserva {$id} atualizada para 'confirmado'\n";
            
            // Atualizar também a agenda
            $stmt_reserva = $conn->prepare("SELECT data FROM reservas WHERE id = ?");
            $stmt_reserva->bind_param("s", $id);
            $stmt_reserva->execute();
            $result_reserva = $stmt_reserva->get_result();
            
            if ($reserva = $result_reserva->fetch_assoc()) {
                $stmt_agenda = $conn->prepare("UPDATE agenda SET status = 'reservado' WHERE data = ?");
                $stmt_agenda->bind_param("s", $reserva['data']);
                $stmt_agenda->execute();
                echo "  - Agenda para data {$reserva['data']} atualizada para 'reservado'\n";
            }
            $stmt_reserva->close();
        } else {
            echo "- Erro ao atualizar reserva {$id}\n";
        }
        $stmt_update->close();
    }
    
    // Atualizar status na tabela de associação
    $stmt_assoc = $conn->prepare("UPDATE mp_payment_links SET reservation_status = 'confirmado' WHERE mp_payment_id = ?");
    $stmt_assoc->bind_param("s", $payment_id);
    $stmt_assoc->execute();
    $stmt_assoc->close();
    
    echo "\nStatus após a atualização:\n";
    foreach ($ids_array as $id) {
        $id = trim($id);
        $stmt_check = $conn->prepare("SELECT id, status, payment_confirmed_at FROM reservas WHERE id = ?");
        $stmt_check->bind_param("s", $id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($r = $result_check->fetch_assoc()) {
            echo "- Reserva {$r['id']}: {$r['status']} (confirmado em: {$r['payment_confirmed_at']})\n";
        }
        $stmt_check->close();
    }
    
    // Verificar status da agenda
    echo "\nStatus da agenda:\n";
    $stmt_agenda_all = $conn->prepare("SELECT data, status FROM agenda WHERE data IN (SELECT data FROM reservas WHERE id IN ('" . implode("','", $ids_array) . "'))");
    $stmt_agenda_all->execute();
    $result_agenda_all = $stmt_agenda_all->get_result();
    
    while ($row_agenda = $result_agenda_all->fetch_assoc()) {
        echo "- Data {$row_agenda['data']}: {$row_agenda['status']}\n";
    }
    $stmt_agenda_all->close();
    
} else {
    echo "Nenhum pagamento encontrado na tabela de associação\n";
}

$stmt->close();
$conn->close();
echo "\n======= ATUALIZAÇÃO CONCLUÍDA =======\n";
?>