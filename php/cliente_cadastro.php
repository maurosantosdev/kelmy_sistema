<?php
// Prevent direct access to this file - redirect to the form page
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_POST)) {
    header("Location: ../cliente/cadastro.php");
    exit();
}

require 'db_connect.php';

// Verificar se é uma requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Define headers only when we're sure we're returning JSON
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Headers already set above for all POST requests

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
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

// Log para verificar se o campo confirmar_senha está presente
error_log("DEBUG - Campo confirmar_senha existe: " . (isset($_POST['confirmar_senha']) ? 'SIM' : 'NÃO'));
error_log("DEBUG - Campo confirmar_senha valor: '" . $confirmar_senha . "'");
error_log("DEBUG - Campo senha valor: '" . $senha . "'");

// Log para debug - verificar quais dados foram recebidos
error_log("DEBUG - Nome recebido: " . $nome);
error_log("DEBUG - Email recebido: " . $email);
error_log("DEBUG - CPF recebido: " . $cpf);
error_log("DEBUG - Numero recebido: " . $numero);
error_log("DEBUG - Confirmar senha recebida: " . $confirmar_senha);
error_log("DEBUG - Todos os campos recebidos: " . print_r($_POST, true));

/* ================= VALIDAÇÕES ================= */

// Debug: verificar quais campos estão vazios
error_log("DEBUG - Campos recebidos - nome: '$nome', rg: '$rg', estado_civil: '$estado_civil', rua: '$rua', numero: '$numero', bairro: '$bairro', cidade: '$cidade', data_nascimento: '$data_nascimento', email: '$email', senha: '[oculto]', confirmar_senha: '[oculto]'");

if (
    empty($nome) || empty($rg) || empty($cpf) || empty($estado_civil) ||
    empty($rua) || empty($numero) || empty($bairro) ||
    empty($cidade) || empty($data_nascimento) ||
    empty($email) || empty($senha) || empty($confirmar_senha)
) {
    $missing_fields = [];
    if (empty($nome)) $missing_fields[] = 'nome';
    if (empty($rg)) $missing_fields[] = 'rg';
    if (empty($cpf)) $missing_fields[] = 'cpf';
    if (empty($estado_civil)) $missing_fields[] = 'estado_civil';
    if (empty($rua)) $missing_fields[] = 'rua';
    if (empty($numero)) $missing_fields[] = 'numero';
    if (empty($bairro)) $missing_fields[] = 'bairro';
    if (empty($cidade)) $missing_fields[] = 'cidade';
    if (empty($data_nascimento)) $missing_fields[] = 'data_nascimento';
    if (empty($email)) $missing_fields[] = 'email';
    if (empty($senha)) $missing_fields[] = 'senha';
    if (empty($confirmar_senha)) $missing_fields[] = 'confirmar_senha';

    error_log("DEBUG - Campos ausentes: " . implode(', ', $missing_fields));

    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios. Campos ausentes: ' . implode(', ', $missing_fields)]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

// Formatando o CPF para consistência (se vier sem pontos e traço, adiciona)
if (!empty($cpf)) {
    $cpf_clean = preg_replace('/\D/', '', $cpf); // Remove tudo que não é dígito
    if (strlen($cpf_clean) == 11) {
        $cpf = substr($cpf_clean, 0, 3) . '.' . substr($cpf_clean, 3, 3) . '.' . substr($cpf_clean, 6, 3) . '-' . substr($cpf_clean, 9, 2);
    }

    if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf)) {
        echo json_encode(['success' => false, 'message' => 'CPF inválido']);
        exit;
    }
}

if ($senha !== $confirmar_senha) {
    echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
    exit;
}


/* ================= DUPLICIDADE ================= */

// Debug: Verificar quantos registros existem na tabela
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios");
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
error_log("DEBUG - Total de registros na tabela usuarios: " . $countRow['total']);

// Email
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

error_log("DEBUG - Email verificado: " . $email . ", Resultados encontrados: " . $stmt->num_rows);

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado']);
    exit;
}
$stmt->close();

// CPF - apenas verificar se não estiver vazio
if (!empty($cpf)) {
    $cpf_unformatted = preg_replace('/\D/', '', $cpf);

    $stmt = $conn->prepare("
        SELECT id FROM usuarios
        WHERE cpf = ? OR REPLACE(REPLACE(cpf,'.',''),'-','') = ?
    ");
    $stmt->bind_param("ss", $cpf, $cpf_unformatted);
    $stmt->execute();
    $stmt->store_result();

    error_log("DEBUG - CPF verificado: " . $cpf . " ou " . $cpf_unformatted . ", Resultados encontrados: " . $stmt->num_rows);

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'CPF já cadastrado']);
        exit;
    }
    $stmt->close();
}

/* ================= CADASTRO ================= */

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Preparar a consulta SQL com os campos opcionais
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
    $cpf,  // Pode ser vazio
    $estado_civil,
    $rua,
    $numero,
    $bairro,
    $cep,  // Pode ser vazio
    $cidade,
    $telefone,  // Pode ser vazio
    $email,
    $senha_hash
);

if ($stmt->execute()) {

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['user_id'] = $conn->insert_id;  // Corrigido: usar $conn->insert_id para obter o ID do último insert
    $_SESSION['user_name'] = $nome;
    $_SESSION['user_email'] = $email;
    $_SESSION['reload_reserva'] = true;

    // Always return JSON response since both pages use AJAX
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso!',
        'redirect' => '../cliente/reserva.php'
    ]);
    exit;
} else {
    // Em caso de erro na inserção
    error_log("ERRO - Falha ao inserir usuário: " . $stmt->error);

    // Always return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao cadastrar usuário: ' . $stmt->error
    ]);
    exit;
}

$stmt->close();
$conn->close();
