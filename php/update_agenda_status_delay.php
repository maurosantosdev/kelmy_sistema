<?php
// Script para atualizar os status da agenda após o delay de 5 segundos
require 'db_connect.php';

// Buscar reservas confirmadas cujo payment_confirmed_at foi há pelo menos 5 segundos
// e que ainda têm status 'pendente' na agenda
$sql = "UPDATE agenda 
        SET status = 'reservado' 
        WHERE data IN (
            SELECT DISTINCT r.data 
            FROM reservas r 
            WHERE r.status = 'confirmado' 
            AND r.payment_confirmed_at IS NOT NULL
            AND TIMESTAMPDIFF(SECOND, r.payment_confirmed_at, NOW()) >= 5
        ) 
        AND status = 'pendente'";

if ($conn->query($sql)) {
    $affected_rows = $conn->affected_rows;
    error_log("Script de atualização de agenda executado. {$affected_rows} linhas atualizadas.");
} else {
    error_log("Erro ao executar script de atualização de agenda: " . $conn->error);
}

$conn->close();
?>