<?php
require 'db_connect.php';

// Adicionar coluna para armazenar o tipo de porcentagem (50 ou 100)
$sql = "ALTER TABLE reservas ADD COLUMN tipo_porcentagem VARCHAR(3) DEFAULT '50'";

if ($conn->query($sql) === TRUE) {
    echo "Coluna 'tipo_porcentagem' adicionada à tabela 'reservas' com sucesso!\n";
    echo "A coluna foi configurada com valor padrão de '50' (metade do valor total como sinal)\n";
    
    // Atualizar os registros existentes com base no valor de payment_percentage
    $update_sql = "UPDATE reservas SET tipo_porcentagem = CASE 
                        WHEN payment_percentage = 100 THEN '100' 
                        ELSE '50' 
                      END 
                      WHERE tipo_porcentagem IS NULL OR tipo_porcentagem = ''";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "Registros existentes atualizados com base no campo payment_percentage\n";
    } else {
        echo "Erro ao atualizar registros existentes: " . $conn->error . "\n";
    }
} else {
    echo "Erro ao adicionar coluna 'tipo_porcentagem': " . $conn->error . "\n";
}

$conn->close();
?>