<?php
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$response = ['success' => false, 'message' => ''];

$nome = $_POST['nome'] ?? '';
$rg = $_POST['rg'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$estado_civil = $_POST['estado_civil'] ?? '';
$rua = $_POST['rua'] ?? '';
$numero = $_POST['numero'] ?? '';
$bairro = $_POST['bairro'] ?? '';
$cep = $_POST['cep'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$data_nascimento = $_POST['data_nascimento'] ?? '';
$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';

// Validação dos dados
if (empty($nome) || empty($rg) || empty($cpf) || empty($estado_civil) || empty($rua) || empty($numero) || empty($bairro) || empty($cep) || empty($cidade) || empty($telefone) || empty($data_nascimento) || empty($email) || empty($senha)) {
    $response['message'] = 'Todos os campos são obrigatórios';
    echo json_encode($response);
    exit;
}

// Validação do formato do e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Formato de e-mail inválido';
    echo json_encode($response);
    exit;
}

// Validação do CPF
if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/', $cpf)) {
    $response['message'] = 'Formato de CPF inválido';
    echo json_encode($response);
    exit;
}

// Validação do CEP
if (!preg_match('/^\d{8}$/', $cep)) {
    $response['message'] = 'Formato de CEP inválido';
    echo json_encode($response);
    exit;
}

// Primeiro verificar se o e-mail ou CPF já existe (abordagem segura)
$cpf_unformatted = preg_replace('/\D/', '', $cpf);

// Verificar duplicidade de e-mail
$checkEmailStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$checkEmailStmt->bind_param("s", $email);
$checkEmailStmt->execute();
$checkEmailResult = $checkEmailStmt->get_result();

if ($checkEmailResult->num_rows > 0) {
    $checkEmailStmt->close();
    $conn->close();
    $response['message'] = 'Este e-mail já está cadastrado.';
    echo json_encode($response);
    exit;
}
$checkEmailStmt->close();

// Verificar duplicidade de CPF (com e sem formatação)
$checkCpfStmt = $conn->prepare("SELECT id FROM usuarios WHERE cpf = ? OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?");
$checkCpfStmt->bind_param("ss", $cpf, $cpf_unformatted);
$checkCpfStmt->execute();
$checkCpfResult = $checkCpfStmt->get_result();

if ($checkCpfResult->num_rows > 0) {
    $checkCpfStmt->close();
    $conn->close();
    $response['message'] = 'Este CPF já está cadastrado.';
    echo json_encode($response);
    exit;
}
$checkCpfStmt->close();

// Se chegamos até aqui, não há duplicidade - podemos cadastrar com segurança
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Formatar CPF se necessário
if (preg_match('/^\d{11}$/', $cpf)) {
    $cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Agora sim, fazer a inserção
$stmt = $conn->prepare("INSERT INTO usuarios (nome, data_nascimento, rg, cpf, estado_civil, rua, numero, bairro, cep, cidade, telefone, email, senha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssssss", $nome, $data_nascimento, $rg, $cpf, $estado_civil, $rua, $numero, $bairro, $cep, $cidade, $telefone, $email, $senha_hash);

if ($stmt->execute()) {
    // Obter o ID do usuário recém-criado
    $user_id = $conn->insert_id;
    
    // Iniciar a sessão e definir os dados do usuário
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $nome;
    $_SESSION['user_email'] = $email;
    
    $response['success'] = true;
    $response['message'] = 'Cadastro realizado com sucesso!';
    
    // Verificar se a requisição veio via AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Requisição AJAX - retornar JSON
        echo json_encode($response);
    } else {
        // Requisição tradicional - redirecionar
        header("Location: https://chacararecantodosossegorr.com.br/chacara_kelmy/cliente/reserva.php");
        exit();
    }
} else {
    $response['message'] = 'Erro ao cadastrar cliente: ' . $stmt->error;
    
    // Verificar se a requisição veio via AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Requisição AJAX - retornar JSON
        echo json_encode($response);
    } else {
        // Requisição tradicional - mostrar erro ou redirecionar de volta
        header("Location: https://chacararecantodosossegorr.com.br/chacara_kelmy/cliente/cadastro.php?error=" . urlencode($response['message']));
        exit();
    }
}

$stmt->close();
$conn->close();
?>