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

$dates_json = $_POST['dates'] ?? '';
$observacoes = $_POST['observacoes'] ?? '';
$total_valor = floatval($_POST['total_valor'] ?? 0);
$payment_percentage = intval($_POST['payment_percentage'] ?? 100); // 50 ou 100

if (empty($dates_json)) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Datas são obrigatórias']);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

// Decodificar as datas
$dates = json_decode($dates_json, true);

if (!$dates || !is_array($dates) || count($dates) === 0) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(['success' => false, 'message' => 'Formato de datas inválido']);
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

// Calcular o valor a pagar com base na porcentagem
$payment_amount = ($total_valor * $payment_percentage) / 100;

// Primeira etapa: Verificar disponibilidade e validar dados sem bloquear
try {
    foreach ($dates as $date) {
        // Obter o preço da agenda para esta data
        $stmt = $conn->prepare("SELECT preco FROM agenda WHERE data = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Data {$date} não encontrada na agenda");
        }

        $row = $result->fetch_assoc();
        $preco = floatval($row['preco']);

        if ($preco <= 0) {
            throw new Exception("Valor da reserva para {$date} inválido");
        }
        
        // Verificar se o usuário já tem uma reserva pendente para esta data
        $stmt_check = $conn->prepare("SELECT id FROM reservas WHERE user_id = ? AND data = ? AND status = 'pendente'");
        $stmt_check->bind_param("is", $_SESSION['user_id'], $date);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            throw new Exception("Você já tem uma reserva pendente para {$date}");
        }
        
        // Verificar se o usuário já tem uma reserva confirmada para esta data
        $stmt_check2 = $conn->prepare("SELECT id FROM reservas WHERE user_id = ? AND data = ? AND status = 'confirmado'");
        $stmt_check2->bind_param("is", $_SESSION['user_id'], $date);
        $stmt_check2->execute();
        $result_check2 = $stmt_check2->get_result();
        
        if ($result_check2->num_rows > 0) {
            throw new Exception("Você já tem uma reserva confirmada para {$date}");
        }
        
        // Verificar se a data ainda está disponível (SEM bloqueio FOR UPDATE nesta etapa)
        $stmt = $conn->prepare("SELECT status FROM agenda WHERE data = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Data {$date} não encontrada na agenda");
        }
        
        $row = $result->fetch_assoc();
        if ($row['status'] === 'reservado' || $row['status'] === 'pendente') {
            throw new Exception("A data {$date} não está mais disponível");
        }
    }
    
    // Gerar os IDs das reservas antes de criar o pagamento no Mercado Pago
    // Precisamos dos IDs para usar como external_reference no pagamento
    $reservation_ids = [];
    $reservation_ids_original = []; // Armazenar os IDs originais para usar na associação
    foreach ($dates as $date) {
        $reservation_id = uniqid('res_', true);
        $reservation_ids[] = $reservation_id;
        $reservation_ids_original[] = $reservation_id; // Salvar cópia original
    }
    
    // Segunda etapa: Criar pagamento no Mercado Pago
    // Configurar o SDK do Mercado Pago
    \MercadoPago\MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);
    
    // Criar o cliente de pagamento
    $client = new \MercadoPago\Client\Payment\PaymentClient();
    
    // Criar a requisição de pagamento com o valor baseado na porcentagem
    $description = "Reserva Chácara RS - " . count($dates) . " dias";
    if(count($dates) == 1) {
        $description = "Reserva Chácara RS - {$dates[0]}";
    }
    
    // Criar a requisição de pagamento
    $request = [
        "transaction_amount" => $payment_amount,
        "description" => $description,
        "payment_method_id" => "pix",
        "external_reference" => implode(',', $reservation_ids), // Usar os IDs reais das reservas
        "notification_url" => defined('WEBHOOK_URL') ? WEBHOOK_URL : (defined('BASE_URL') ? BASE_URL . '/webhook-mercado-pago.php' : 'https://chacararecantodosossegorr.com.br/repo_limpo/webhook-mercado-pago.php'),
        "statement_descriptor" => "Chácara Recanto do Sossego",
        "payer" => [
            "type" => "customer",
            "email" => $_SESSION['user_email'] ?? 'cliente@chacara.com.br',
            "first_name" => $_SESSION['user_name'] ?? 'Cliente'
        ]
    ];

    // Adicionando log para debug
    error_log("WEBHOOK DEBUG - notification_url sendo usada: " . $request["notification_url"]);
    error_log("WEBHOOK DEBUG - external_reference sendo usada: " . $request["external_reference"]);
    
    // Criar o pagamento
    error_log("Tentando criar pagamento no Mercado Pago com os seguintes dados: " . json_encode($request));
    $payment = $client->create($request);
    
    if (empty($payment->id)) {
        error_log("Erro ao criar pagamento no Mercado Pago. Resposta: " . json_encode($payment));
        throw new Exception('Erro ao criar cobrança no Mercado Pago: ID do pagamento não retornado ou resposta inválida. Detalhes: ' . json_encode($payment));
    }
    
    // Registrar imediatamente a associação entre o pagamento do Mercado Pago e as reservas
    // Fazemos isso imediatamente após a criação do pagamento para garantir persistência
    $mp_payment_id = $payment->id ?? null;
    
    // Adicionando logs mais detalhados para depuração
    error_log("DEBUG - Valor de payment->id imediatamente após criação: " . ($mp_payment_id ?? 'NULL'));
    error_log("DEBUG - Contagem de reservation_ids_original imediatamente após criação do pagamento: " . count($reservation_ids_original ?? []));
    error_log("DEBUG - Valor de reservation_ids_original imediatamente após criação do pagamento: " . (empty($reservation_ids_original) ? 'EMPTY' : implode(',', $reservation_ids_original)));
    
    // Garantir que os arrays não sejam nulos
    $reservation_ids_original = $reservation_ids_original ?? [];
    
    if (!empty($mp_payment_id) && !empty($reservation_ids_original)) {
        $reservation_ids_str = implode(',', $reservation_ids_original);
        $stmt_link = $conn->prepare("INSERT INTO mp_payment_links (mp_payment_id, reservation_ids) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE reservation_ids = VALUES(reservation_ids), updated_at = CURRENT_TIMESTAMP");
        if ($stmt_link) {
            $stmt_link->bind_param("ss", $mp_payment_id, $reservation_ids_str);
            if (!$stmt_link->execute()) {
                error_log("Erro ao registrar associação de pagamento MP imediatamente após criação: " . $stmt_link->error);
            } else {
                error_log("Associação de pagamento MP registrada imediatamente após criação: {$mp_payment_id} -> " . $reservation_ids_str);
            }
            $stmt_link->close();
        } else {
            error_log("Erro ao preparar query para registrar associação de pagamento MP imediatamente após criação: " . $conn->error);
        }
    } else {
        error_log("Condição para registrar associação NÃO atendida imediatamente após criação - mp_payment_id: " . ($mp_payment_id ?? 'NULL') . ", reservation_ids_original count: " . count($reservation_ids_original));
        error_log("Condição para registrar associação NÃO atendida imediatamente após criação - mp_payment_id verificado: " . (!empty($mp_payment_id) ? 'SIM' : 'NÃO'));
        error_log("Condição para registrar associação NÃO atendida imediatamente após criação - reservation_ids_original verificado: " . (!empty($reservation_ids_original) ? 'SIM' : 'NÃO'));
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
    // Verificar se os dados do QR Code estão disponíveis
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
    // Primeiro tenta qr_code_base64, se não existir tenta gerar a partir do qr_code
    if (empty($qr_code_base64) && !empty($qr_code)) {
        // Converter o código PIX em base64 para exibição
        error_log("Gerando QR Code base64 a partir do conteúdo do QR Code para reserva");
        
        // Tenta converter o qr_code em imagem base64
        try {
            // Gerar QR Code com biblioteca PHP
            require_once __DIR__ . '/../vendor/autoload.php';
            if (class_exists('Endroid\\QrCode\\QrCode')) {
                // Importar as classes necessárias
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
            error_log("Erro ao gerar QR Code a partir do código: " . $e->getMessage());
        }
        
        // Por enquanto, vamos verificar se o ticket_url está disponível
        if (empty($payment_url) && !empty($payment->point_of_interaction->transaction_data->ticket_url)) {
            $payment_url = $payment->point_of_interaction->transaction_data->ticket_url;
        }
    }
    
    if (empty($qr_code_base64)) {
        error_log("QR Code base64 não disponível para a reserva. Dados do pagamento: " . json_encode($payment));
        
        // Verificar se há alternativas para apresentar ao usuário
        if (!empty($payment_url)) {
            error_log("Usando URL de pagamento alternativa. URL: " . $payment_url);
        } else {
            throw new Exception('QR Code não foi gerado corretamente pelo Mercado Pago e nenhuma URL de pagamento alternativa está disponível. Tente novamente ou entre em contato com o suporte.');
        }
    }
    
    // O ID do pagamento
    $payment_id = $payment->id;
    
    // Terceira etapa: AGORA sim, registrar as reservas e bloquear as datas
    // Começar transação somente após o pagamento ser criado com sucesso
    $conn->begin_transaction();

    $reservation_data = [];
    
    // Processar cada data e registrar a reserva
    foreach ($dates as $date) {
        // Obter o preço da agenda para esta data novamente
        $stmt = $conn->prepare("SELECT preco FROM agenda WHERE data = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $preco = floatval($row['preco']);
        
        // Verificar novamente se a data está disponível com bloqueio (FOR UPDATE)
        $stmt = $conn->prepare("SELECT status FROM agenda WHERE data = ? FOR UPDATE");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['status'] === 'reservado' || $row['status'] === 'pendente') {
            throw new Exception("A data {$date} não está mais disponível no momento da confirmação");
        }
        
        // Atualizar status para pendente (aguardando pagamento)
        $updateAgenda = $conn->prepare("UPDATE agenda SET status = 'pendente' WHERE data = ?");
        $updateAgenda->bind_param("s", $date);
        if (!$updateAgenda->execute()) {
            throw new Exception("Erro ao atualizar status da agenda para {$date}");
        }
        
        // Generate a random group identifier for all reservations (single or multiple days)
        $reserva_grupo = bin2hex(random_bytes(8));

        // Obter o ID da reserva que foi gerado anteriormente e usado no external_reference
        $reservation_id = array_shift($reservation_ids);

        // Salvar a reserva no banco com status pendente
        $insert = $conn->prepare("INSERT INTO reservas (id, user_id, data, valor, status, observacoes, created_at, payment_percentage, tipo_porcentagem, reserva_grupo) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
        $status = 'pendente';
        $tipo_porcentagem = strval($payment_percentage);
        $insert->bind_param("sssssssss", $reservation_id, $_SESSION['user_id'], $date, $preco, $status, $observacoes, $payment_percentage, $tipo_porcentagem, $reserva_grupo);

        if (!$insert->execute()) {
            throw new Exception("Erro ao salvar reserva no banco para {$date}");
        }
        
        $reservation_data[] = [
            'id' => $reservation_id,
            'date' => $date,
            'value' => $preco
        ];
    }
    
    // Confirmar transação
    $conn->commit();
    
    // Log para debug para verificar os valores que estão sendo gerados
    error_log("Valores gerados para a reserva: IDs=" . implode(',', $reservation_ids_original) . ", Valor Total={$total_valor}, Valor a Pagar={$payment_amount}, Porcentagem={$payment_percentage}%, Datas=" . json_encode($dates));
    error_log("QR Code Base64 disponível: " . (empty($qr_code_base64) ? 'NÃO' : 'SIM'));
    error_log("QR Code texto disponível: " . (empty($qr_code) ? 'NÃO' : 'SIM'));
    error_log("URL de pagamento disponível: " . (empty($payment_url) ? 'NÃO' : 'SIM'));
    
    // Registrar a associação entre o pagamento do Mercado Pago e as reservas
    // Extrair o ID do pagamento do objeto de resposta
    $mp_payment_id = $payment->id ?? null;
    
    // Adicionando logs mais detalhados para depuração
    error_log("DEBUG - Valor de payment->id: " . ($mp_payment_id ?? 'NULL'));
    error_log("DEBUG - Contagem de reservation_ids_original: " . count($reservation_ids_original ?? []));
    error_log("DEBUG - Valor de reservation_ids_original: " . (empty($reservation_ids_original) ? 'EMPTY' : implode(',', $reservation_ids_original)));
    
    // Garantir que os arrays não sejam nulos
    $reservation_ids_original = $reservation_ids_original ?? [];
    
    if (!empty($mp_payment_id) && !empty($reservation_ids_original)) {
        $reservation_ids_str = implode(',', $reservation_ids_original);
        $stmt_link = $conn->prepare("INSERT INTO mp_payment_links (mp_payment_id, reservation_ids) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE reservation_ids = VALUES(reservation_ids), updated_at = CURRENT_TIMESTAMP");
        if ($stmt_link) {
            $stmt_link->bind_param("ss", $mp_payment_id, $reservation_ids_str);
            if (!$stmt_link->execute()) {
                error_log("Erro ao registrar associação de pagamento MP: " . $stmt_link->error);
            } else {
                error_log("Associação de pagamento MP registrada: {$mp_payment_id} -> " . $reservation_ids_str);
            }
            $stmt_link->close();
        } else {
            error_log("Erro ao preparar query para registrar associação de pagamento MP: " . $conn->error);
        }
    } else {
        error_log("Condição para registrar associação NÃO atendida - mp_payment_id: " . ($mp_payment_id ?? 'NULL') . ", reservation_ids_original count: " . count($reservation_ids_original));
        error_log("Condição para registrar associação NÃO atendida - mp_payment_id verificado: " . (!empty($mp_payment_id) ? 'SIM' : 'NÃO'));
        error_log("Condição para registrar associação NÃO atendida - reservation_ids_original verificado: " . (!empty($reservation_ids_original) ? 'SIM' : 'NÃO'));
    }
    
    echo json_encode([
        'success' => true,
        'reservation_ids' => $reservation_ids_original,
        'reservation_data' => $reservation_data,
        'qr_code_base64' => $qr_code_base64,
        'qr_code' => $qr_code,
        'pix_key' => $qr_code, // Chave PIX para copiar e colar
        'payment_url' => $payment_url,
        'payment_id' => $mp_payment_id,
        'status' => 'pendente',
        'valor' => $payment_amount,
        'data' => json_encode($dates), // Retorna as datas em formato JSON
        'payment_percentage' => $payment_percentage,
        'total_valor' => $total_valor
    ]);

} catch (Exception $e) {
    // Limpar qualquer conteúdo que possa ter sido enviado para o buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Reverter transação em caso de erro após o início da transação
    if ($conn && $conn->ping()) { // Verificar se a conexão ainda está ativa
        $conn->rollback();
    }
    error_log("Erro ao criar reserva: " . $e->getMessage());
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