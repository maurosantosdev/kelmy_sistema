<?php
echo "<h1>Teste de Conexão com o Banco de Dados</h1>";

// Inclui as mesmas configurações de conexão do sistema
require 'db_connect.php';

// A variável $conn vem do arquivo db_connect.php
if ($conn && $conn->connect_error) {
    echo "<p style='color:red; font-weight:bold;'>Conexão Falhou: " . htmlspecialchars($conn->connect_error) . "</p>";
} else {
    echo "<p style='color:green; font-weight:bold;'>Conexão com o banco de dados 'chacara_kelmy' foi bem-sucedida!</p>";
}

// Fecha a conexão
if ($conn) {
    $conn->close();
}
?>