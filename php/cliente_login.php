<?php
// Detectar se é uma requisição JAVASCRIPT antes de qualquer saída
$isJavascriptRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
                 (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ||
                 (!empty($_POST['email']) && !empty($_POST['senha']));

// Definir o tipo de conteúdo como JSON para requisições JAVASCRIPT
if ($isJavascriptRequest) {
    header('Content-Type: application/json');
}

// Incluir o arquivo de conexão com tratamento de erro
try {
    require 'db_connect.php';
} catch (Exception $e) {
    if ($isJavascriptRequest) {
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
    } else {
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php?error=1");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJavascriptRequest) {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    } else {
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php?error=1");
    }
    exit;
}

// Verificar se a sessão já existe (usuário já logado)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se é uma requisição para checar a sessão
if (isset($_POST['check_session']) && $_POST['check_session'] == '1') {
    if (isset($_SESSION['user_id'])) {
        if ($isJavascriptRequest) {
            $response = ['success' => true, 'message' => 'Usuário já está logado.'];
            echo json_encode($response);
        } else {
            header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php");
        }
    } else {
        if ($isJavascriptRequest) {
            $response = ['success' => false, 'message' => 'Usuário não está logado.'];
            echo json_encode($response);
        } else {
            header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php");
        }
    }
    exit;
}

// Se o usuário já estiver logado e não for uma requisição de verificação de sessão, retornar sucesso com mensagem informativa
if (isset($_SESSION['user_id'])) {
    if ($isJavascriptRequest) {
        $response = ['success' => true, 'message' => 'Usuário já está logado.'];
        echo json_encode($response);
    } else {
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php");
    }
    exit;
}

$response = ['success' => false, 'message' => ''];

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    $response['message'] = 'E-mail e senha são obrigatórios';
    if ($isJavascriptRequest) {
        echo json_encode($response);
    } else {
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php?error=1");
    }
    exit;
}

error_log("BANCO - Tentativa de login para email: $email");

// Consultar usuário no banco com todas as informações necessárias em uma única consulta
$stmt = $conn->prepare("SELECT id, email, senha, nome FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("BANCO - Nenhum usuário encontrado para o email: $email");
    // Para evitar timing attacks, vamos simular o tempo de verificação de senha mesmo quando o usuário não existe
    password_verify('dummy', '$2y$10$' . str_repeat('A', 22)); // Hash dummy para simular o tempo
    
    if ($isJavascriptRequest) {
        $response['message'] = 'E-mail ou senha incorretos';
        echo json_encode($response);
    } else {
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php?error=1");
    }
} else {
    $user = $result->fetch_assoc();

    // Verificar senha
    if (password_verify($senha, $user['senha'])) {
        error_log("BANCO - Login bem sucedido para o email: $email");

        // Regenerar ID da sessão para segurança
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nome'];

        if ($isJavascriptRequest) {
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso!';
            echo json_encode($response);
        } else {
            header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php");
            exit();
        }
    } else {
        error_log("BANCO - Senha incorreta para o email: $email");

        if ($isJavascriptRequest) {
            $response['message'] = 'E-mail ou senha incorretos';
            echo json_encode($response);
        } else {
            header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php?error=1");
            exit();
        }
    }
}

$stmt->close();
$conn->close();
?>