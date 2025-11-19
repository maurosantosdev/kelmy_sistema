<?php
// Arquivo para inicializar o SDK do Mercado Pago
// Este arquivo deve ser incluído antes de usar qualquer funcionalidade do MP

// Verificar se o autoload existe
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require $autoload_path;
} else {
    // Nesse caso, vamos apenas logar o erro em vez de morrer abruptamente
    error_log('SDK do Mercado Pago não instalado. Execute: composer require mercadopago/dx-php');
    // Não faz die() aqui pois pode ser chamado por endpoints que retornam JSON
    if (headers_sent() || !defined('CONTENT_TYPE_SET')) {
        header('Content-Type: application/json');
        define('CONTENT_TYPE_SET', true);
    }
    echo json_encode(['success' => false, 'message' => 'SDK do Mercado Pago não instalado']);
    exit;
}

// Carregar configurações antes de inicializar o SDK
require_once 'mp_config.php';

// Nenhuma inicialização adicional necessária, pois o mp_config.php já faz isso
?>