<?php
// Script para testar o webhook com dados mais realistas

// Criar uma requisição que simula a estrutura de um webhook real do Mercado Pago
// Vamos usar um ID de reserva real do seu sistema para testar

require 'php/db_connect.php';

// Buscar uma reserva pendente para usar no teste
$stmt = $conn->prepare("SELECT id, external_reference FROM reservas WHERE status = 'pendente' ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $reservation_id = $row['id'];
    echo "Usando reserva real para teste: $reservation_id\n";
    
    // Simular um webhook real do Mercado Pago
    $webhook_data = [
        'action' => 'payment.updated',
        'api_version' => 'v1',
        'data' => [
            'id' => '50838133487' // ID de exemplo (será um ID real do MP quando o pagamento for feito)
        ],
        'date_created' => date('c'),
        'id' => 123456,
        'live_mode' => true,
        'type' => 'payment',
        'user_id' => 123456789
    ];
    
    echo "Dados do webhook que seriam enviados:\n";
    echo json_encode($webhook_data, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Para testar o webhook com dados reais, execute no terminal:\n";
    echo "curl -k -X POST https://chacararecantodosossegorr.com.br/repo_limpo/webhook-mercado-pago.php \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -H \"User-Agent: MercadoPago IPN v1.0\" \\\n";
    echo "  -d '" . json_encode($webhook_data) . "'\n\n";
    
} else {
    echo "Nenhuma reserva pendente encontrada para teste.\n";
    echo "Faça uma reserva real primeiro para testar o webhook com dados reais.\n";
    
    // Mesmo assim, criar um exemplo com um ID de reserva possível
    $example_reservation_id = 'res_6908c034050f57.84588181'; // Peguei um ID do log anterior
    echo "Usando ID de exemplo para demonstração: $example_reservation_id\n";
    
    $webhook_data = [
        'action' => 'payment.updated',
        'api_version' => 'v1',
        'data' => [
            'id' => '50838133487'
        ],
        'date_created' => date('c'),
        'id' => 123456,
        'live_mode' => true,
        'type' => 'payment',
        'user_id' => 123456789
    ];
    
    echo "Dados do webhook que seriam enviados:\n";
    echo json_encode($webhook_data, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Para testar o webhook com dados reais, execute no terminal:\n";
    echo "curl -k -X POST https://chacararecantodosossegorr.com.br/repo_limpo/webhook-mercado-pago.php \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -H \"User-Agent: MercadoPago IPN v1.0\" \\\n";
    echo "  -d '" . json_encode($webhook_data) . "'\n\n";
}

$stmt->close();
$conn->close();
?>