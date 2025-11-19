<?php
// Script para sincronizar os dados entre 'reservas' e 'agenda'
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se é uma chamada de sincronização manual
$action = $_POST['action'] ?? '';

if ($action === 'sync_agenda') {
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
        echo json_encode([
            'success' => false, 
            'message' => 'Erro na sincronização: ' . $conn->error
        ]);
    }
} else {
    // Rotina padrão de verificação de limpeza de reservas antigas
    // Atualizar status da agenda para 'ativo' para reservas pendentes expiradas
    $sql = "UPDATE agenda 
            SET status = 'ativo' 
            WHERE data IN (
                SELECT DISTINCT r.data 
                FROM reservas r 
                WHERE r.status = 'pendente' 
                AND r.created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)  -- Exemplo: expiração após 30 minutos
            ) 
            AND status = 'pendente'";

    $conn->query($sql);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Verificação de limpeza de reservas expiradas concluída.'
    ]);
}

$conn->close();
?>