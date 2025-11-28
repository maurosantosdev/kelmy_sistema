<?php
// Simular ambiente de sessão para teste
session_start();
$_SESSION['user_id'] = 46;  // ID do usuário que temos no banco de dados
$_SESSION['user_name'] = 'Test User';

require 'php/db_connect.php';

// Código copiado e adaptado de get_user_reservations.php para teste
$user_id = $_SESSION['user_id'];

// Consultar reservas do usuário
$stmt = $conn->prepare("SELECT id, data, valor, status, observacoes, created_at, payment_confirmed_at, payment_percentage, tipo_porcentagem, reserva_grupo FROM reservas WHERE user_id = ? ORDER BY data DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservas = [];

// Primeiro, obter todas as associações de pagamento
$payment_links = [];
$payment_links_stmt = $conn->prepare("SELECT mp_payment_id, reservation_ids FROM mp_payment_links");
$payment_links_stmt->execute();
$payment_links_result = $payment_links_stmt->get_result();

while ($payment_row = $payment_links_result->fetch_assoc()) {
    $reservation_ids = explode(',', $payment_row['reservation_ids']);
    foreach ($reservation_ids as $reservation_id) {
        $reservation_id = trim($reservation_id);
        // Apenas incluir este ID de pagamento se a reserva pertencer ao usuário atual
        $check_user_stmt = $conn->prepare("SELECT user_id FROM reservas WHERE id = ?");
        $check_user_stmt->bind_param("s", $reservation_id);
        $check_user_stmt->execute();
        $check_result = $check_user_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_row = $check_result->fetch_assoc();
            if ($check_row['user_id'] == $user_id) {
                $payment_links[$reservation_id] = $payment_row['mp_payment_id'];
            }
        }
        $check_user_stmt->close();
    }
}

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

    // Verificar se o contrato para esta reserva já foi assinado e obter a data de assinatura
    $contrato_stmt = $conn->prepare("SELECT data_assinatura FROM contratos_assinados WHERE user_id = ? AND reserva_id = ?");
    $contrato_stmt->bind_param("is", $user_id, $row['id']);
    $contrato_stmt->execute();
    $contrato_result = $contrato_stmt->get_result();
    $contrato_row = $contrato_result->fetch_assoc();
    $contrato_assinado = $contrato_result->num_rows > 0;
    $data_assinatura = $contrato_assinado ? $contrato_row['data_assinatura'] : null;

    // Obter o ID do pagamento do Mercado Pago associado a esta reserva (se existir)
    $mp_payment_id = $payment_links[$row['id']] ?? null;

    $reservas[] = [
        'id' => $row['id'],
        'data' => $row['data'],
        'data_reserva' => $row['data'], // Also provide data_reserva for compatibility with JavaScript sorting
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
        'reserva_grupo' => $row['reserva_grupo'], // Grupo de reserva para agrupar diárias
        'contrato_assinado' => $contrato_assinado,
        'data_assinatura' => $data_assinatura ? (new DateTime($data_assinatura))->format('d/m/Y H:i:s') : null,
        'mp_payment_id' => $mp_payment_id // ID do pagamento do Mercado Pago associado a esta reserva
    ];
}

// Mostrar os resultados
echo "Reservas encontradas:\n";
foreach ($reservas as $reserva) {
    echo "ID: " . $reserva['id'] . " | Data: " . $reserva['data_formatada'] . " | Status: " . $reserva['status'] . " | MP Payment ID: " . ($reserva['mp_payment_id'] ?? 'NULL') . "\n";
}

// Agora vamos simular a lógica de agrupamento
echo "\nAgrupando por mp_payment_id:\n";

$gruposPorPagamento = [];
$gruposProcessados = [];

// Primeiro, agrupar as reservas que têm o mesmo mp_payment_id
foreach ($reservas as $reserva) {
    // Se a reserva tem um pagamento associado e ainda não foi processada
    if ($reserva['mp_payment_id'] && !in_array($reserva['mp_payment_id'], $gruposProcessados)) {
        // Encontrar todas as reservas com o mesmo ID de pagamento
        $reservasMesmoPagamento = array_filter($reservas, function($r) use ($reserva) {
            return $r['mp_payment_id'] === $reserva['mp_payment_id'];
        });

        if (count($reservasMesmoPagamento) > 0) {
            $gruposPorPagamento[] = array_values($reservasMesmoPagamento); // array_values para reindexar
            $gruposProcessados[] = $reserva['mp_payment_id'];
        }
    }
}

// Depois, adicionar reservas que não têm pagamento associado como grupos individuais
foreach ($reservas as $reserva) {
    if (!$reserva['mp_payment_id'] && !in_array($reserva['id'], array_column($gruposPorPagamento, 'id'))) {
        $reservasUnicas = [$reserva];
        $gruposPorPagamento[] = $reservasUnicas;
    }
}

// Verificar se há grupos individuais corretos
foreach ($gruposPorPagamento as $idx => $grupo) {
    echo "Grupo " . ($idx + 1) . ":\n";
    foreach ($grupo as $reserva) {
        echo "  - ID: " . $reserva['id'] . " | Data: " . $reserva['data_formatada'] . " | MP Payment ID: " . ($reserva['mp_payment_id'] ?? 'NULL') . "\n";
    }
    echo "\n";
}

// Verificar quais IDs de pagamento foram usados
$paymentIds = [];
foreach ($reservas as $reserva) {
    if ($reserva['mp_payment_id']) {
        if (!in_array($reserva['mp_payment_id'], $paymentIds)) {
            $paymentIds[] = $reserva['mp_payment_id'];
        }
    }
}

echo "Total de grupos de pagamento distintos: " . count($paymentIds) . "\n";
echo "Total de reservas individuais (sem pagamento): " . (count($reservas) - count(array_filter($reservas, function($r) { return $r['mp_payment_id'] !== null; }))) . "\n";
?>