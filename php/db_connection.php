<?php
// Configurações do banco de dados - usando as mesmas do arquivo db_connect.php
$host = 'localhost';
$dbname = 'chacara_kelmy';
$username = 'maurosantos';
$password = 'Casamento24!@#$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>