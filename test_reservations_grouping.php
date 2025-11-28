<?php
require 'php/db_connect.php';

// Simular um user_id para testes (vamos usar o user_id 46 que vimos nos resultados anteriores)
$user_id = 46;

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
    $mp_payment_id = $payment_links[$row['id']] ?? null;
    
    echo "Reserva ID: " . $row['id'] . " | ";
    echo "Data: " . $row['data'] . " | ";
    echo "Status: " . $row['status'] . " | ";
    echo "Grupo: " . ($row['reserva_grupo'] ?? 'NULL') . " | ";
    echo "MP Payment ID: " . ($mp_payment_id ?? 'NULL') . "\n";
}

$stmt->close();
$conn->close();
?>