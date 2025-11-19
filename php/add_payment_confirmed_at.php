<?php
require 'db_connect.php';

// Adicionar coluna para rastrear o tempo de confirmação do pagamento
$sql = "ALTER TABLE reservas ADD COLUMN payment_confirmed_at DATETIME NULL";

if ($conn->query($sql) === TRUE) {
    echo "Coluna 'payment_confirmed_at' adicionada à tabela 'reservas' com sucesso!\n";
} else {
    echo "Erro ao adicionar coluna: " . $conn->error . "\n";
}

$conn->close();
?>