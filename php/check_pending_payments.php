<?php
require 'db_connect.php';

/*
 * Script de demonstração para verificar pagamentos pendentes
 * Em um ambiente real, isso seria substituído por uma integração real com a API do banco
 * Este script simula a verificação de pagamento com base em um critério de tempo
 */

// Este script pode ser chamado via cron job ou executado periodicamente
// Por exemplo: */5 * * * * /usr/bin/php /path/to/check_pending_payments.php

echo "Iniciando verificação de pagamentos pendentes...\n";

// Selecionar reservas pendentes criadas há mais de 5 minutos (exemplo)
$cutoff_time = date('Y-m-d H:i:s', strtotime('-5 minutes'));

$stmt = $conn->prepare("SELECT id, data, user_id FROM reservas WHERE status = 'pendente' AND created_at < ?");
$stmt->bind_param("s", $cutoff_time);
$stmt->execute();
$result = $stmt->get_result();

$processed = 0;
while ($row = $result->fetch_assoc()) {
    // Neste exemplo, vamos simular a verificação
    // Em um ambiente real, aqui seria feita a verificação com a API do banco
    
    // Simulando que 50% das transações pendentes são confirmadas
    if (rand(0, 1) === 1) {
        // Confirmar a reserva
        $updateStmt = $conn->prepare("UPDATE reservas SET status = 'confirmado' WHERE id = ?");
        $updateStmt->bind_param("s", $row['id']);
        
        if ($updateStmt->execute()) {
            // Atualizar também a agenda
            $updateAgenda = $conn->prepare("UPDATE agenda SET status = 'reservado' WHERE data = ?");
            $updateAgenda->bind_param("s", $row['data']);
            $updateAgenda->execute();
            
            echo "Reserva {$row['id']} confirmada.\n";
        }
    } else {
        // Cancelar a reserva pendente (tempo expirado)
        $updateStmt = $conn->prepare("UPDATE reservas SET status = 'cancelado' WHERE id = ?");
        $updateStmt->bind_param("s", $row['id']);
        
        if ($updateStmt->execute()) {
            // Libertar a data na agenda novamente
            $updateAgenda = $conn->prepare("UPDATE agenda SET status = 'ativo' WHERE data = ?");
            $updateAgenda->bind_param("s", $row['data']);
            $updateAgenda->execute();
            
            echo "Reserva {$row['id']} cancelada por tempo expirado.\n";
        }
    }
    
    $processed++;
}

echo "Verificação concluída. {$processed} reservas processadas.\n";

$stmt->close();
$conn->close();
?>