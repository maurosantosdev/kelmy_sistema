<?php
// Evitar iniciar sessão desnecessariamente no arquivo de conexão
// A sessão será iniciada apenas quando necessário nos arquivos que usam esta conexão

// $DB_URL = "localhost";
// $DB_USER = "root";
// $DB_PASSWORD = "SEADI#nstec@2050@";
// $DB_NAME = "chacara_kelmy";

$DB_URL = "127.0.0.1";  // Usar IP em vez de localhost para evitar problemas com socket
$DB_PORT = 3306;        // Definir porta explicitamente
$DB_USER = "maurosantos";
$DB_PASSWORD = "Casamento24!@#$";
$DB_NAME = "chacara_kelmy";

// Log de tentativa de conexão
error_log("BANCO - Tentando conectar ao banco: " . $DB_NAME . " em " . $DB_URL . ":" . $DB_PORT);

$conn = new mysqli($DB_URL, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    error_log("BANCO - Erro de conexão: " . $conn->connect_error);
    die("Erro de Conexão: " . $conn->connect_error);
} else {
    error_log("BANCO - Conexão bem sucedida ao banco: " . $DB_NAME);
}

// Função para log de consultas (se necessário para debug)
function log_query($query, $params = null) {
    $log_msg = "BANCO - Query executada: " . $query;
    if ($params) {
        $log_msg .= " com parâmetros: " . json_encode($params);
    }
    error_log($log_msg);
}
?>