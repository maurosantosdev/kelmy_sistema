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

$reservas_ids_json = $_POST['reservas_ids'] ?? '';

if (empty($reservas_ids_json)) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'message' => 'IDs das reservas são obrigatórios']);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

// Decodificar os IDs das reservas
$reservas_ids = json_decode($reservas_ids_json, true);

if (!$reservas_ids || !is_array($reservas_ids) || count($reservas_ids) === 0) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Formato de IDs das reservas inválido']);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

try {
    // Verificar se todas as reservas existem e pertencem ao usuário logado e estão pendentes
    $placeholders = str_repeat('?,', count($reservas_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, user_id, data, valor, status, payment_percentage, tipo_porcentagem FROM reservas WHERE id IN ($placeholders) AND user_id = ? ORDER BY data ASC");
    $params = array_merge($reservas_ids, [$_SESSION['user_id']]);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservas = [];
    $total_valor = 0;
    $reservas_nao_encontradas = [];

    // Verificar quais reservas existem e estão pendentes
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] !== 'pendente') {
            throw new Exception("A reserva {$row['id']} não está pendente e não pode ser paga separadamente");
        }
        $reservas[] = $row;
        $total_valor += floatval($row['valor']);
    }
    
    // Verificar se todas as reservas pedidas existem
    $reservas_ids_existentes = array_column($reservas, 'id');
    $reservas_ids_nao_encontradas = array_diff($reservas_ids, $reservas_ids_existentes);
    
    if (!empty($reservas_ids_nao_encontradas)) {
        throw new Exception("Reservas não encontradas ou não pertencem ao usuário: " . implode(', ', $reservas_ids_nao_encontradas));
    }
    
    if (empty($reservas)) {
        throw new Exception("Nenhuma reserva válida encontrada para pagamento");
    }
    
    // Configurar o SDK do Mercado Pago
    \MercadoPago\MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);
    
    // Criar o cliente de pagamento
    $client = new \MercadoPago\Client\Payment\PaymentClient();
    
    // Criar a requisição de pagamento
    $description = "Pagamento de " . count($reservas) . " reserva" . (count($reservas) > 1 ? 's' : '') . " - Chácara Recanto do Sossego";
    
    $request = [
        "transaction_amount" => $total_valor,
        "description" => $description,
        "payment_method_id" => "pix",
        "external_reference" => implode(',', $reservas_ids), // Usar os IDs das reservas existentes
        "notification_url" => defined('WEBHOOK_URL') ? WEBHOOK_URL : (defined('BASE_URL') ? BASE_URL . '/webhook-mercado-pago.php' : ''),
        "statement_descriptor" => "Chácara Recanto do Sossego",
        "payer" => [
            "type" => "customer",
            "email" => $_SESSION['user_email'] ?? 'cliente@chacara.com.br',
            "first_name" => $_SESSION['user_name'] ?? 'Cliente'
        ]
    ];
    
    // Criar o pagamento
    error_log("Tentando criar pagamento PIX para múltiplas reservas existentes com os seguintes dados: " . json_encode($request));
    $payment = $client->create($request);
    
    if (empty($payment->id)) {
        error_log("Erro ao criar pagamento no Mercado Pago para múltiplas reservas existentes. Resposta: " . json_encode($payment));
        throw new Exception('Erro ao criar cobrança no Mercado Pago: ID do pagamento não retornado ou resposta inválida. Detalhes: ' . json_encode($payment));
    }
    
    // Registrar imediatamente a associação entre o pagamento do Mercado Pago e as reservas
    $mp_payment_id = $payment->id ?? null;
    
    if (!empty($mp_payment_id) && !empty($reservas_ids)) {
        $reservation_ids_str = implode(',', $reservas_ids);
        $stmt_link = $conn->prepare("INSERT INTO mp_payment_links (mp_payment_id, reservation_ids) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE reservation_ids = VALUES(reservation_ids), updated_at = CURRENT_TIMESTAMP");
        if ($stmt_link) {
            $stmt_link->bind_param("ss", $mp_payment_id, $reservation_ids_str);
            if (!$stmt_link->execute()) {
                error_log("Erro ao registrar associação de pagamento MP para múltiplas reservas: " . $stmt_link->error);
            } else {
                error_log("Associação de pagamento MP registrada para múltiplas reservas: {$mp_payment_id} -> " . $reservation_ids_str);
            }
            $stmt_link->close();
        } else {
            error_log("Erro ao preparar query para registrar associação de pagamento MP para múltiplas reservas: " . $conn->error);
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
        error_log("Gerando QR Code base64 a partir do conteúdo do QR Code para múltiplas reservas");
        
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
            error_log("Erro ao gerar QR Code a partir do código para múltiplas reservas: " . $e->getMessage());
        }
        
        if (empty($payment_url) && !empty($payment->point_of_interaction->transaction_data->ticket_url)) {
            $payment_url = $payment->point_of_interaction->transaction_data->ticket_url;
        }
    }
    
    if (empty($qr_code_base64)) {
        error_log("QR Code base64 não disponível para as múltiplas reservas. Dados do pagamento: " . json_encode($payment));
        
        if (!empty($payment_url)) {
            error_log("Usando URL de pagamento alternativa para múltiplas reservas. URL: " . $payment_url);
        } else {
            throw new Exception('QR Code não foi gerado corretamente pelo Mercado Pago e nenhuma URL de pagamento alternativa está disponível. Tente novamente ou entre em contato com o suporte.');
        }
    }
    
    // O ID do pagamento
    $payment_id = $payment->id;
    
    // Log para debug
    error_log("Pagamento PIX gerado com sucesso para múltiplas reservas: IDs=" . implode(',', $reservas_ids) . ", Valor Total={$total_valor}, Quantidade=" . count($reservas));
    error_log("QR Code Base64 disponível: " . (empty($qr_code_base64) ? 'NÃO' : 'SIM'));
    error_log("QR Code texto disponível: " . (empty($qr_code) ? 'NÃO' : 'SIM'));
    error_log("URL de pagamento disponível: " . (empty($payment_url) ? 'NÃO' : 'SIM'));
    
    echo json_encode([
        'success' => true,
        'reservation_ids' => $reservas_ids,
        'reservation_count' => count($reservas),
        'reservations_data' => array_map(function($reserva) {
            return [
                'id' => $reserva['id'],
                'data' => $reserva['data'],
                'valor' => floatval($reserva['valor'])
            ];
        }, $reservas),
        'qr_code_base64' => $qr_code_base64,
        'qr_code' => $qr_code,
        'pix_key' => $qr_code, // Chave PIX para copiar e colar
        'payment_url' => $payment_url,
        'payment_id' => $payment_id,
        'status' => 'pendente',
        'valor_total' => $total_valor,
        'payment_percentage' => 100 // Pagamento completo das reservas
    ]);

} catch (Exception $e) {
    // Limpar qualquer conteúdo que possa ter sido enviado para o buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Erro ao criar pagamento PIX para múltiplas reservas existentes: " . $e->getMessage());
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