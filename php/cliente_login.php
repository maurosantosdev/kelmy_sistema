<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se a sessão já existe (usuário já logado)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se é uma requisição para checar a sessão
if (isset($_POST['check_session']) && $_POST['check_session'] == '1') {
    if (isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        $response = ['success' => true, 'message' => 'Usuário já está logado.'];
        echo json_encode($response);
    } else {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Usuário não está logado.'];
        echo json_encode($response);
    }
    exit;
}

// Se o usuário já estiver logado e não for uma requisição de verificação de sessão, retornar sucesso com mensagem informativa
if (isset($_SESSION['user_id'])) {
    // Verificar se a requisição veio via AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Requisição AJAX - retornar JSON com sucesso e mensagem informativa
        header('Content-Type: application/json');
        $response = ['success' => true, 'message' => 'Usuário já está logado.'];
        echo json_encode($response);
    } else {
        // Requisição tradicional - redirecionar para suas_reservas.php
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/suas_reservas.php");
        exit();
    }
    exit;
}

$response = ['success' => false, 'message' => ''];

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    header('Content-Type: application/json');
    $response['message'] = 'E-mail e senha são obrigatórios';
    echo json_encode($response);
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
    header('Content-Type: application/json');
    $response['message'] = 'E-mail ou senha incorretos';
    echo json_encode($response);
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
        
        // Verificar se a requisição veio via AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Requisição AJAX - retornar JSON
            header('Content-Type: application/json');
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso!';
            echo json_encode($response);
        } else {
            // Requisição tradicional - redirecionar
            header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/suas_reservas.php");
            exit();
        }
    } else {
        error_log("BANCO - Senha incorreta para o email: $email");
        
        // Verificar se a requisição veio via AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // Requisição AJAX - retornar JSON
            header('Content-Type: application/json');
            $response['message'] = 'E-mail ou senha incorretos';
            echo json_encode($response);
        } else {
            // Requisição tradicional - redirecionar de volta para login com erro
            header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/login.php?error=1");
            exit();
        }
    }
}

$stmt->close();
$conn->close();
?>