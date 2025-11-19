<?php
// Script para atualizar o Access Token do Mercado Pago
if (isset($argv[1])) {
    $new_token = $argv[1];
} else {
    echo "Uso: php update_mp_token.php [SEU_ACCESS_TOKEN]\n";
    exit(1);
}

// Ler o arquivo de configuração
$config_file = 'php/mp_env_config.php';
$content = file_get_contents($config_file);

// Substituir o token padrão pelo novo token
$pattern = '/define\(\'MP_ACCESS_TOKEN\', \'[^\']*\'\);/';
$replacement = "define('MP_ACCESS_TOKEN', '{$new_token}');";

$new_content = preg_replace($pattern, $replacement, $content);

// Escrever o arquivo atualizado
file_put_contents($config_file, $new_content);

echo "Access Token atualizado com sucesso!\n";
echo "Novo token configurado: {$new_token}\n";
?>