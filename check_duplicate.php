<?php
// Script para verificação de duplicidade no banco
require 'php/db_connect.php';

// Testar com um CPF e e-mail específicos
$test_email = $_GET['email'] ?? 'teste.teste.2025@exemplo.com';
$test_cpf = $_GET['cpf'] ?? '12345678901';

echo "Verificando duplicidade para:\n";
echo "Email: $test_email\n";
echo "CPF: $test_cpf\n\n";

// Verificar duplicidade
$stmt = $conn->prepare("SELECT id, email, cpf FROM usuarios WHERE email = ? OR cpf = ?");
$stmt->bind_param("ss", $test_email, $test_cpf);
$stmt->execute();
$result = $stmt->get_result();

echo "Registros encontrados: " . $result->num_rows . "\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Email: " . $row['email'] . " - CPF: " . $row['cpf'] . "\n";
    }
} else {
    echo "Nenhum registro duplicado encontrado.\n";
}

$stmt->close();
$conn->close();
?>