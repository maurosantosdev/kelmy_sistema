<?php
// Teste de conectividade para verificar se o webhook está acessível
// Este script permite verificar se o Mercado Pago consegue acessar seu webhook

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$test_result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'php_version' => phpversion(),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'unknown',
    ],
    'client_info' => [
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    ],
    'headers' => [],
    'body' => null
];

// Capturar todos os cabeçalhos HTTP
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $header_name = str_replace('_', '-', substr($key, 5));
        $test_result['headers'][$header_name] = $value;
    }
}

// Capturar o body, se houver
$body = file_get_contents('php://input');
if (!empty($body)) {
    $test_result['body'] = $body;
}

echo json_encode($test_result, JSON_PRETTY_PRINT);

// Registrar o acesso para depuração
error_log("WEBHOOK-TEST - Acesso recebido em: " . $test_result['timestamp']);
error_log("WEBHOOK-TEST - IP: " . $test_result['client_info']['remote_addr']);
error_log("WEBHOOK-TEST - User-Agent: " . $test_result['client_info']['http_user_agent']);
error_log("WEBHOOK-TEST - Headers: " . print_r($test_result['headers'], true));
if (!empty($test_result['body'])) {
    error_log("WEBHOOK-TEST - Body: " . $test_result['body']);
}
?>