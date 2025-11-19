<?php
// Script para limpar reservas pendentes abandonadas
// Este script deve ser executado periodicamente (por exemplo, via cron job)
// para limpar reservas pendentes que não foram confirmadas após um tempo limite

require 'db_connect.php';

// Definir o tempo limite (em minutos) após o qual uma reserva pendente é considerada abandonada
$timeout_minutes = 30;

echo "Iniciando limpeza de reservas abandonadas (timeout: {$timeout_minutes} minutos)...\n";

try {
    // Encontrar datas em agenda com status 'pendente' que têm reservas pendentes antigas
    // (reservas pendentes criadas há mais de $timeout_minutes minutos)
    $stmt = $conn->prepare("
        SELECT a.data, a.id, r.id as reserva_id
        FROM agenda a
        LEFT JOIN reservas r ON a.data = r.data AND r.status = 'pendente'
        WHERE a.status = 'pendente'
        AND r.id IS NOT NULL
        AND r.created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->bind_param("i", $timeout_minutes);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            // Excluir a reserva pendente antiga
            $deleteReserva = $conn->prepare("DELETE FROM reservas WHERE id = ?");
            $deleteReserva->bind_param("s", $row['reserva_id']);
            
            if ($deleteReserva->execute()) {
                // Atualizar status da agenda de 'pendente' para 'ativo'
                $updateAgenda = $conn->prepare("UPDATE agenda SET status = 'ativo' WHERE id = ?");
                $updateAgenda->bind_param("i", $row['id']);
                
                if ($updateAgenda->execute()) {
                    echo "Data {$row['data']} (ID: {$row['id']}) limpa - reserva pendente antiga excluída e status atualizado para 'ativo'\n";
                    $count++;
                } else {
                    echo "Erro ao atualizar agenda para data {$row['data']}: " . $conn->error . "\n";
                }
            } else {
                echo "Erro ao excluir reserva pendente {$row['reserva_id']}: " . $conn->error . "\n";
            }
        }
        
        echo "Limpeza concluída. {$count} registros atualizados.\n";
    } else {
        echo "Erro na consulta: " . $conn->error . "\n";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "Erro durante a limpeza: " . $e->getMessage() . "\n";
}

$conn->close();
?>
</content>