<?php
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$response = ['success' => false, 'message' => ''];

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$date = $_POST['date'] ?? '';
$observacoes = $_POST['observacoes'] ?? '';
$valor = $_POST['valor'] ?? '';

if (empty($date) || empty($valor)) {
    $response['message'] = 'Data e valor são obrigatórios';
    echo json_encode($response);
    exit;
}

// Começar transação
$conn->begin_transaction();

try {
    // Verificar se a data já está reservada ou pendente
    $stmt = $conn->prepare("SELECT status FROM agenda WHERE data = ? FOR UPDATE");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Data não encontrada na agenda');
    }
    
    $row = $result->fetch_assoc();
    if ($row['status'] !== 'ativo') {
        throw new Exception('Esta data não está mais disponível');
    }
    
    // Atualizar status para pendente (aguardando pagamento)
    $updateStmt = $conn->prepare("UPDATE agenda SET status = 'pendente' WHERE data = ?");
    $updateStmt->bind_param("s", $date);
    if (!$updateStmt->execute()) {
        throw new Exception('Erro ao atualizar status da agenda');
    }
    
    // Gerar um ID de reserva único
    $reservation_id = uniqid('res_' . date('YmdHis'));

    // Generate a random group identifier
    $reserva_grupo = bin2hex(random_bytes(8));

    // Inserir reserva pendente no banco
    $insertStmt = $conn->prepare("INSERT INTO reservas (id, user_id, data, valor, status, observacoes, created_at, reserva_grupo) VALUES (?, ?, ?, ?, 'pendente', ?, NOW(), ?)");
    $status = 'pendente';
    $insertStmt->bind_param("ssssss", $reservation_id, $user_id, $date, $valor, $observacoes, $reserva_grupo);
    
    if (!$insertStmt->execute()) {
        throw new Exception('Erro ao criar reserva');
    }
    
    // Confirmar transação
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = 'Reserva pendente criada com sucesso';
    $response['reservation_id'] = $reservation_id;
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>