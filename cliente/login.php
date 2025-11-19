<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    // Se o usuário já estiver logado, redirecionar para a página de reserva
    header("Location: https://chacararecantodosossegorr.com.br/chacara_kelmy/cliente/reserva.php");
    exit();
}

// Processar login via GET parameters (for direct URL login)
if (isset($_GET['email_login']) && isset($_GET['senha_login'])) {
    $email = $_GET['email_login'];
    $senha = $_GET['senha_login'];
    
    // Validate and sanitize inputs
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Include the login processing logic directly
        require_once '../php/db_connect.php';
        
        // Consultar usuário no banco
        $stmt = $conn->prepare("SELECT id, email, senha, nome FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verificar senha
            if (password_verify($senha, $user['senha'])) {
                // Login bem-sucedido
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['nome'];
                
                // Redirecionar para a página de reserva com um parâmetro para limpar a URL
                header("Location: https://chacararecantodosossegorr.com.br/chacara_kelmy/cliente/reserva.php?login=success");
                exit();
            }
        }
        
        // Login failed - add error parameter to URL without sensitive data
        $redirect_url = "login.php?error=1";
        header("Location: $redirect_url");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="../assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div data-role="page" id="loginPageCliente">
    <div data-role="header" data-position="fixed">
        <h1>Login</h1>
    </div>

    <div role="main" class="ui-content">

        <div class="card">
            <h2>Login</h2>
            <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="ui-body ui-body-e" style="margin-bottom: 10px;">
                <p style="color: red;">E-mail ou senha incorretos. Por favor, tente novamente.</p>
            </div>
            <?php endif; ?>
            <form id="loginClienteForm">
                <label for="email_login">E-mail:</label>
                <input type="email" name="email_login" id="email_login" value="<?php echo isset($_GET['email_login']) ? htmlspecialchars($_GET['email_login']) : ''; ?>" required>
                <label for="senha_login">Senha:</label>
                <input type="password" name="senha_login" id="senha_login" required>
                <button type="submit" class="ui-btn ui-btn-b" id="loginSubmitBtn">Entrar</button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="cadastro.php" class="ui-btn">Ainda não tem conta? Cadastre-se</a>
        </div>

    </div>

    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-icon="home">Inicio</a></li>
                <li><a href="reserva.php" data-icon="grid">Reserve</a></li>
                <li><a href="suas_reservas.php" data-icon="calendar">Reservas</a></li>
                <li><a href="perfil.php" data-icon="user">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/jquery.mobile-1.4.5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="login.js?v=<?php echo time(); ?>"></script>

</body>
</html>