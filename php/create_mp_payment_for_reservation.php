<?php
ob_start(); // Iniciar output buffering para garantir que apenas JSON seja retornado

require 'db_connect.php';
require 'mp_init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    ob_end_flush(); // Enviar o buffer e terminar o script
    exit;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

$reserva_id = $_POST['reserva_id'] ?? '';

if (empty($reserva_id)) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'message' => 'ID da reserva é obrigatório']);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

try {
    // Verificar se a reserva existe e pertence ao usuário logado
    $stmt = $conn->prepare("SELECT id, user_id, data, valor, status, payment_percentage, tipo_porcentagem FROM reservas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("si", $reserva_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Reserva não encontrada ou não pertence ao usuário");
    }

    $reserva = $result->fetch_assoc();
    
    // Verificar se a reserva está pendente
    if ($reserva['status'] !== 'pendente') {
        throw new Exception("Apenas reservas pendentes podem ter o pagamento gerado");
    }
    
    $reservation_id = $reserva['id'];
    $reservation_data = $reserva['data'];
    $reservation_value = floatval($reserva['valor']);
    
    // Configurar o SDK do Mercado Pago
    \MercadoPago\MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);
    
    // Criar o cliente de pagamento
    $client = new \MercadoPago\Client\Payment\PaymentClient();
    
    // Criar a requisição de pagamento
    $request = [
        "transaction_amount" => $reservation_value,
        "description" => "Pagamento reserva Chácara - {$reservation_data}",
        "payment_method_id" => "pix",
        "external_reference" => $reservation_id, // Usar o ID da reserva existente
        "notification_url" => defined('WEBHOOK_URL') ? WEBHOOK_URL : (defined('BASE_URL') ? BASE_URL . '/webhook-mercado-pago.php' : ''),
        "statement_descriptor" => "Chácara Recanto do Sossego",
        "payer" => [
            "type" => "customer",
            "email" => $_SESSION['user_email'] ?? 'cliente@chacara.com.br',
            "first_name" => $_SESSION['user_name'] ?? 'Cliente'
        ]
    ];
    
    // Criar o pagamento
    error_log("Tentando criar pagamento PIX para reserva existente com os seguintes dados: " . json_encode($request));
    $payment = $client->create($request);
    
    if (empty($payment->id)) {
        error_log("Erro ao criar pagamento no Mercado Pago para reserva existente. Resposta: " . json_encode($payment));
        throw new Exception('Erro ao criar cobrança no Mercado Pago: ID do pagamento não retornado ou resposta inválida. Detalhes: ' . json_encode($payment));
    }
    
    // Registrar imediatamente a associação entre o pagamento do Mercado Pago e a reserva
    $mp_payment_id = $payment->id ?? null;
    
    if (!empty($mp_payment_id) && !empty($reservation_id)) {
        $stmt_link = $conn->prepare("INSERT INTO mp_payment_links (mp_payment_id, reservation_ids) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE reservation_ids = VALUES(reservation_ids), updated_at = CURRENT_TIMESTAMP");
        if ($stmt_link) {
            $stmt_link->bind_param("ss", $mp_payment_id, $reservation_id);
            if (!$stmt_link->execute()) {
                error_log("Erro ao registrar associação de pagamento MP para reserva existente: " . $stmt_link->error);
            } else {
                error_log("Associação de pagamento MP registrada para reserva existente: {$mp_payment_id} -> " . $reservation_id);
            }
            $stmt_link->close();
        } else {
            error_log("Erro ao preparar query para registrar associação de pagamento MP para reserva existente: " . $conn->error);
        }
    }
    
    // Verificar se o QR Code está disponível no campo correto para PIX
    if (!isset($payment->point_of_interaction) || 
        !isset($payment->point_of_interaction->transaction_data)) {
        
        error_log("Dados de interação de pagamento não encontrados na resposta do Mercado Pago. Resposta completa: " . json_encode($payment));
        
        // Para contas PF, pode haver uma estrutura diferente, vamos verificar
        $payment_debug = json_decode(json_encode($payment), true);
        error_log("Estrutura completa do pagamento para depuração: " . print_r($payment_debug, true));
        
        throw new Exception('Estrutura de dados incompleta do Mercado Pago para gerar QR Code PIX');
    }
    
    // Retornar informações para o frontend
    $qr_code_base64 = '';
    $qr_code = '';
    $payment_url = '';
    
    if (isset($payment->point_of_interaction) && 
        isset($payment->point_of_interaction->transaction_data)) {
        $qr_code_base64 = $payment->point_of_interaction->transaction_data->qr_code_base64 ?? '';
        $qr_code = $payment->point_of_interaction->transaction_data->qr_code ?? '';
    }
    
    if (isset($payment->transaction_details)) {
        $payment_url = $payment->transaction_details->external_resource_url ?? $payment->point_of_interaction->transaction_data->ticket_url ?? '';
    }
    
    // Verificar se o QR Code foi gerado corretamente
    if (empty($qr_code_base64) && !empty($qr_code)) {
        // Converter o código PIX em base64 para exibição
        error_log("Gerando QR Code base64 a partir do conteúdo do QR Code para reserva existente");
        
        try {
            // Gerar QR Code com biblioteca PHP
            require_once __DIR__ . '/../vendor/autoload.php';
            if (class_exists('Endroid\\QrCode\\QrCode')) {
                $qrCodeClass = 'Endroid\\QrCode\\QrCode';
                $writerClass = 'Endroid\\QrCode\\Writer\\PngWriter';
                
                $qrCode = new $qrCodeClass($qr_code);
                $qrCode->setSize(300);
                $qrCode->setMargin(10);
                
                $writer = new $writerClass();
                $result = $writer->write($qrCode);
                
                $qrCodeData = $result->getString();
                $qr_code_base64 = base64_encode($qrCodeData);
            } else {
                error_log("Classe Endroid\\\\QrCode\\\\QrCode não encontrada");
            }
        } catch (Exception $e) {
            error_log("Erro ao gerar QR Code a partir do código para reserva existente: " . $e->getMessage());
        }
        
        if (empty($payment_url) && !empty($payment->point_of_interaction->transaction_data->ticket_url)) {
            $payment_url = $payment->point_of_interaction->transaction_data->ticket_url;
        }
    }
    
    if (empty($qr_code_base64)) {
        error_log("QR Code base64 não disponível para a reserva existente. Dados do pagamento: " . json_encode($payment));
        
        if (!empty($payment_url)) {
            error_log("Usando URL de pagamento alternativa para reserva existente. URL: " . $payment_url);
        } else {
            throw new Exception('QR Code não foi gerado corretamente pelo Mercado Pago e nenhuma URL de pagamento alternativa está disponível. Tente novamente ou entre em contato com o suporte.');
        }
    }
    
    // O ID do pagamento
    $payment_id = $payment->id;
    
    // Log para debug
    error_log("Pagamento PIX gerado com sucesso para reserva existente: ID={$reservation_id}, Valor={$reservation_value}, Data={$reservation_data}");
    error_log("QR Code Base64 disponível: " . (empty($qr_code_base64) ? 'NÃO' : 'SIM'));
    error_log("QR Code texto disponível: " . (empty($qr_code) ? 'NÃO' : 'SIM'));
    error_log("URL de pagamento disponível: " . (empty($payment_url) ? 'NÃO' : 'SIM'));
    
    echo json_encode([
        'success' => true,
        'reservation_id' => $reservation_id,
        'reservation_data' => $reservation_data,
        'reservation_value' => $reservation_value,
        'qr_code_base64' => $qr_code_base64,
        'qr_code' => $qr_code,
        'pix_key' => $qr_code, // Chave PIX para copiar e colar
        'payment_url' => $payment_url,
        'payment_id' => $payment_id,
        'status' => 'pendente',
        'valor' => $reservation_value,
        'payment_percentage' => 100 // Pagamento completo da reserva existente
    ]);

} catch (Exception $e) {
    // Limpar qualquer conteúdo que possa ter sido enviado para o buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Erro ao criar pagamento PIX para reserva existente: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if ($conn) {
    $conn->close();
}

// Certificar-se de que o output buffering é finalizado corretamente
if (ob_get_level()) {
    ob_end_flush();
}
?>