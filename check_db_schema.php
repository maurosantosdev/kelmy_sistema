<?php
require 'php/db_connect.php';

// Função para obter informações das tabelas
function getTableInfo($conn, $tableName) {
    echo "=== Tabela: $tableName ===\n";
    
    // Obter estrutura da tabela
    $result = $conn->query("DESCRIBE $tableName");
    if ($result) {
        echo "Colunas:\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']}) - {$row['Null']}, {$row['Key']}, {$row['Extra']}\n";
        }
    } else {
        echo "Erro ao descrever a tabela: " . $conn->error . "\n";
    }
    
    // Obter contagem de registros
    $countResult = $conn->query("SELECT COUNT(*) as total FROM $tableName");
    if ($countResult) {
        $countRow = $countResult->fetch_assoc();
        echo "Total de registros: {$countRow['total']}\n";
    }
    
    echo "\n";
}

// Obter lista de tabelas
$tablesResult = $conn->query("SHOW TABLES");
echo "Tabelas no banco de dados:\n";
$tableNames = [];
while ($row = $tablesResult->fetch_row()) {
    $tableName = $row[0];
    $tableNames[] = $tableName;
    echo "- $tableName\n";
}

echo "\n";

// Obter informações de cada tabela
foreach ($tableNames as $tableName) {
    getTableInfo($conn, $tableName);
}

$conn->close();
?>