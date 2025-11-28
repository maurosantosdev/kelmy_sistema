<?php
// Arquivo de configuração para ambiente de produção (conta PF)
// Este arquivo pode ser usado para definir variáveis de ambiente

// Definir o Access Token como variável de ambiente ou constante
if (!defined('MP_ACCESS_TOKEN')) {
    // Primeira tentativa: variável de ambiente
    $env_token = getenv('MP_ACCESS_TOKEN');
    if ($env_token && !empty($env_token)) {
        define('MP_ACCESS_TOKEN', $env_token);
    } else {
        // Segunda tentativa: usar o valor padrão (seu token real)
        define('MP_ACCESS_TOKEN', 'APP_USR-1894657220266098-102310-a775dc4ce721c45004e627996c267349-126742231');
    }
}

// Modo de sandbox (pode ser definido via variável de ambiente também)
if (!defined('MP_SANDBOX')) {
    $env_sandbox = getenv('MP_SANDBOX');
    if ($env_sandbox !== false) {
        define('MP_SANDBOX', filter_var($env_sandbox, FILTER_VALIDATE_BOOLEAN));
    } else {
        define('MP_SANDBOX', false); // Agora está em produção real
    }
}

// Verificar se está usando HTTPS (importante para contas PF no MP)
if (!defined('IS_SECURE_CONNECTION')) {
    define('IS_SECURE_CONNECTION', 
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    );
}

// URL base do sistema
$protocol = IS_SECURE_CONNECTION ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Verificar se estamos em ambiente CLI ou Web
if (php_sapi_name() === 'cli') {
    // Em ambiente CLI, usar a configuração padrão
    $base_url_path = '/repo_limpo';
} else {
    // Em ambiente web, obter o caminho base corretamente
    // O caminho base deve ser baseado na raiz do projeto (repo_limpo), não no diretório do script
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if (strpos($script_dir, '/repo_limpo') === 0) {
        // Se o script estiver em um subdiretório de repo_limpo, manter apenas repo_limpo
        $base_url_path = '/repo_limpo';
    } else {
        // Caso contrário, usar o dirname normal
        $base_url_path = $script_dir !== '/' ? $script_dir : '';
    }
}

// Definir constantes com os valores calculados
if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . $host . $base_url_path);
}

// Webhook URL (deve ser HTTPS para contas PF no MP)
if (!defined('WEBHOOK_URL')) {
    define('WEBHOOK_URL', BASE_URL . '/webhook-mercado-pago.php');
}

// Validações para contas PF
if (!IS_SECURE_CONNECTION) {
    error_log("Aviso: O sistema está sendo executado sem HTTPS. Webhooks do Mercado Pago exigem HTTPS.");
}
?>