<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Remover todas as variáveis de sessão
$_SESSION = array();

// Se a sessão usa um cookie para persistência, exclua-o também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir completamente a sessão
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso']);
?>