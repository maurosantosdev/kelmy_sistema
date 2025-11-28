<?php
// Simular ambiente de sessão para teste
session_start();
$_SESSION['user_id'] = 46;  // Usando o mesmo ID que temos nos dados do banco
$_SESSION['user_name'] = 'Test User';

// Capturar a saída do get_user_reservations.php
ob_start();
require 'php/get_user_reservations.php';
$output = ob_get_clean();

// Agora decodificar o JSON e mostrar de forma formatada
$response = json_decode($output, true);

if ($response && isset($response['reservations'])) {
    echo "Total de reservas retornadas: " . count($response['reservations']) . "\n\n";
    
    foreach ($response['reservations'] as $reserva) {
        echo "ID: " . $reserva['id'] . "\n";
        echo "Data: " . $reserva['data_formatada'] . "\n";
        echo "Status: " . $reserva['status'] . "\n";
        echo "MP Payment ID: " . ($reserva['mp_payment_id'] ?? 'NULL') . "\n";
        echo "---\n";
    }
    
    // Contar quantos IDs de pagamento únicos existem
    $payment_ids = array_filter(array_column($response['reservations'], 'mp_payment_id'));
    $unique_payment_ids = array_unique($payment_ids);
    echo "\nIDs de pagamento únicos encontrados: " . count($unique_payment_ids) . "\n";
    foreach ($unique_payment_ids as $id) {
        echo "- " . $id . "\n";
    }
} else {
    echo "Erro: Não foi possível obter as reservas\n";
    echo "Saída do backend: " . $output . "\n";
}
?>