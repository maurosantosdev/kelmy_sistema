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

/* ================= VALIDAÇÕES ================= */

if (
    empty($nome) || empty($rg) || empty($cpf) || empty($estado_civil) ||
    empty($rua) || empty($numero) || empty($bairro) || empty($cep) ||
    empty($cidade) || empty($telefone) || empty($data_nascimento) ||
    empty($email) || empty($senha)
) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$|^\d{11}$/', $cpf)) {
    echo json_encode(['success' => false, 'message' => 'CPF inválido']);
    exit;
}

if (!preg_match('/^\d{8}$/', $cep)) {
    echo json_encode(['success' => false, 'message' => 'CEP inválido']);
    exit;
}

/* ================= DUPLICIDADE ================= */

$cpf_unformatted = preg_replace('/\D/', '', $cpf);

// Email
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado']);
    exit;
}
$stmt->close();

// CPF
$stmt = $conn->prepare("
    SELECT id FROM usuarios 
    WHERE cpf = ? OR REPLACE(REPLACE(cpf,'.',''),'-','') = ?
");
$stmt->bind_param("ss", $cpf, $cpf_unformatted);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'CPF já cadastrado']);
    exit;
}
$stmt->close();

/* ================= CADASTRO ================= */

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

if (preg_match('/^\d{11}$/', $cpf)) {
    $cpf = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

$stmt = $conn->prepare("
    INSERT INTO usuarios 
    (nome, data_nascimento, rg, cpf, estado_civil, rua, numero, bairro, cep, cidade, telefone, email, senha)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssssssss",
    $nome,
    $data_nascimento,
    $rg,
    $cpf,
    $estado_civil,
    $rua,
    $numero,
    $bairro,
    $cep,
    $cidade,
    $telefone,
    $email,
    $senha_hash
);

if ($stmt->execute()) {

    session_start();
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['user_name'] = $nome;
    $_SESSION['user_email'] = $email;
    $_SESSION['reload_reserva'] = true;

    echo json_encode([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso!',
        'redirect' => 'https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar']);
