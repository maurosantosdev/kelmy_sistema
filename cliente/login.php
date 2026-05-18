<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e se o usuário ainda existe no banco de dados
if (isset($_SESSION['user_id'])) {
    // Conectar ao banco de dados para verificar se o usuário ainda existe
    require_once '../php/db_connect.php';

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Usuário ainda existe no banco, redirecionar para reserva
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php");
        exit();
    } else {
        // Usuário não existe mais no banco, limpar a sessão
        $_SESSION = array();

        // Se a sessão usa um cookie para persistência, exclua-o também
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }
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
                header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php?login=success");
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
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        #loginPageCliente header h1 {
            color: #34495e !important;
        }

        /* Estilos responsivos para o cabeçalho com nome do usuário */
        header.bg-white.text-dark.py-3 {
            padding: 0.75rem 0 !important;
        }

        header .h5 {
            font-size: 1rem;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.6rem 0 !important;
            }

            header .h5 {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.5rem 0 !important;
            }

            header .h5 {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 400px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.4rem 0 !important;
            }

            header .h5 {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 350px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.3rem 0 !important;
            }

            header .h5 {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.25rem 0 !important;
            }

            header .h5 {
                font-size: 0.7rem;
            }
        }

        /* Estilo para o container do header */
        .d-flex.justify-content-between.align-items-center {
            gap: 0.25rem;
        }

        @media (max-width: 400px) {
            .d-flex.justify-content-between.align-items-center {
                gap: 0.2rem;
            }
        }

        @media (max-width: 350px) {
            .d-flex.justify-content-between.align-items-center {
                gap: 0.15rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            .d-flex.justify-content-between.align-items-center {
                gap: 0.1rem;
            }
        }

        /* Estilo específico para o botão no header */
        header .btn.btn-light.btn-sm {
            min-height: 1.5rem;
            min-width: auto;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            header .btn.btn-light.btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            header .btn.btn-light.btn-sm {
                padding: 0.15rem 0.3rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 400px) {
            header .btn.btn-light.btn-sm {
                padding: 0.12rem 0.25rem;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 350px) {
            header .btn.btn-light.btn-sm {
                padding: 0.1rem 0.2rem;
                font-size: 0.65rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            header .btn.btn-light.btn-sm {
                min-height: 1.1rem;
                padding: 0.05rem 0.15rem !important;
                font-size: 0.6rem;
            }
        }

        /* Mobile-specific styles for the Cadastre-se button */
        @media (max-width: 768px) {
            .cadastre-btn {
                background-color: white !important;
                border: 1px solid #2c3e50 !important;
                color: #2c3e50 !important;
            }
        }
    </style>
</head>
<body>

<div id="loginPageCliente" class="container-fluid d-flex flex-column" style="min-height: 100vh;">
    <header class="bg-white text-dark py-3">
        <div class="container">
            <h1 class="text-center mb-0">Login</h1>
        </div>
    </header>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center" style="padding-bottom: 60px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-10">
                    <div class="card shadow">
                        <div class="card-body p-4" style="max-height: 70vh; overflow-y: auto;">
                            <h2 class="card-title text-center mb-4">Acesse sua Conta</h2>

                            <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
                            <div class="alert alert-danger" role="alert">
                                E-mail ou senha incorretos. Por favor, tente novamente.
                            </div>
                            <?php endif; ?>

                            <form id="loginClienteForm" method="POST">
                                <div class="mb-3">
                                    <label for="email_login" class="form-label">E-mail:</label>
                                    <input type="email" name="email_login" class="form-control" id="email_login" value="<?php echo isset($_GET['email_login']) ? htmlspecialchars($_GET['email_login']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="senha_login" class="form-label">Senha:</label>
                                    <input type="password" name="senha_login" class="form-control" id="senha_login" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" id="loginSubmitBtn" class="btn btn-primary btn-lg" style="background-color: #2c3e50; border-color: #2c3e50;">Entrar</button>
                                </div>
                            </form>
                        </div>

                        <div class="card-footer bg-light text-center py-3">
                            <p class="mb-0">Ainda não tem conta?
                                <a href="cadastro.php" class="btn btn-outline-primary btn-sm ms-2 cadastre-btn" style="border-color: #2c3e50; color: #2c3e50;">Cadastre-se</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="fixed-bottom bg-dark text-white py-2">
        <div class="container-fluid px-0">
            <nav class="row g-0 text-center">
                <div class="col-3 py-2">
                    <a href="../index.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-home mb-1"></i>
                        <small>Inicio</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="reserva.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-calendar-alt mb-1"></i>
                        <small>Reserve</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="suas_reservas.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-calendar mb-1"></i>
                        <small>Reservas</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="perfil.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-user mb-1"></i>
                        <small>Perfil</small>
                    </a>
                </div>
            </nav>
        </div>
    </footer>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="login.js?v=<?php echo time(); ?>"></script>

</body>
</html>