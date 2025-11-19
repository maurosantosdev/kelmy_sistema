<?php
// Script de verificação contínua de pagamentos pendentes
// Este script verifica periodicamente o status dos pagamentos no Mercado Pago
// e atualiza os status das reservas de acordo com o status do pagamento

require 'php/db_connect.php';
require 'php/mp_env_config.php';

echo "Iniciando serviço de verificação de pagamentos...\n";
error_log("SERVIÇO DE VERIFICAÇÃO - Iniciando serviço de verificação de pagamentos pendentes");

// Função para processar as reservas (cópia da função do webhook)
function processarReservas($external_reference, $reservation_status, $conn) {
    error_log("SERVIÇO DE VERIFICAÇÃO - Iniciando processamento de reservas - external_reference: {$external_reference}, status: {$reservation_status}");
    
    // O external_reference pode conter múltiplos IDs de reserva separados por vírgula
    $reservation_ids = explode(',', $external_reference);
    
    // Verificar se os IDs precisam ser limpos de espaços ou caracteres especiais
    $reservation_ids = array_map('trim', $reservation_ids);
    
    error_log("SERVIÇO DE VERIFICAÇÃO - IDs de reserva extraídos: " . print_r($reservation_ids, true));
    error_log("SERVIÇO DE VERIFICAÇÃO - Quantidade de IDs de reservas a serem atualizados: " . count($reservation_ids) . ", IDs: " . implode(',', $reservation_ids));
    
    // Atualizar todas as reservas associadas a este pagamento
    foreach ($reservation_ids as $reservation_id) {
        $reservation_id = trim($reservation_id); // Remover espaços em branco
        
        // Validar que o ID da reserva é válido antes de fazer a atualização
        if (!empty($reservation_id) && preg_match('/^res_/', $reservation_id)) {
            error_log("SERVIÇO DE VERIFICAÇÃO - Atualizando reserva: {$reservation_id} para status: {$reservation_status}");
            
            if ($reservation_status === 'confirmado') {
                // Atualizar status e definir o tempo de confirmação
                $stmt = $conn->prepare("UPDATE reservas SET status = ?, payment_confirmed_at = NOW() WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("SERVIÇO DE VERIFICAÇÃO - Definindo payment_confirmed_at para a reserva: {$reservation_id}");
            } else {
                // Para outros status, não atualizar o tempo de confirmação
                $stmt = $conn->prepare("UPDATE reservas SET status = ? WHERE id = ?");
                $stmt->bind_param("ss", $reservation_status, $reservation_id);
                error_log("SERVIÇO DE VERIFICAÇÃO - Atualizando status (sem payment_confirmed_at) para a reserva: {$reservation_id}");
            }
            
            if ($stmt->execute()) {
                error_log("SERVIÇO DE VERIFICAÇÃO - Reserva {$reservation_id} atualizada com sucesso para status: {$reservation_status}");
                
                // Log adicional para verificar resposta do banco
                error_log("SERVIÇO DE VERIFICAÇÃO - Linhas afetadas pela atualização: " . $stmt->affected_rows);
                
                // Se o pagamento foi aprovado, atualizar também a agenda
                if ($reservation_status === 'confirmado') {
                    // Obter a data da reserva
                    $reserva_data = $conn->prepare("SELECT data FROM reservas WHERE id = ?");
                    $reserva_data->bind_param("s", $reservation_id);
                    $reserva_data->execute();
                    $result = $reserva_data->get_result();
                    
                    error_log("SERVIÇO DE VERIFICAÇÃO - Consulta à tabela reservas executada para reserva {$reservation_id}");
                    error_log("SERVIÇO DE VERIFICAÇÃO - Linhas retornadas pela consulta: " . $result->num_rows);
                    
                    if ($row = $result->fetch_assoc()) {
                        error_log("SERVIÇO DE VERIFICAÇÃO - Data da reserva {$reservation_id} encontrada: {$row['data']}");
                        
                        // Atualizar status da agenda para 'reservado' quando o pagamento é confirmado
                        $update_agenda = $conn->prepare("UPDATE agenda SET status = 'reservado' WHERE data = ?");
                        $update_agenda->bind_param("s", $row['data']);
                        $update_agenda->execute();
                        error_log("SERVIÇO DE VERIFICAÇÃO - Atualização da agenda para data {$row['data']}, status 'reservado' (reserva: {$reservation_id}), linhas afetadas: " . $update_agenda->affected_rows);
                    } else {
                        error_log("SERVIÇO DE VERIFICAÇÃO - Erro: Não foi possível encontrar a data da reserva {$reservation_id}");
                    }
                }
            } else {
                error_log("SERVIÇO DE VERIFICAÇÃO - Erro ao atualizar reserva {$reservation_id}: " . $stmt->error);
            }
            
            $stmt->close();
        } else {
            error_log("SERVIÇO DE VERIFICAÇÃO - ID de reserva inválido ignorado: {$reservation_id}");
            error_log("SERVIÇO DE VERIFICAÇÃO - Padrão esperado: /^res_/");
            error_log("SERVIÇO DE VERIFICAÇÃO - ID recebido: {$reservation_id}");
            error_log("SERVIÇO DE VERIFICAÇÃO - Validação do ID: " . (preg_match('/^res_/', $reservation_id) ? 'PASSOU' : 'FALHOU'));
        }
    }
}

// Função para obter status atual do pagamento no Mercado Pago
function obterStatusPagamentoMP($payment_id) {
    global $conn;
    
    // Carregar o access token
    $access_token = defined('MP_ACCESS_TOKEN') ? MP_ACCESS_TOKEN : 'SEU_ACCESS_TOKEN_AQUI';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$payment_id}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log("SERVIÇO DE VERIFICAÇÃO - Erro de cURL ao verificar pagamento {$payment_id}: " . $curl_error);
        return null;
    }
    
    if ($http_code === 200) {
        $payment = json_decode($response, true);
        if (isset($payment['status'])) {
            return $payment['status'];
        }
    } else {
        error_log("SERVIÇO DE VERIFICAÇÃO - Erro na chamada à API do Mercado Pago para pagamento {$payment_id}. HTTP Code: {$http_code}. Resposta: " . $response);
    }
    
    return null;
}

// Função para mapear status do Mercado Pago para status da reserva
function mapearStatusMPParaReserva($mp_status) {
    switch ($mp_status) {
        case 'approved':
            return 'confirmado';
        case 'rejected':
        case 'cancelled':
        case 'refunded':
            return 'cancelado';
        case 'in_process':
        case 'pending':
        case 'authorized':
        default:
            return 'pendente';
    }
}

// Loop principal de verificação
while (true) {
    echo "\n" . date('Y-m-d H:i:s') . " - Verificando pagamentos pendentes...\n";
    error_log("SERVIÇO DE VERIFICAÇÃO - " . date('Y-m-d H:i:s') . " - Iniciando verificação de pagamentos pendentes");
    
    try {
        // Obter todos os pagamentos da tabela de associação que ainda estão pendentes
        $stmt = $conn->prepare("SELECT mp_payment_id, reservation_ids, reservation_status FROM mp_payment_links WHERE reservation_status = 'pendente' OR reservation_status IS NULL OR reservation_status = '' ORDER BY created_at ASC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total_verificados = 0;
        $total_atualizados = 0;
        
        while ($row = $result->fetch_assoc()) {
            $payment_id = $row['mp_payment_id'];
            $reservation_ids = $row['reservation_ids'];
            $current_status = $row['reservation_status'];
            
            echo "  Verificando pagamento: {$payment_id}\n";
            error_log("SERVIÇO DE VERIFICAÇÃO - Verificando pagamento: {$payment_id}, reservas: {$reservation_ids}, status atual: {$current_status}");
            
            // Obter status atual do pagamento no Mercado Pago
            $mp_status = obterStatusPagamentoMP($payment_id);
            
            if ($mp_status !== null) {
                // Mapear status do Mercado Pago para status da reserva
                $new_status = mapearStatusMPParaReserva($mp_status);
                
                echo "    Status do MP: {$mp_status}, Status mapeado: {$new_status}\n";
                error_log("SERVIÇO DE VERIFICAÇÃO - Status do MP: {$mp_status}, Status mapeado: {$new_status}");
                
                // Se o status mudou, atualizar
                if ($new_status !== $current_status) {
                    echo "    Status mudou! Atualizando de '{$current_status}' para '{$new_status}'\n";
                    error_log("SERVIÇO DE VERIFICAÇÃO - Status mudou! Atualizando de '{$current_status}' para '{$new_status}' para pagamento {$payment_id}");
                    
                    // Atualizar status na tabela de associação
                    $update_stmt = $conn->prepare("UPDATE mp_payment_links SET reservation_status = ? WHERE mp_payment_id = ?");
                    $update_stmt->bind_param("ss", $new_status, $payment_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Processar as reservas com o novo status
                    processarReservas($reservation_ids, $new_status, $conn);
                    
                    $total_atualizados++;
                } else {
                    echo "    Status não mudou ({$current_status})\n";
                    error_log("SERVIÇO DE VERIFICAÇÃO - Status não mudou para pagamento {$payment_id} ({$current_status})");
                }
            } else {
                echo "    Não foi possível obter status do pagamento no Mercado Pago\n";
                error_log("SERVIÇO DE VERIFICAÇÃO - Não foi possível obter status do pagamento {$payment_id} no Mercado Pago");
            }
            
            $total_verificados++;
        }
        
        $stmt->close();
        
        echo date('Y-m-d H:i:s') . " - Verificação concluída. Verificados: {$total_verificados}, Atualizados: {$total_atualizados}\n";
        error_log("SERVIÇO DE VERIFICAÇÃO - " . date('Y-m-d H:i:s') . " - Verificação concluída. Verificados: {$total_verificados}, Atualizados: {$total_atualizados}");
        
    } catch (Exception $e) {
        echo "Erro durante a verificação: " . $e->getMessage() . "\n";
        error_log("SERVIÇO DE VERIFICAÇÃO - Erro durante a verificação: " . $e->getMessage());
    }
    
    // Aguardar 5 segundos antes da próxima verificação
    echo "Aguardando 5 segundos...\n";
    sleep(5);
}

$conn->close();
?>