<?php
try {
    require 'db_connect.php';

    header('Content-Type: application/json');

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Obter dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $estado_civil = trim($_POST['estado_civil'] ?? '');
    $rua = trim($_POST['rua'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $data_nascimento = trim($_POST['data_nascimento'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Remover formatação de CPF, CEP e telefone para validação e armazenamento
    $cpf_limpo = preg_replace('/\D/', '', $cpf);
    $cep_limpo = preg_replace('/\D/', '', $cep);
    $telefone_limpo = preg_replace('/\D/', '', $telefone);

    // Validação dos dados
    error_log("UPDATE USER - Nome: '$nome', RG: '$rg', CPF original: '$cpf', CPF limpo: '$cpf_limpo', Estado Civil: '$estado_civil', Telefone original: '$telefone', Email: '$email'");

    if (empty($nome) || empty($rg) || empty($cpf_limpo) || empty($estado_civil) || empty($telefone_limpo) || empty($data_nascimento) || empty($email)) {
        $emptyFields = [];
        if (empty($nome)) $emptyFields[] = 'nome';
        if (empty($rg)) $emptyFields[] = 'rg';
        if (empty($cpf_limpo)) $emptyFields[] = 'cpf';
        if (empty($estado_civil)) $emptyFields[] = 'estado civil';
        if (empty($telefone_limpo)) $emptyFields[] = 'telefone';
        if (empty($data_nascimento)) $emptyFields[] = 'data de nascimento';
        if (empty($email)) $emptyFields[] = 'email';
        
        $emptyFieldList = implode(', ', $emptyFields);
        echo json_encode(['success' => false, 'message' => "Os seguintes campos obrigatórios estão vazios: $emptyFieldList"]);
        exit;
    }

    // Debug: verificar o conteúdo recebido
    error_log("DEBUG - Dados recebidos: " . print_r($_POST, true));

    // Validação do formato do e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Formato de e-mail inválido']);
        exit;
    }

    // Validação do CPF - agora usando a versão limpa
    if (!preg_match('/^\d{11}$/', $cpf_limpo)) {
        echo json_encode(['success' => false, 'message' => 'Formato de CPF inválido']);
        exit;
    }

    // Validação do CEP
    if (!preg_match('/^\d{8}$/', $cep_limpo)) {
        echo json_encode(['success' => false, 'message' => 'Formato de CEP inválido']);
        exit;
    }

    // Formatar CPF para verificação de duplicidade
    $cpf_formatado = $cpf_limpo;
    if (preg_match('/^\d{11}$/', $cpf_limpo)) {
        $cpf_formatado_db = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf_limpo);
    } else {
        $cpf_formatado_db = $cpf; // Usa o CPF como recebido se já estiver formatado
    }

    // Verificar duplicidade de e-mail (excluindo o usuário atual)
    $checkEmailStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $checkEmailStmt->bind_param("si", $email, $user_id);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();

    if ($checkEmailResult->num_rows > 0) {
        $checkEmailStmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado com outro usuário.']);
        exit;
    }
    $checkEmailStmt->close();

    // Verificar duplicidade de CPF (excluindo o usuário atual)
    $checkCpfStmt = $conn->prepare("SELECT id FROM usuarios WHERE (cpf = ? OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?) AND id != ?");
    $checkCpfStmt->bind_param("ssi", $cpf_formatado_db, $cpf_limpo, $user_id);
    $checkCpfStmt->execute();
    $checkCpfResult = $checkCpfStmt->get_result();

    if ($checkCpfResult->num_rows > 0) {
        $checkCpfStmt->close();
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Este CPF já está cadastrado com outro usuário.']);
        exit;
    }
    $checkCpfStmt->close();

    // Formatar CPF para armazenamento
    if (preg_match('/^\d{11}$/', $cpf_limpo)) {
        $cpf_formatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf_limpo);
    } else {
        $cpf_formatado = $cpf; // Usa o CPF como recebido se já estiver formatado
    }

    // Atualizar as informações do usuário
    $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, data_nascimento = ?, rg = ?, cpf = ?, estado_civil = ?, rua = ?, numero = ?, bairro = ?, cep = ?, cidade = ?, telefone = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssssssssssssi", $nome, $data_nascimento, $rg, $cpf_formatado, $estado_civil, $rua, $numero, $bairro, $cep_limpo, $cidade, $telefone_limpo, $email, $user_id);

    if ($stmt->execute()) {
        // Atualizar também as informações na sessão
        $_SESSION['user_name'] = $nome;
        $_SESSION['user_email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Erro no update_user_info.php: " . $e->getMessage());
    error_log("Erro no update_user_info.php - Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor: ' . $e->getMessage()]);
}
?>