<?php
// Configurações do Mercado Pago para Conta PF
require_once 'mp_env_config.php'; // Carregar configurações de ambiente

// Verificar se o SDK do Mercado Pago está instalado
if (!class_exists('MercadoPago\MercadoPagoConfig')) {
    // Nesse caso, vamos apenas logar o erro em vez de morder abruptamente
    error_log('SDK do Mercado Pago não encontrada. Execute: composer require mercadopago/dx-php');
    // Não faz die() aqui pois pode ser chamado por endpoints que retornam JSON
    if (headers_sent() || !defined('CONTENT_TYPE_SET')) {
        header('Content-Type: application/json');
        define('CONTENT_TYPE_SET', true);
    }
    echo json_encode(['success' => false, 'message' => 'SDK do Mercado Pago não encontrada']);
    exit;
}

// Validação de credenciais (para contas PF, o token deve ter formato específico)
if (MP_ACCESS_TOKEN === 'TEST-6953179459495619-100515-9acc4c2819f863ec65074eb7a12ec822-586609742' || empty(MP_ACCESS_TOKEN)) {
    error_log("Aviso: Access Token do Mercado Pago não configurado. O sistema não funcionará até que você configure o token real.");
}

// Configurar o SDK com o novo método
\MercadoPago\MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

// O modo sandbox é tratado automaticamente ou não é necessário no novo SDK
?>