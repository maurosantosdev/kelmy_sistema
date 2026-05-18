<?php
// Arquivo para receber webhooks do Mercado Pago
// Este arquivo deve ser acessível via HTTPS no servidor final

// Verificar se é uma requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(["status" => "success", "message" => "CORS preflight request"]);
    exit();
}

// Log para debug do caminho solicitado - usando o log padrão do PHP
error_log("WEBHOOK - Webhook chamado em: " . $_SERVER['REQUEST_URI']);
error_log("WEBHOOK - Webhook chamado com método: " . $_SERVER['REQUEST_METHOD']);
error_log("WEBHOOK - IP do cliente: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));
error_log("WEBHOOK - User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido'));
error_log("WEBHOOK - Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'desconhecido'));

// Verificar a assinatura do Mercado Pago (necessário para validar a autenticidade da requisição)
// Mercado Pago pode enviar a assinatura em diferentes cabeçalhos
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_X_MP_IPN_SIGNATURE'] ?? $_SERVER['HTTP_X_MP_SIGNATURE'] ?? null;
$topic = $_SERVER['HTTP_X_TOPIC'] ?? $_SERVER['HTTP_X_MP_IPN_TOPIC'] ?? null;
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
$source = $_SERVER['HTTP_X_SOURCE'] ?? $_SERVER['HTTP_X_MP_SOURCE'] ?? null;

// Log para debug de todos os cabeçalhos do Mercado Pago
error_log("WEBHOOK - Cabeçalhos do Mercado Pago - X-Signature: " . ($_SERVER['HTTP_X_SIGNATURE'] ?? 'NENHUM'));
error_log("WEBHOOK - Cabeçalhos do Mercado Pago - X-MP-IPN-Signature: " . ($_SERVER['HTTP_X_MP_IPN_SIGNATURE'] ?? 'NENHUM'));
error_log("WEBHOOK - Cabeçalhos do Mercado Pago - X-MP-Signature: " . ($_SERVER['HTTP_X_MP_SIGNATURE'] ?? 'NENHUM'));
error_log("WEBHOOK - Cabeçalhos do Mercado Pago - X-Topic: " . ($_SERVER['HTTP_X_TOPIC'] ?? 'NENHUM'));
error_log("WEBHOOK - Cabeçalhos do Mercado Pago - X-MP-IPN-Topic: " . ($_SERVER['HTTP_X_MP_IPN_TOPIC'] ?? 'NENHUM'));
error_log("WEBHOOK - Cabeçalhos do Mercado Pago - X-Source: " . ($source ?? 'NENHUM'));

// Log para debug de todos os cabeçalhos recebidos
error_log("WEBHOOK - Assinatura recebida (X-Signature/X-MP-IPN-Signature): " . ($signature ?? 'NENHUMA'));
error_log("WEBHOOK - Assinatura alternativa recebida (X-MP-Signature): " . ($signature_alt ?? 'NENHUMA'));
error_log("WEBHOOK - Tópico recebido: " . ($topic ?? 'NENHUM'));
error_log("WEBHOOK - Request ID recebido: " . ($requestId ?? 'NENHUM'));
error_log("WEBHOOK - Todos os cabeçalhos HTTP: " . print_r(array_filter($_SERVER, function($key) { return strpos($key, 'HTTP_') === 0; }, ARRAY_FILTER_USE_KEY), true));

// Verificar se o request vem do Mercado Pago (opcional, pode ser útil para segurança adicional)
// A lista de IPs do Mercado Pago pode ser consultada em: https://www.mercadopago.com.br/ipn-notifications
$allowed_ips = [
    '186.124.183.17', // IP conhecido do Mercado Pago
    '186.124.183.18', // IP conhecido do Mercado Pago
    '186.124.183.19', // IP conhecido do Mercado Pago
    '186.124.183.20', // IP conhecido do Mercado Pago
    // IPs de sandbox/teste
    '127.0.0.1',
    '::1'
];

$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';

// Para testes locais, pode-se comentar esta verificação
// if (!in_array($client_ip, $allowed_ips)) {
//     error_log("IP bloqueado: {$client_ip}");
//     http_response_code(403);
//     echo "Acesso não autorizado";
//     exit();
// }

// Evitar incluir mp_config.php para não carregar o SDK do Mercado Pago desnecessariamente
require 'php/db_connect.php';

// Ler os dados do webhook
$input = file_get_contents('php://input');

// Verificar se o input está vazio
if (empty($input)) {
    error_log("WEBHOOK - Webhook chamado sem dados no body");
    http_response_code(400);
    echo json_encode(["error" => "Nenhum dado recebido no body"]);
    exit();
}

// Verificar a assinatura HMAC-SHA256 se estiver disponível
// Mercado Pago envia a assinatura no cabeçalho HTTP_X_SIGNATURE
if ($signature) {
    // Remover espaços em branco e tentar extrair a assinatura
    $signature = trim($signature);

    // Mercado Pago pode enviar a assinatura em diferentes formatos
    // Normalmente no formato: "ts={timestamp},v1={signature}" ou apenas "{signature}"
    $signature_value = null;

    // Tenta extrair o valor da assinatura do formato "ts=timestamp,v1=signature"
    if (strpos($signature, 'v1=') !== false) {
        $parts = explode(',', $signature);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, 'v1=') === 0) {
                $signature_value = substr($part, 3); // Remove o prefixo 'v1='
                break;
            }
        }
    } else {
        // Se não estiver no formato ts,v1 assume que é apenas a assinatura
        $signature_value = $signature;
    }

    if ($signature_value) {
        // Carregar a chave secreta do webhook do Mercado Pago
        $webhook_secret = '';

        // Primeiro tenta obter do arquivo de configuração
        if (file_exists('php/mp_webhook_secret.php')) {
            require_once 'php/mp_webhook_secret.php';
            if (defined('MP_WEBHOOK_SECRET')) {
                $webhook_secret = MP_WEBHOOK_SECRET;
            }
        }

        // Se ainda não definido, tenta obter de variável de ambiente
        if (empty($webhook_secret)) {
            $env_secret = getenv('MP_WEBHOOK_SECRET');
            if ($env_secret && !empty($env_secret)) {
                $webhook_secret = $env_secret;
            }
        }

        // Fallback para o valor antigo (deverá ser substituído por um valor correto)
        if (empty($webhook_secret)) {
            // NOTA: Este é um fallback temporário - você DEVE configurar o webhook_secret correto
            // em seu painel do Mercado Pago e definir a chave correspondente
            $webhook_secret = 'd27402fcea742f0b3d2b0fda166aa4bf5bfe726cb6f80f3ab225bf732d802bce'; // Remover após configuração correta
        }

        if (!empty($webhook_secret)) {
            // Calcular o HMAC-SHA256 da carga útil com a chave secreta
            // Usando SHA256 para o cálculo da assinatura, conforme documentação do Mercado Pago
            $calculated_signature = hash_hmac('sha256', $input, $webhook_secret);

            // Verificar se a assinatura corresponde
            if (!hash_equals($signature_value, $calculated_signature)) {
                error_log("WEBHOOK - Assinatura inválida. Assinatura recebida: {$signature_value}, Assinatura calculada: {$calculated_signature}");
                error_log("WEBHOOK - Input usado para cálculo: " . $input);

                // Para permitir testes e depuração, vamos continuar processando
                // Em ambiente de produção, você pode optar por rejeitar requisições com assinatura inválida
                error_log("WEBHOOK - Continuando processamento mesmo com assinatura inválida (para fins de debug)");
            } else {
                error_log("WEBHOOK - Assinatura válida");
            }
        } else {
            error_log("WEBHOOK - Nenhuma chave secreta de webhook encontrada para verificação");
        }
    } else {
        error_log("WEBHOOK - Assinatura não encontrada ou não pôde ser extraída do cabeçalho: {$signature}");
    }
} else {
    error_log("WEBHOOK - Nenhuma assinatura recebida no cabeçalho HTTP_X_SIGNATURE");
    // Para permitir testes e depuração, não vamos rejeitar requisições sem assinatura
    // Em produção, você pode exigir assinatura após configurar corretamente
}

// Adicionando log para verificar o conteúdo do evento recebido
error_log("WEBHOOK - Conteúdo do evento recebido: " . print_r($event, true));

error_log("WEBHOOK - Raw input recebido: " . $input);

// Tentar decodificar o JSON
$event = json_decode($input, true);

// Verificar se o JSON é válido
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("WEBHOOK - Erro ao decodificar JSON: " . json_last_error_msg() . " - Input: " . $input);
    http_response_code(400);
    echo json_encode(["error" => "JSON inválido recebido"]);
    exit();
}

// Log para debug (remover em produção)
error_log("WEBHOOK - Webhook recebido: " . print_r($event, true));

// Verificar se o webhook foi chamado corretamente
if (empty($event)) {
    error_log("WEBHOOK - Webhook chamado sem dados válidos após decodificação");
    http_response_code(400);
    echo json_encode(["error" => "Nenhum dado válido recebido"]);
    exit();
}

error_log("WEBHOOK - Ação recebida: " . ($event['action'] ?? 'N/A'));
error_log("WEBHOOK - Tipo recebido: " . ($event['type'] ?? 'N/A'));
error_log("WEBHOOK - Tópico recebido: " . ($topic ?? 'N/A'));

// Verificar se é um evento de pagamento
$is_payment_event = false;
if (isset($event['action']) && strpos($event['action'], 'payment') !== false) {
    $is_payment_event = true;
    error_log("WEBHOOK - Evento identificado como pagamento via action: " . $event['action']);
} elseif (isset($event['type']) && $event['type'] === 'payment') {
    $is_payment_event = true;
    error_log("WEBHOOK - Evento identificado como pagamento via type: " . $event['type']);
} elseif ($topic === 'payment') {
    $is_payment_event = true;
    error_log("WEBHOOK - Evento identificado como pagamento via tópico: " . $topic);
}

if ($is_payment_event) {
    error_log("WEBHOOK - Evento de pagamento recebido: " . print_r($event, true));
    
    // Extrair o ID do pagamento de diferentes possíveis locais
    $payment_id = null;
    if (isset($event['data']['id'])) {
        $payment_id = $event['data']['id'];
    } elseif (isset($event['id'])) {
        // Para alguns tipos de eventos, o ID pode estar diretamente no objeto
        $payment_id = $event['id'];
    }
    
    // Se ainda não encontrado, tentar extrair do ID do evento se for um evento de pagamento
    if (!$payment_id && isset($event['data']['payment_id'])) {
        $payment_id = $event['data']['payment_id'];
    }
    
    if ($payment_id) {
        error_log("WEBHOOK - Processando pagamento ID: {$payment_id}");
        
        // Carregar apenas as configurações de ambiente do Mercado Pago para obter o token
        require_once 'php/mp_env_config.php';
        
        // Obter detalhes do pagamento via API do Mercado Pago
        $access_token = defined('MP_ACCESS_TOKEN') ? MP_ACCESS_TOKEN : 'SEU_ACCESS_TOKEN_AQUI'; // Usar constante definida em mp_config.php
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$payment_id}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verificar certificado SSL
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout de 30 segundos
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecionamentos
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3); // Máximo de 3 redirecionamentos
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log("WEBHOOK - Erro de cURL ao verificar pagamento {$payment_id}: " . $curl_error);
            error_log("WEBHOOK - Tentando processar diretamente do evento recebido...");
            
            // Em caso de erro na API, tentar processar diretamente do evento recebido
            // Isso é útil para pagamentos reais que ainda estão sendo processados
            $reservation_status = 'pendente';
            
            // Extrair informações do evento recebido
            $payment_status = null;
            $external_reference = null;
            
            // Extrair status do pagamento do evento
            if (isset($event['action'])) {
                if (strpos($event['action'], 'payment.approved') !== false || strpos($event['action'], 'approved') !== false) {
                    $reservation_status = 'confirmado';
                } elseif (strpos($event['action'], 'payment.rejected') !== false || strpos($event['action'], 'rejected') !== false) {
                    $reservation_status = 'cancelado';
                } elseif (strpos($event['action'], 'payment.pending') !== false || strpos($event['action'], 'pending') !== false) {
                    $reservation_status = 'pendente';
                }
            }
            
            // Tentar obter external_reference de diferentes locais no evento
            if (isset($event['data']['external_reference']) && !empty($event['data']['external_reference'])) {
                $external_reference = $event['data']['external_reference'];
            } elseif (isset($event['external_reference']) && !empty($event['external_reference'])) {
                $external_reference = $event['external_reference'];
            } elseif (isset($event['data']['payment']['external_reference']) && !empty($event['data']['payment']['external_reference'])) {
                $external_reference = $event['data']['payment']['external_reference'];
            }
            
            if ($external_reference) {
                error_log("WEBHOOK - Processando via evento direto - external_reference: {$external_reference}, status: {$reservation_status}");
                processarReservas($external_reference, $reservation_status, $conn);
            } else {
                error_log("WEBHOOK - Nenhum external_reference encontrado no evento para processamento direto");
            }
            
        } elseif ($http_code === 200) {
            $payment = json_decode($response, true);
            
            // Processar informações do pagamento da API
            if ($payment && isset($payment['status']) && isset($payment['external_reference'])) {
                $external_reference = $payment['external_reference'];
                
                // Mapear status do pagamento para status da reserva
                $status = $payment['status'];
                $reservation_status = 'pendente';
                
                switch ($status) {
                    case 'approved':
                        $reservation_status = 'confirmado';
                        break;
                    case 'rejected':
                    case 'cancelled':
                    case 'refunded':
                        $reservation_status = 'cancelado';
                        break;
                    case 'in_process':
                    case 'pending':
                    case 'authorized':
                        $reservation_status = 'pendente';
                        break;
                }
                
                error_log("WEBHOOK - Status do pagamento: {$status}, Status para reserva: {$reservation_status}");
                
                // Processar as reservas associadas
                processarReservas($external_reference, $reservation_status, $conn);
                
            } else {
                error_log("WEBHOOK - Erro ao decodificar resposta da API do Mercado Pago para pagamento {$payment_id}: " . $response);
                
                // Tenta interpretar diretamente do evento recebido
                $reservation_status = 'pendente';
                $external_reference = null;
                
                if (isset($event['data']['external_reference']) && !empty($event['data']['external_reference'])) {
                    $external_reference = $event['data']['external_reference'];
                } elseif (isset($event['external_reference']) && !empty($event['external_reference'])) {
                    $external_reference = $event['external_reference'];
                }
                
                if ($external_reference) {
                    error_log("WEBHOOK - Processando via evento (API falhou) - external_reference: {$external_reference}, status: {$reservation_status}");
                    processarReservas($external_reference, $reservation_status, $conn);
                }
            }
        } else {
            error_log("WEBHOOK - Erro na chamada à API do Mercado Pago para pagamento {$payment_id}. HTTP Code: {$http_code}. Resposta: " . $response);
            
            // Para alguns erros específicos, podemos tentar novamente com um endpoint diferente
            // ou usar uma abordagem diferente para obter os detalhes do pagamento
            $reservation_status = 'pendente';
            if (isset($event['action'])) {
                if (strpos($event['action'], 'payment.approved') !== false || 
                    strpos($event['action'], 'approved') !== false || 
                    strpos($event['action'], 'succeeded') !== false ||
                    strpos($event['action'], 'completed') !== false ||
                    strpos($event['action'], 'payment.updated') !== false) {
                    $reservation_status = 'confirmado';
                } elseif (strpos($event['action'], 'payment.rejected') !== false || 
                          strpos($event['action'], 'rejected') !== false || 
                          strpos($event['action'], 'cancelled') !== false || 
                          strpos($event['action'], 'refunded') !== false) {
                    $reservation_status = 'cancelado';
                }
            }
            
            $external_reference = null;
            
            // Tentar obter external_reference do evento
            if (isset($event['data']['external_reference']) && !empty($event['data']['external_reference'])) {
                $external_reference = $event['data']['external_reference'];
            } elseif (isset($event['external_reference']) && !empty($event['external_reference'])) {
                $external_reference = $event['external_reference'];
            } elseif (isset($event['data']['payment']['external_reference']) && !empty($event['data']['payment']['external_reference'])) {
                $external_reference = $event['data']['payment']['external_reference'];
            }
            
            if ($external_reference) {
                error_log("WEBHOOK - Tentando processamento via evento após falha de API - external_reference: {$external_reference}, status: {$reservation_status}");
                processarReservas($external_reference, $reservation_status, $conn);
            } else {
                error_log("WEBHOOK - Nenhum external_reference encontrado no evento para pagamento {$payment_id}. Tentando buscar na tabela de associação.");
                
                // Buscar o external_reference na tabela de associação
                $stmt_link = $conn->prepare("SELECT reservation_ids FROM mp_payment_links WHERE mp_payment_id = ?");
                if ($stmt_link) {
                    $stmt_link->bind_param("s", $payment_id);
                    $stmt_link->execute();
                    $result_link = $stmt_link->get_result();
                    
                    if ($row_link = $result_link->fetch_assoc()) {
                        $external_reference = $row_link['reservation_ids'];
                        error_log("WEBHOOK - External reference encontrado na tabela de associação para pagamento {$payment_id}: {$external_reference}");
                        
                        // Atualizar o status na tabela de associação também
                        $update_status = $conn->prepare("UPDATE mp_payment_links SET reservation_status = ? WHERE mp_payment_id = ?");
                        if ($update_status) {
                            $update_status->bind_param("ss", $reservation_status, $payment_id);
                            $update_status->execute();
                            $update_status->close();
                        }
                        
                        processarReservas($external_reference, $reservation_status, $conn);
                    } else {
                        error_log("WEBHOOK - Nenhum registro de associação encontrado para pagamento {$payment_id} na tabela mp_payment_links");
                        
                        // Adicionar log para verificar se o pagamento existe em outra forma
                        error_log("WEBHOOK - Verificando se existem outros registros na tabela mp_payment_links...");
                        $check_all = $conn->query("SELECT mp_payment_id, reservation_ids FROM mp_payment_links LIMIT 10");
                        while ($check_row = $check_all->fetch_assoc()) {
                            error_log("WEBHOOK - Registro encontrado: pagamento={$check_row['mp_payment_id']}, reservas={$check_row['reservation_ids']}");
                        }
                        
                        // Tentar obter os dados do pagamento usando um endpoint diferente ou uma abordagem alternativa
                        error_log("WEBHOOK - Tentando obter dados do pagamento via endpoint alternativo para pagamento {$payment_id}");
                        
                        // Tentar obter dados do pagamento com uma chamada separada que pode retornar mais informações
                        $ch2 = curl_init();
                        curl_setopt($ch2, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$payment_id}?include_fee=true");
                        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $access_token,
                            'Content-Type: application/json'
                        ]);
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, true);
                        curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
                        
                        $response2 = curl_exec($ch2);
                        $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        curl_close($ch2);
                        
                        if ($http_code2 === 200) {
                            $payment_details = json_decode($response2, true);
                            if (isset($payment_details['external_reference'])) {
                                $external_reference = $payment_details['external_reference'];
                                error_log("WEBHOOK - External reference obtido do endpoint alternativo: {$external_reference}");
                                processarReservas($external_reference, $reservation_status, $conn);
                            } else {
                                error_log("WEBHOOK - Nenhum external_reference encontrado no endpoint alternativo para pagamento {$payment_id}");
                            }
                        } else {
                            error_log("WEBHOOK - Endpoint alternativo também falhou: HTTP {$http_code2}, Resposta: " . $response2);
                            
                            // Ultimo recurso: tentar encontrar reservas pendentes recentes que possam estar associadas
                            // Isso é arriscado, mas melhor do que não fazer nada
                            error_log("WEBHOOK - Nenhum external_reference encontrado após todas as tentativas para pagamento {$payment_id}");
                        }
                    }
                    $stmt_link->close();
                } else {
                    error_log("WEBHOOK - Falha ao preparar consulta para buscar associação de pagamento {$payment_id}");
                }
            }
        }
    } else {
        error_log("WEBHOOK - Webhook recebido sem ID de pagamento identificável: " . print_r($event, true));
        
        // Mesmo sem ID de pagamento direto, pode haver informações úteis no evento
        if (isset($event['data']['external_reference']) || isset($event['external_reference'])) {
            $external_reference = $event['data']['external_reference'] ?? $event['external_reference'] ?? null;
            if ($external_reference) {
                error_log("WEBHOOK - Processando evento por external_reference mesmo sem ID de pagamento: {$external_reference}");
                
                // Determinar status com base na ação do evento
                $reservation_status = 'pendente';
                if (isset($event['action'])) {
                    if (strpos($event['action'], 'payment.approved') !== false || 
                        strpos($event['action'], 'approved') !== false || 
                        strpos($event['action'], 'succeeded') !== false ||
                        strpos($event['action'], 'completed') !== false ||
                        strpos($event['action'], 'payment.updated') !== false) {
                        $reservation_status = 'confirmado';
                    } elseif (strpos($event['action'], 'payment.rejected') !== false || 
                              strpos($event['action'], 'rejected') !== false || 
                              strpos($event['action'], 'cancelled') !== false || 
                              strpos($event['action'], 'refunded') !== false) {
                        $reservation_status = 'cancelado';
                    }
                }
                
                processarReservas($external_reference, $reservation_status, $conn);
            }
        }
    }
} else {
    error_log("WEBHOOK - Webhook recebido com tipo não identificado como pagamento: " . print_r($event, true));
    
    // Registrar o tipo de evento recebido
    $event_type = $event['type'] ?? 'unknown';
    $event_action = $event['action'] ?? 'unknown';
    error_log("WEBHOOK - Tipo do evento: {$event_type}, Ação: {$event_action}");
}

// Função para processar as reservas associadas a um pagamento
function processarReservas($external_reference, $reservation_status, $conn) {
    error_log("WEBHOOK - Iniciando processamento de reservas - external_reference: {$external_reference}, status: {$reservation_status}");

    // O external_reference pode conter múltiplos IDs de reserva separados por vírgula
    $reservation_ids = explode(',', $external_reference);

    // Verificar se os IDs precisam ser limpos de espaços ou caracteres especiais
    $reservation_ids = array_map('trim', $reservation_ids);

    error_log("WEBHOOK - IDs de reserva extraídos: " . print_r($reservation_ids, true));
    error_log("WEBHOOK - Quantidade de IDs de reservas a serem atualizados: " . count($reservation_ids) . ", IDs: " . implode(',', $reservation_ids));

    // Atualizar todas as reservas associadas a este pagamento
    foreach ($reservation_ids as $reservation_id) {
        $reservation_id = trim($reservation_id); // Remover espaços em branco

        // Validar que o ID da reserva é válido antes de fazer a atualização
        // O formato do ID gerado por uniqid('res_', true) é como 'res_68fbe6b2e9b5f2.69131627'
        if (!empty($reservation_id) && preg_match('/^res_[a-f0-9]+[.][0-9]+$/', $reservation_id)) {
            error_log("WEBHOOK - Atualizando reserva: {$reservation_id} para status: {$reservation_status}");

            if ($reservation_status === 'confirmado') {
                // Atualizar status e definir o tempo de confirmação
                $stmt = $conn->prepare("UPDATE reservas SET status = ?, payment_confirmed_at = NOW() WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("WEBHOOK - Definindo payment_confirmed_at para a reserva: {$reservation_id}");
            } else {
                // Para outros status, não atualizar o tempo de confirmação
                $stmt = $conn->prepare("UPDATE reservas SET status = ? WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("WEBHOOK - Atualizando status (sem payment_confirmed_at) para a reserva: {$reservation_id}");
            }

            if ($stmt->execute()) {
                error_log("WEBHOOK - Reserva {$reservation_id} atualizada com sucesso para status: {$reservation_status}");

                // Log adicional para verificar resposta do banco
                error_log("WEBHOOK - Linhas afetadas pela atualização: " . $stmt->affected_rows);

                // Se o pagamento foi aprovado, atualizar também a agenda
                if ($reservation_status === 'confirmado') {
                    // Obter a data da reserva
                    $reserva_data = $conn->prepare("SELECT data, user_id FROM reservas WHERE id = ?");
                    $reserva_data->bind_param("s", $reservation_id);
                    $reserva_data->execute();
                    $result = $reserva_data->get_result();

                    error_log("WEBHOOK - Consulta à tabela reservas executada para reserva {$reservation_id}");
                    error_log("WEBHOOK - Linhas retornadas pela consulta: " . $result->num_rows);

                    if ($row = $result->fetch_assoc()) {
                        error_log("WEBHOOK - Data da reserva {$reservation_id} encontrada: {$row['data']}, user_id: {$row['user_id']}");

                        // Atualizar status da agenda para 'reservado' quando o pagamento é confirmado via webhook
                        $update_agenda = $conn->prepare("UPDATE agenda SET status = 'reservado' WHERE data = ?");
                        $update_agenda->bind_param("s", $row['data']);
                        $update_agenda->execute();
                        error_log("WEBHOOK - Atualização da agenda para data {$row['data']}, status 'reservado' (reserva: {$reservation_id}), linhas afetadas: " . $update_agenda->affected_rows);

                        // Registrar o evento de confirmação para debug
                        error_log("WEBHOOK - Pagamento confirmado registrado para reserva {$reservation_id}, data {$row['data']}, usuário {$row['user_id']}");
                    } else {
                        error_log("WEBHOOK - Erro: Não foi possível encontrar a data da reserva {$reservation_id}");
                    }
                }
            } else {
                error_log("WEBHOOK - Erro ao atualizar reserva {$reservation_id}: " . $stmt->error);
            }

            $stmt->close();
        } else {
            error_log("WEBHOOK - ID de reserva inválido ignorado: {$reservation_id}");
            error_log("WEBHOOK - Padrão esperado: /^res_[a-f0-9]+[.][0-9]+$/");
            error_log("WEBHOOK - ID recebido: {$reservation_id}");
            error_log("WEBHOOK - Validação do ID: " . (preg_match('/^res_[a-f0-9]+[.][0-9]+$/', $reservation_id) ? 'PASSOU' : 'FALHOU'));
        }
    }

    // Adicionando log para confirmar que o processamento foi concluído
    error_log("WEBHOOK - Processamento de reservas concluído para external_reference: {$external_reference}, status final: {$reservation_status}");
}

// Responder com 200 OK para confirmar recebimento
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(["status" => "success", "message" => "Webhook recebido e processado com sucesso"]);
?>