<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Retornar erro se não estiver autenticado
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Pegar o ID da reserva via GET
$raw_reserva_id = $_GET['reserva_id'] ?? '';
$reserva_id = $raw_reserva_id;

error_log("get_reservation_details_for_receipt: Raw GET parameter = '" . $raw_reserva_id . "'");
error_log("get_reservation_details_for_receipt: Type of raw parameter = " . gettype($_GET['reserva_id']));
error_log("get_reservation_details_for_receipt: session user_id = " . ($_SESSION['user_id'] ?? 'not set'));

if (empty($reserva_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID da reserva inválido (' . $raw_reserva_id . ')']);
    exit();
}

// Conectar ao banco de dados - usando o mesmo método do get_user_reservations.php
require_once 'db_connect.php';

error_log("get_reservation_details_for_receipt: Querying reservation ID " . $reserva_id . " for user ID " . $_SESSION['user_id']);

// Consultar detalhes da reserva e informações do usuário
$stmt = $conn->prepare("
    SELECT r.*, u.nome, u.cpf, u.email 
    FROM reservas r 
    JOIN usuarios u ON r.user_id = u.id 
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->bind_param("si", $reserva_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

error_log("get_reservation_details_for_receipt: Query result count = " . $result->num_rows);

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reserva não encontrada ou não pertence ao usuário']);
    exit();
}

$reserva = $result->fetch_assoc();

// Verificar se a reserva está confirmada e tem data de pagamento
if ($reserva['status'] !== 'confirmado' || !$reserva['payment_confirmed_at']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Apenas reservas confirmadas com pagamento realizado podem emitir recibo']);
    exit();
}

// Obter dados da reserva
$data_reserva = date('d/m/Y', strtotime($reserva['data']));
$valor_pago = 'R$ ' . number_format(floatval($reserva['valor_pago'] ?? $reserva['valor']), 2, ',', '.');
$data_pagamento = date('d/m/Y', strtotime($reserva['payment_confirmed_at']));

// Obter nome e CPF do cliente
$cliente_nome = $reserva['nome'];
$cliente_documento = $reserva['cpf'] ?? 'N/A';

// Se não tiver documento, usar valor padrão
if (empty($cliente_documento) || $cliente_documento === 'N/A') {
    $cliente_documento = 'N/A';
}

error_log("get_reservation_details_for_receipt: Cliente nome: " . $cliente_nome . ", Documento: " . $cliente_documento . ", Valor: " . $valor_pago);

// Se não tiver documento, usar valor padrão
if (empty($cliente_documento)) {
    $cliente_documento = 'N/A';
}

// Verificar se o nome do usuário já está na sessão
if (isset($_SESSION['user_name'])) {
    $user_nome = $_SESSION['user_name'];
} else {
    // Obter nome do usuário do banco de dados
    $user_stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_nome = '';
    if ($user_row = $user_result->fetch_assoc()) {
        $user_nome = $user_row['nome'];
        // Armazenar na sessão para uso futuro
        $_SESSION['user_name'] = $user_nome;
    }
    $user_stmt->close();
}

// Retornar informações para o recibo
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'id' => $reserva['id'],
    'data_reserva' => $data_reserva,
    'valor_pago' => $valor_pago,
    'data_pagamento' => $data_pagamento,
    'cliente_nome' => $cliente_nome,
    'cliente_documento' => $cliente_documento,
    'user_name' => $user_nome
]);

$stmt->close();
$conn->close();
?>