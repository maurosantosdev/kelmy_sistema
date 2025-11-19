<?php
require 'php/db_connect.php';

// Verificar a estrutura atual da tabela clientes
echo "Estrutura da tabela clientes:\n";
$result = $conn->query("DESCRIBE clientes");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']} ({$row['Type']}) - {$row['Null']}, {$row['Key']}, {$row['Extra']}\n";
}

echo "\nCampos adicionados recentemente:\n";
$check_estado_civil = $conn->query("SHOW COLUMNS FROM clientes LIKE 'estado_civil'");
if ($check_estado_civil->num_rows > 0) {
    echo "- estado_civil: adicionado com sucesso\n";
} else {
    echo "- estado_civil: NÃO encontrado\n";
}

$conn->close();
?>