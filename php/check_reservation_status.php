<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verificar se foi fornecido um ID específico de reserva
$reserva_id = $_GET['reserva_id'] ?? $_POST['reserva_id'] ?? null;

if ($reserva_id) {
    // Verificar o status de uma reserva específica
    $stmt = $conn->prepare("SELECT id, status FROM reservas WHERE user_id = ? AND id = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $reserva_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Reserva não encontrada ou não pertence ao usuário'
        ]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'confirmed' => $row['status'] === 'confirmado',
        'status' => $row['status'],
        'reserva_id' => $row['id']
    ]);
    
} else {
    // Verificar se foram fornecidos IDs de reservas ou datas
    $ids_json = $_GET['reservas_ids'] ?? $_POST['reservas_ids'] ?? 
                $_GET['dates'] ?? $_POST['dates'] ?? null;

    if (empty($ids_json)) {
        echo json_encode(['success' => false, 'message' => 'IDs de reservas ou datas não fornecidos']);
        exit;
    }

    $ids = json_decode($ids_json, true);

    if (!$ids || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => 'Formato de IDs de reservas ou datas inválido']);
        exit;
    }

    // Determinar se são IDs de reserva ou datas
    $is_reservation_ids = true;
    foreach ($ids as $id) {
        if (!is_string($id) || !preg_match('/^res_[a-f0-9]+[.][0-9]+$/', $id)) {
            // Se algum item não seguir o padrão de ID de reserva, assume que são datas
            $is_reservation_ids = false;
            break;
        }
    }
    
    if ($is_reservation_ids) {
        // Consultar status das reservas pelos IDs
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, status, payment_confirmed_at FROM reservas WHERE user_id = ? AND id IN ($placeholders) ORDER BY data ASC");
        $params = array_merge([$user_id], $ids);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $reservations_status = [];
        $all_confirmed = true;

        while ($row = $result->fetch_assoc()) {
            $reservations_status[$row['id']] = [
                'status' => $row['status'],
                'payment_confirmed_at' => $row['payment_confirmed_at']
            ];
            if ($row['status'] !== 'confirmado') {
                $all_confirmed = false;
            }
        }
    } else {
        // Consultar status das reservas pelas datas (funcionalidade antiga)
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT data, status, payment_confirmed_at FROM reservas WHERE user_id = ? AND data IN ($placeholders) ORDER BY data ASC");
        $params = array_merge([$user_id], $ids);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $reservations_status = [];
        $all_confirmed = true;

        while ($row = $result->fetch_assoc()) {
            $reservations_status[$row['data']] = [
                'status' => $row['status'],
                'payment_confirmed_at' => $row['payment_confirmed_at']
            ];
            if ($row['status'] !== 'confirmado') {
                $all_confirmed = false;
            }
        }
    }

    // Verificar se todas as reservas estão confirmadas
    if ($all_confirmed) {
        echo json_encode([
            'success' => true,
            'all_confirmed' => true,
            'message' => 'Todas as reservas foram confirmadas',
            'reservations_status' => $reservations_status
        ]);
    } else {
        // Verificar se alguma reserva foi confirmada recentemente (nos últimos 10 minutos)
        $has_recent_confirmation = false;
        $recent_confirmed_count = 0;
        $total_confirmed_count = 0;

        foreach ($reservations_status as $status_info) {
            if (is_array($status_info)) {
                if ($status_info['status'] === 'confirmado') {
                    $total_confirmed_count++;

                    if (!empty($status_info['payment_confirmed_at'])) {
                        $confirmation_time = new DateTime($status_info['payment_confirmed_at']);
                        $current_time = new DateTime();
                        $interval = $current_time->diff($confirmation_time);

                        // Se a confirmação foi feita há menos de 10 minutos, considerar como recente
                        if ($interval->i < 10 && $interval->h === 0 && $interval->d === 0) {
                            $has_recent_confirmation = true;
                            $recent_confirmed_count++;
                        }
                    }
                }
            } else {
                // Caso o status_info seja uma string (versão antiga)
                if ($status_info === 'confirmado') {
                    $total_confirmed_count++;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'all_confirmed' => false,
            'has_recent_confirmation' => $has_recent_confirmation,
            'recent_confirmed_count' => $recent_confirmed_count,
            'total_confirmed_count' => $total_confirmed_count,
            'message' => $has_recent_confirmation ? 'Confirmação de pagamento recebida recentemente' : 'Aguardando confirmação de pagamento',
            'reservations_status' => $reservations_status
        ]);
    }
}

$stmt->close();
$conn->close();
?>