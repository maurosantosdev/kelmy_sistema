<?php
// Script para simular o recebimento de um webhook do Mercado Pago
require 'php/db_connect.php';

echo "======= SIMULAÇÃO DE WEBHOOK DO MERCADO PAGO =======\n\n";

// IDs de teste que já existem no banco
$payment_id = '50838133487'; // ID de pagamento que causaria falha na API (404), como nos logs
$reservation_ids = 'res_6908e2589724f9.90889415,res_6908e258972605.30832210'; // IDs reais das reservas
$reservation_status = 'confirmado'; // Status que deveria ser definido para pagamento aprovado

echo "1. Registrando associação para simulação...\n";
$stmt_link = $conn->prepare("INSERT INTO mp_payment_links (mp_payment_id, reservation_ids, reservation_status) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE reservation_ids = VALUES(reservation_ids), reservation_status = VALUES(reservation_status), updated_at = CURRENT_TIMESTAMP");
if ($stmt_link) {
    $stmt_link->bind_param("sss", $payment_id, $reservation_ids, $reservation_status);
    if ($stmt_link->execute()) {
        echo "   - Associação registrada: {$payment_id} -> {$reservation_ids}\n";
    } else {
        echo "   - Erro ao registrar associação: " . $stmt_link->error . "\n";
    }
    $stmt_link->close();
}

echo "\n2. Verificando status antes da atualização:\n";
$stmt_before = $conn->prepare("SELECT id, status, payment_confirmed_at FROM reservas WHERE id IN ('res_6908e2589724f9.90889415', 'res_6908e258972605.30832210') ORDER BY id");
$stmt_before->execute();
$result_before = $stmt_before->get_result();

while ($row = $result_before->fetch_assoc()) {
    echo "   - Reserva {$row['id']}: Status={$row['status']}, ConfirmedAt={$row['payment_confirmed_at']}\n";
}
$stmt_before->close();

echo "\n3. Verificando status da agenda antes da atualização:\n";
$stmt_agenda_before = $conn->prepare("SELECT data, status FROM agenda WHERE data IN ('2025-10-16', '2025-10-17') ORDER BY data");
$stmt_agenda_before->execute();
$result_agenda_before = $stmt_agenda_before->get_result();

while ($row = $result_agenda_before->fetch_assoc()) {
    echo "   - Data {$row['data']}: Status={$row['status']}\n";
}
$stmt_agenda_before->close();

echo "\n4. Chamando diretamente a função de processamento de reservas...\n";

// Incluindo a função processarReservas do webhook
function processarReservas($external_reference, $reservation_status, $conn) {
    error_log("WEBHOOK - Iniciando processamento de reservas - external_reference: {$external_reference}, status: {$reservation_status}");
    
    // O external_reference pode conter múltiplos IDs de reserva separados por vírgula
    $reservation_ids = explode(',', $external_reference);
    
    // Verificar se os IDs precisam ser limpos de espaços ou caracteres especiais
    $reservation_ids = array_map('trim', $reservation_ids);
    
    error_log("WEBHOOK - IDs de reserva extraídos: " . print_r($reservation_ids, true));
    error_log("WEBHOOK - Quantidade de IDs de reservas a serem atualizados: " . count($reservation_ids) . ", IDs: " . implode(',', $reservation_ids));
    
    // Atualizar todas as reservas associadas a este pagamento
    foreach ($reservation_ids as $reservation_id) {
        $reservation_id = trim($reservation_id); // Remover espaços em branco
        
        // Validar que o ID da reserva é válido antes de fazer a atualização
        // O formato do ID gerado por uniqid('res_', true) é como 'res_68fbe6b2e9b5f2.69131627'
        if (!empty($reservation_id) && preg_match('/^res_[a-f0-9]+[.][0-9]+$/', $reservation_id)) {
            error_log("WEBHOOK - Atualizando reserva: {$reservation_id} para status: {$reservation_status}");
            
            if ($reservation_status === 'confirmado') {
                // Atualizar status e definir o tempo de confirmação
                $stmt = $conn->prepare("UPDATE reservas SET status = ?, payment_confirmed_at = NOW() WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("WEBHOOK - Definindo payment_confirmed_at para a reserva: {$reservation_id}");
            } else {
                // Para outros status, não atualizar o tempo de confirmação
                $stmt = $conn->prepare("UPDATE reservas SET status = ? WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("WEBHOOK - Atualizando status (sem payment_confirmed_at) para a reserva: {$reservation_id}");
            }
            
            if ($stmt->execute()) {
                error_log("WEBHOOK - Reserva {$reservation_id} atualizada com sucesso para status: {$reservation_status}");
                
                // Log adicional para verificar resposta do banco
                error_log("WEBHOOK - Linhas afetadas pela atualização: " . $stmt->affected_rows);
                
                // Se o pagamento foi aprovado, atualizar também a agenda
                if ($reservation_status === 'confirmado') {
                    // Obter a data da reserva
                    $reserva_data = $conn->prepare("SELECT data FROM reservas WHERE id = ?");
                    $reserva_data->bind_param("s", $reservation_id);
                    $reserva_data->execute();
                    $result = $reserva_data->get_result();
                    
                    error_log("WEBHOOK - Consulta à tabela reservas executada para reserva {$reservation_id}");
                    error_log("WEBHOOK - Linhas retornadas pela consulta: " . $result->num_rows);
                    
                    if ($row = $result->fetch_assoc()) {
                        error_log("WEBHOOK - Data da reserva {$reservation_id} encontrada: {$row['data']}");
                        
                        // Atualizar status da agenda para 'reservado' quando o pagamento é confirmado via webhook
                        $update_agenda = $conn->prepare("UPDATE agenda SET status = 'reservado' WHERE data = ?");
                        $update_agenda->bind_param("s", $row['data']);
                        $update_agenda->execute();
                        error_log("WEBHOOK - Atualização da agenda para data {$row['data']}, status 'reservado' (reserva: {$reservation_id}), linhas afetadas: " . $update_agenda->affected_rows);
                    } else {
                        error_log("WEBHOOK - Erro: Não foi possível encontrar a data da reserva {$reservation_id}");
                    }
                }
            } else {
                error_log("WEBHOOK - Erro ao atualizar reserva {$reservation_id}: " . $stmt->error);
            }
            
            $stmt->close();
        } else {
            error_log("WEBHOOK - ID de reserva inválido ignorado: {$reservation_id}");
            error_log("WEBHOOK - Padrão esperado: /^res_[a-f0-9]+[.][0-9]+$/");
            error_log("WEBHOOK - ID recebido: {$reservation_id}");
            error_log("WEBHOOK - Validação do ID: " . (preg_match('/^res_[a-f0-9]+[.][0-9]+$/', $reservation_id) ? 'PASSOU' : 'FALHOU'));
        }
    }
}

// Chamando a função de processamento
processarReservas($reservation_ids, $reservation_status, $conn);

echo "\n5. Verificando status após a atualização:\n";
$stmt_after = $conn->prepare("SELECT id, status, payment_confirmed_at FROM reservas WHERE id IN ('res_6908e2589724f9.90889415', 'res_6908e258972605.30832210') ORDER BY id");
$stmt_after->execute();
$result_after = $stmt_after->get_result();

while ($row = $result_after->fetch_assoc()) {
    echo "   - Reserva {$row['id']}: Status={$row['status']}, ConfirmedAt={$row['payment_confirmed_at']}\n";
}
$stmt_after->close();

echo "\n6. Verificando status da agenda após a atualização:\n";
$stmt_agenda_after = $conn->prepare("SELECT data, status FROM agenda WHERE data IN ('2025-10-16', '2025-10-17') ORDER BY data");
$stmt_agenda_after->execute();
$result_agenda_after = $stmt_agenda_after->get_result();

while ($row = $result_agenda_after->fetch_assoc()) {
    echo "   - Data {$row['data']}: Status={$row['status']}\n";
}
$stmt_agenda_after->close();

echo "\n======= SIMULAÇÃO CONCLUÍDA =======\n";

// Reverter as alterações para não afetar o sistema
echo "\n7. Revertendo alterações para estado original...\n";
$revert_reservas = $conn->prepare("UPDATE reservas SET status = 'pendente', payment_confirmed_at = NULL WHERE id IN ('res_6908e2589724f9.90889415', 'res_6908e258972605.30832210')");
$revert_reservas->execute();
echo "   - Reservas revertidas para pendente\n";

$revert_agenda = $conn->prepare("UPDATE agenda SET status = 'pendente' WHERE data IN ('2025-10-16', '2025-10-17')");
$revert_agenda->execute();
echo "   - Agenda revertida para pendente\n";

$revert_link = $conn->prepare("DELETE FROM mp_payment_links WHERE mp_payment_id = ?");
$revert_link->bind_param("s", $payment_id);
$revert_link->execute();
echo "   - Registro de associação removido\n";

$revert_reservas->close();
$revert_agenda->close();
$revert_link->close();

$conn->close();
?>