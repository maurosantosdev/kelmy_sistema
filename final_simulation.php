<?php
// Script para simular o webhook completo com os dados reais
require 'php/db_connect.php';

echo "======= SIMULAÇÃO DO PROCESSO COMPLETO =======\n\n";

// IDs reais que vimos nos logs
$reservation_ids = 'res_6908e508d54168.32500087,res_6908e508d54263.57010498';
$payment_id = '50838133487'; // ID de exemplo que aparece nos logs
$action = 'payment.updated'; // Ação que indica pagamento aprovado

echo "1. Status antes da simulação:\n";
$stmt_check = $conn->prepare("SELECT id, data, status, payment_confirmed_at FROM reservas WHERE id IN ('res_6908e508d54168.32500087', 'res_6908e508d54263.57010498') ORDER BY id");
$stmt_check->execute();
$result_check = $stmt_check->get_result();

while ($row = $result_check->fetch_assoc()) {
    echo "   - Reserva {$row['id']}: Status={$row['status']}, ConfirmedAt={$row['payment_confirmed_at']}\n";
}
$stmt_check->close();

echo "\n2. Status da agenda antes:\n";
$stmt_agenda_check = $conn->prepare("SELECT data, status FROM agenda WHERE data IN ('2025-10-16', '2025-10-17') ORDER BY data");
$stmt_agenda_check->execute();
$result_agenda_check = $stmt_agenda_check->get_result();

while ($row = $result_agenda_check->fetch_assoc()) {
    echo "   - Data {$row['data']}: Status={$row['status']}\n";
}
$stmt_agenda_check->close();

echo "\n3. Simulando o webhook para pagamento {$payment_id} com ação: {$action}\n";
echo "   - External reference: {$reservation_ids}\n";
echo "   - Status a ser aplicado: confirmado (porque action é {$action})\n";

// Simular a lógica do webhook com base na ação
$reservation_status = 'pendente';
if (strpos($action, 'payment.updated') !== false || 
    strpos($action, 'payment.approved') !== false || 
    strpos($action, 'approved') !== false || 
    strpos($action, 'succeeded') !== false ||
    strpos($action, 'completed') !== false) {
    $reservation_status = 'confirmado';
} elseif (strpos($action, 'payment.rejected') !== false || 
          strpos($action, 'rejected') !== false || 
          strpos($action, 'cancelled') !== false || 
          strpos($action, 'refunded') !== false) {
    $reservation_status = 'cancelado';
}

echo "   - Status determinado: {$reservation_status}\n";

// Função para processar reservas (cópia da função do webhook)
function processarReservas($external_reference, $reservation_status, $conn) {
    error_log("WEBHOOK - Iniciando processamento de reservas - external_reference: {$external_reference}, status: {$reservation_status}");
    
    $reservation_ids = explode(',', $external_reference);
    $reservation_ids = array_map('trim', $reservation_ids);
    
    foreach ($reservation_ids as $reservation_id) {
        $reservation_id = trim($reservation_id);
        
        if (!empty($reservation_id) && preg_match('/^res_[a-f0-9]+[.][0-9]+$/', $reservation_id)) {
            if ($reservation_status === 'confirmado') {
                $stmt = $conn->prepare("UPDATE reservas SET status = ?, payment_confirmed_at = NOW() WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("WEBHOOK - Definindo payment_confirmed_at para a reserva: {$reservation_id}");
            } else {
                $stmt = $conn->prepare("UPDATE reservas SET status = ? WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
            }
            
            if ($stmt->execute()) {
                echo "   - Reserva {$reservation_id} atualizada para: {$reservation_status} (afetadas: {$stmt->affected_rows})\n";
                
                if ($reservation_status === 'confirmado') {
                    $reserva_data = $conn->prepare("SELECT data FROM reservas WHERE id = ?");
                    $reserva_data->bind_param("s", $reservation_id);
                    $reserva_data->execute();
                    $result = $reserva_data->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $update_agenda = $conn->prepare("UPDATE agenda SET status = 'reservado' WHERE data = ?");
                        $update_agenda->bind_param("s", $row['data']);
                        $update_agenda->execute();
                        echo "   - Agenda para data {$row['data']} atualizada para: reservado (afetadas: {$update_agenda->affected_rows})\n";
                    }
                }
            } else {
                echo "   - Erro ao atualizar reserva {$reservation_id}: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "   - ID de reserva inválido ignorado: {$reservation_id}\n";
        }
    }
}

// Processar as reservas
processarReservas($reservation_ids, $reservation_status, $conn);

echo "\n4. Status após a simulação:\n";
$stmt_after = $conn->prepare("SELECT id, data, status, payment_confirmed_at FROM reservas WHERE id IN ('res_6908e508d54168.32500087', 'res_6908e508d54263.57010498') ORDER BY id");
$stmt_after->execute();
$result_after = $stmt_after->get_result();

while ($row = $result_after->fetch_assoc()) {
    echo "   - Reserva {$row['id']}: Status={$row['status']}, ConfirmedAt={$row['payment_confirmed_at']}\n";
}
$stmt_after->close();

echo "\n5. Status da agenda após:\n";
$stmt_agenda_after = $conn->prepare("SELECT data, status FROM agenda WHERE data IN ('2025-10-16', '2025-10-17') ORDER BY data");
$stmt_agenda_after->execute();
$result_agenda_after = $stmt_agenda_after->get_result();

while ($row = $result_agenda_after->fetch_assoc()) {
    echo "   - Data {$row['data']}: Status={$row['status']}\n";
}
$stmt_agenda_after->close();

echo "\n======= SIMULAÇÃO CONCLUÍDA =======\n";

$conn->close();
?>