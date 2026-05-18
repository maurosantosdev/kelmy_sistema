<?php
// Roteamento simples para o servidor embutido do PHP
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|svg|woff|woff2)$/i', $_SERVER["REQUEST_URI"])) {
    // Serve arquivos estáticos diretamente
    return false;
} else {
    // Redireciona todas as outras requisições para index.php
    require __DIR__ . '/index.php';
}
