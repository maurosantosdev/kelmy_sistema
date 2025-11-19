<?php
require 'db_connect.php';

// Script para criar a tabela de usuários se não existir
$sql_usuarios = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(15),
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_usuarios) === TRUE) {
    echo "Tabela 'usuarios' está pronta para uso!\n";
} else {
    echo "Erro ao criar tabela usuarios: " . $conn->error . "\n";
}

// Script para criar a tabela de reservas se não existir
$sql_reservas = "CREATE TABLE IF NOT EXISTS reservas (
    id VARCHAR(50) PRIMARY KEY,
    user_id INT NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'confirmado', 'cancelado') DEFAULT 'pendente',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
)";

if ($conn->query($sql_reservas) === TRUE) {
    echo "Tabela 'reservas' está pronta para uso!\n";
} else {
    echo "Erro ao criar tabela reservas: " . $conn->error . "\n";
}

// Adicionar coluna status à tabela agenda se não existir
$checkColumn = "SHOW COLUMNS FROM agenda LIKE 'status'";
$result = $conn->query($checkColumn);

if ($result->num_rows == 0) {
    $addColumn = "ALTER TABLE agenda ADD COLUMN status ENUM('ativo', 'reservado', 'pendente') DEFAULT 'ativo'";
    if ($conn->query($addColumn) === TRUE) {
        echo "\nColuna 'status' adicionada à tabela 'agenda'.\n";
    } else {
        echo "\nErro ao adicionar coluna 'status': " . $conn->error . "\n";
    }
} else {
    echo "\nColuna 'status' já existe na tabela 'agenda'.\n";
}

$conn->close();
?>