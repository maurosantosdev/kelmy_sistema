<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Remover todas as variáveis de sessão
$_SESSION = array();

// Destruir completamente a sessão
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso']);
?>