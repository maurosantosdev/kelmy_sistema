<?php
// Script para atualizar as credenciais do Mercado Pago para produção
if (isset($argv[1])) {
    $access_token = $argv[1];
    $public_key = $argv[2] ?? '';
    $client_id = $argv[3] ?? '';
    $client_secret = $argv[4] ?? '';
} else {
    echo "Uso: php update_mp_prod_creds.php [ACCESS_TOKEN] [PUBLIC_KEY] [CLIENT_ID] [CLIENT_SECRET]\n";
    exit(1);
}

// Ler o arquivo de configuração
$config_file = 'php/mp_env_config.php';
$content = file_get_contents($config_file);

// Substituir o token padrão pelo novo token
$pattern = '/define\(\'MP_ACCESS_TOKEN\', \'[^\']*\'\);/';
$replacement = "define('MP_ACCESS_TOKEN', '{$access_token}');";

$new_content = preg_replace($pattern, $replacement, $content);

// Escrever o arquivo atualizado
file_put_contents($config_file, $new_content);

echo "Credenciais de produção atualizadas com sucesso!\n";
echo "Novo Access Token configurado: {$access_token}\n";
?>