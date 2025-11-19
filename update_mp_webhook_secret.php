<?php
// Script para atualizar a chave secreta do webhook do Mercado Pago
if (isset($argv[1])) {
    $webhook_secret = $argv[1];
} else {
    echo "Uso: php update_mp_webhook_secret.php [WEBHOOK_SECRET]\n";
    echo "\nPara obter o WEBHOOK_SECRET:\n";
    echo "1. Acesse https://www.mercadopago.com.br/settings/credentials\n";
    echo "2. Vá até a seção 'Webhooks' na sua conta Mercado Pago\n";
    echo "3. Clique em 'Editar' ou gere um novo Webhook Secret\n";
    echo "4. Copie o valor fornecido e use como parâmetro deste script\n";
    echo "\nExemplo: php update_mp_webhook_secret.php abcdef123456789\n";
    exit(1);
}

// Ler o arquivo de configuração
$config_file = 'php/mp_webhook_secret.php';
$content = file_get_contents($config_file);

// Substituir o valor padrão pelo novo webhook secret
$pattern = "/define\('MP_WEBHOOK_SECRET', '.*'\);/";
$replacement = "define('MP_WEBHOOK_SECRET', '{$webhook_secret}');";

$new_content = preg_replace($pattern, $replacement, $content);

// Escrever o arquivo atualizado
file_put_contents($config_file, $new_content);

echo "Chave secreta do webhook atualizada com sucesso!\n";
echo "Novo Webhook Secret configurado: {$webhook_secret}\n";
echo "\nLembre-se de também configurar a URL do webhook no painel do Mercado Pago:\n";
echo "https://chacararecantodosossegorr.com.br/chacara_kelmy/webhook-mercado-pago.php\n";
?>