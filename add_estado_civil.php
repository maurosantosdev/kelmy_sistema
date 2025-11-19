<?php
require 'php/db_connect.php';

// Verificar se a coluna estado_civil já existe
$result = $conn->query("SHOW COLUMNS FROM clientes LIKE 'estado_civil'");
if ($result->num_rows == 0) {
    // Adicionar a coluna estado_civil à tabela clientes
    $sql = "ALTER TABLE clientes ADD COLUMN estado_civil VARCHAR(50) NOT NULL AFTER cpf";
    
    if ($conn->query($sql) === TRUE) {
        echo "Coluna 'estado_civil' adicionada com sucesso à tabela 'clientes'.\n";
    } else {
        echo "Erro ao adicionar coluna: " . $conn->error . "\n";
    }
} else {
    echo "Coluna 'estado_civil' já existe na tabela 'clientes'.\n";
}

$conn->close();
?>