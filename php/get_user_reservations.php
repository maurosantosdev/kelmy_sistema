<?php
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

// Consultar reservas do usuário
$stmt = $conn->prepare("SELECT id, data, valor, status, observacoes, created_at, payment_confirmed_at, payment_percentage, tipo_porcentagem FROM reservas WHERE user_id = ? ORDER BY data DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservas = [];
$recent_status_change = false; // Flag para verificar se houve mudança de status recente

while ($row = $result->fetch_assoc()) {
    // Formatar data
    $date = new DateTime($row['data']);
    $formatted_date = $date->format('d/m/Y');
    
    // Formatar valor
    $formatted_valor = 'R$ ' . number_format($row['valor'], 2, ',', '.');
    
    // Formatar status
    $status_formatado = '';
    switch($row['status']) {
        case 'pendente':
            $status_formatado = 'Pagamento pendente';
            break;
        case 'confirmado':
            $status_formatado = 'Confirmado';
            break;
        case 'cancelado':
            $status_formatado = 'Cancelado';
            break;
        default:
            $status_formatado = ucfirst($row['status']);
    }
    
    // Verificar se houve mudança de status recente (nos últimos 5 minutos, por exemplo)
    // Vamos verificar se o campo payment_confirmed_at foi preenchido recentemente
    if ($row['status'] === 'confirmado' && !empty($row['payment_confirmed_at'])) {
        $payment_confirmed_at = new DateTime($row['payment_confirmed_at']);
        $now = new DateTime();
        $interval = $now->diff($payment_confirmed_at);
        
        // Considerar mudança recente se payment_confirmed_at for nos últimos 5 minutos
        if ($interval->i < 5 && $interval->h === 0 && $interval->d === 0) {
            $recent_status_change = true;
        }
    }
    
    // Verificar se o contrato para esta reserva já foi assinado e obter a data de assinatura
    $contrato_stmt = $conn->prepare("SELECT data_assinatura FROM contratos_assinados WHERE user_id = ? AND reserva_id = ?");
    $contrato_stmt->bind_param("is", $user_id, $row['id']);
    $contrato_stmt->execute();
    $contrato_result = $contrato_stmt->get_result();
    $contrato_row = $contrato_result->fetch_assoc();
    $contrato_assinado = $contrato_result->num_rows > 0;
    $data_assinatura = $contrato_assinado ? $contrato_row['data_assinatura'] : null;
    
    $reservas[] = [
        'id' => $row['id'],
        'data' => $row['data'],
        'data_formatada' => $formatted_date,
        'valor' => $row['valor'],
        'valor_formatado' => $formatted_valor,
        'status' => $row['status'],
        'status_formatado' => $status_formatado,
        'observacoes' => $row['observacoes'] ? htmlspecialchars($row['observacoes']) : '',
        'created_at' => $row['created_at'],
        'payment_confirmed_at' => $row['payment_confirmed_at'] ? (new DateTime($row['payment_confirmed_at']))->format('Y-m-d H:i:s') : null, // Formatando a data de confirmação
        'payment_percentage' => $row['payment_percentage'] ?? 50, // Padrão 50% se não definido
        'tipo_porcentagem' => $row['tipo_porcentagem'] ?? '50', // Padrão '50' se não definido
        'contrato_assinado' => $contrato_assinado,
        'data_assinatura' => $data_assinatura ? (new DateTime($data_assinatura))->format('d/m/Y H:i:s') : null
    ];
}

// Verificar se o nome do usuário já está na sessão
if (isset($_SESSION['user_name'])) {
    $user_nome = $_SESSION['user_name'];
} else {
    // Obter nome do usuário do banco de dados
    $user_stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
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

echo json_encode([
    'success' => true,
    'reservations' => $reservas,
    'recent_status_change' => $recent_status_change,
    'user_name' => $user_nome
]);

$stmt->close();
$conn->close();
?>