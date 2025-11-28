<?php
// Script para simular um webhook real do Mercado Pago com os dados corretos

// Dados que simulam um webhook real do Mercado Pago após um pagamento aprovado
$payload = [
    'action' => 'payment.updated',
    'api_version' => 'v1',
    'data' => [
        'id' => '50838133487' // ID fictício que causaria falha na API (como nos logs)
    ],
    'date_created' => date('c'),
    'id' => 123456,
    'live_mode' => true,
    'type' => 'payment',
    'user_id' => 123456789,
    'data' => [
        'external_reference' => 'res_6908db8e7d2d14.60951134,res_6908db8e7d2e51.37166973' // IDs existentes no banco
    ]
];

echo "Simulando webhook com payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Preparar para enviar via cURL para o webhook
$url = 'https://chacararecantodosossegorr.com.br/repo_limpo/webhook-mercado-pago.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: MercadoPago IPN v1.0',
    'Content-Length: ' . strlen(json_encode($payload))
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Apenas para teste local
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Resposta do webhook (HTTP {$http_code}):\n";
echo $response . "\n\n";

echo "Agora verificando se os status foram atualizados...\n";

// Verificar diretamente no banco de dados
require 'php/db_connect.php';

echo "\nStatus atual das reservas:\n";
$stmt = $conn->prepare("SELECT id, data, status, payment_confirmed_at FROM reservas WHERE id IN ('res_6908db8e7d2d14.60951134', 'res_6908db8e7d2e51.37166973') ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "Reserva: {$row['id']}, Data: {$row['data']}, Status: {$row['status']}, Payment Confirmed At: {$row['payment_confirmed_at']}\n";
}

echo "\nStatus atual da agenda:\n";
$stmt_agenda = $conn->prepare("SELECT data, status FROM agenda WHERE data IN ('2025-10-16', '2025-10-17') ORDER BY data");
$stmt_agenda->execute();
$result_agenda = $stmt_agenda->get_result();

while ($row = $result_agenda->fetch_assoc()) {
    echo "Data: {$row['data']}, Status: {$row['status']}\n";
}

$stmt->close();
$stmt_agenda->close();
$conn->close();
?>