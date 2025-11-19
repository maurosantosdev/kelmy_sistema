<?php
require 'db_connect.php';

// Alterar a coluna status para incluir 'pendente' como opção
$sql = "ALTER TABLE agenda MODIFY COLUMN status ENUM('ativo', 'inativo', 'reservado', 'pendente') DEFAULT 'ativo'";

if ($conn->query($sql) === TRUE) {
    echo "Coluna 'status' da tabela 'agenda' atualizada com sucesso!\n";
} else {
    echo "Erro ao atualizar coluna 'status': " . $conn->error . "\n";
}

$conn->close();
?>