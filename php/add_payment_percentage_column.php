<?php
require 'db_connect.php';

// Adicionar coluna para rastrear o percentual do pagamento
$sql = "ALTER TABLE reservas ADD COLUMN payment_percentage INT DEFAULT 50";

if ($conn->query($sql) === TRUE) {
    echo "Coluna 'payment_percentage' adicionada à tabela 'reservas' com sucesso!\n";
    echo "A coluna foi configurada com valor padrão de 50% (metade do valor total como sinal)\n";
} else {
    echo "Erro ao adicionar coluna 'payment_percentage': " . $conn->error . "\n";
}

$conn->close();
?>