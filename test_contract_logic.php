<?php
// Test script to verify the contract display logic

require 'php/db_connect.php';

// Test values
$user_id = 1; // Example user ID
$payment_percentage = 50;
$test_date = '2025-11-26';

echo "Testing contract display logic implementation...\n";

// Check if payment_percentage and tipo_porcentagem columns exist and work
$stmt = $conn->prepare("SELECT id, data, valor, status, payment_percentage, tipo_porcentagem FROM reservas WHERE user_id = ? LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Testing reservation data fetch with payment_percentage:\n";
while ($row = $result->fetch_assoc()) {
    echo "- ID: {$row['id']}, Data: {$row['data']}, Valor: R$ {$row['valor']}, Status: {$row['status']}, Payment %: {$row['payment_percentage']}, Tipo Porcentagem: {$row['tipo_porcentagem']}\n";
}

// Test signed contracts function
function getSignedContractDatesForUser($conn, $user_id, $payment_percentage = null) {
    $sql = "SELECT r.data FROM reservas r 
            JOIN contratos_assinados ca ON r.id = ca.reserva_id 
            WHERE r.user_id = ?";
    
    $params = [$user_id];
    $types = "i";
    
    if ($payment_percentage !== null) {
        $sql .= " AND (r.payment_percentage = ? OR r.tipo_porcentagem = ?)";
        $params[] = $payment_percentage;
        $params[] = strval($payment_percentage);
        $types .= "is";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $signed_dates = [];
    while ($row = $result->fetch_assoc()) {
        $signed_dates[] = $row['data'];
    }
    
    $stmt->close();
    return $signed_dates;
}

$signed_dates = getSignedContractDatesForUser($conn, $user_id, $payment_percentage);
echo "\nSigned contract dates for user $user_id with $payment_percentage% payment: " . implode(', ', $signed_dates) . "\n";

// Test the consecutive day logic
echo "\nTesting consecutive day logic...\n";
$current_reservations = [
    ['data' => '2025-11-28', 'payment_percentage' => 50],
    ['data' => '2025-11-29', 'payment_percentage' => 50]
];

$current_reservation_dates = array_map(function($reserva) {
    return new DateTime($reserva['data']);
}, $current_reservations);

$signed_date_objects = array_map(function($date) {
    return new DateTime($date);
}, $signed_dates);

echo "Current reservation dates: ";
foreach ($current_reservation_dates as $date) {
    echo $date->format('Y-m-d') . " ";
}
echo "\n";

echo "Signed dates: ";
foreach ($signed_date_objects as $date) {
    echo $date->format('Y-m-d') . " ";
}
echo "\n";

// Check if current dates extend signed dates
if (!empty($signed_date_objects) && !empty($current_reservation_dates)) {
    usort($signed_date_objects, function($a, $b) {
        return $a->getTimestamp() - $b->getTimestamp();
    });
    
    $last_signed_date = end($signed_date_objects);
    $first_current_date = $current_reservation_dates[0];
    
    $expected_date = clone $last_signed_date;
    $expected_date->modify('+1 day');
    
    if ($first_current_date->format('Y-m-d') == $expected_date->format('Y-m-d')) {
        echo "The current dates are a continuation of the signed dates!\n";
        $all_reservation_dates = array_merge($signed_date_objects, $current_reservation_dates);
        usort($all_reservation_dates, function($a, $b) {
            return $a->getTimestamp() - $b->getTimestamp();
        });
        $total_diarias = count($all_reservation_dates);
        echo "Total diarias to display: $total_diarias\n";
    } else {
        echo "The current dates are NOT a continuation of the signed dates.\n";
        $total_diarias = count($current_reservation_dates);
        echo "Total diarias to display: $total_diarias\n";
    }
}

echo "\nAll tests completed successfully!\n";

$conn->close();
?>