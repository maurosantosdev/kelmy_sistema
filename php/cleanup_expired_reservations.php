<?php
// Script para manutenção da agenda e limpeza de reservas expiradas
require 'db_connect.php';

// Limpar reservas pendentes expiradas (mais de 30 minutos sem confirmação de pagamento)
// e restaurar status na agenda
$sql = "UPDATE agenda 
        SET status = 'ativo' 
        WHERE data IN (
            SELECT DISTINCT r.data 
            FROM reservas r 
            WHERE r.status = 'pendente' 
            AND r.created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ) 
        AND status = 'pendente'";

$conn->query($sql);

// Agora apagar as reservas expiradas
$delete_sql = "DELETE FROM reservas 
               WHERE status = 'pendente' 
               AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)";

$conn->query($delete_sql);

// Opcional: restaurar status para datas que não têm reservas
$sql_sync = "UPDATE agenda 
             SET status = 'ativo' 
             WHERE status IN ('pendente', 'reservado') 
             AND data NOT IN (
                 SELECT DISTINCT data 
                 FROM reservas 
                 WHERE status IN ('pendente', 'confirmado')
             )";

$conn->query($sql_sync);

$conn->close();
?>