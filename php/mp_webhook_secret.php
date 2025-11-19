<?php
// Arquivo de configuração para a chave secreta do webhook do Mercado Pago
// Esta chave deve ser gerada no painel do Mercado Pago e deve corresponder exatamente

// Definir a chave secreta do webhook como variável de ambiente ou constante
if (!defined('MP_WEBHOOK_SECRET')) {
    // Primeira tentativa: variável de ambiente
    $env_secret = getenv('MP_WEBHOOK_SECRET');
    if ($env_secret && !empty($env_secret)) {
        define('MP_WEBHOOK_SECRET', $env_secret);
    } else {
        // Segunda tentativa: definir a chave secreta aqui (substitua pelo valor correto do seu painel MP)
        // PARA USO TEMPORÁRIO - VOCÊ DEVE SUBSTITUIR ESTE VALOR PELO SEU WEBHOOK SECRET REAL DO PAINEL DO MERCADO PAGO
        define('MP_WEBHOOK_SECRET', 'd27402fcea742f0b3d2b0fda166aa4bf5bfe726cb6f80f3ab225bf732d802bce');
    }
}
?>