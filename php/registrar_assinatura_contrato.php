<?php
// Arquivo para registrar a assinatura do contrato

require 'db_connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Definir cabeçalhos para debug
header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'];
$reserva_id_fornecido = isset($_POST['reserva_id']) ? $_POST['reserva_id'] : null;
$reservas_ids_fornecidas_raw = isset($_POST['reservas_ids']) ? $_POST['reservas_ids'] : null;
$reservas_ids_fornecidas = is_string($reservas_ids_fornecidas_raw) ? json_decode($reservas_ids_fornecidas_raw, true) : $reservas_ids_fornecidas_raw;

try {
    // Adicionando logs para debug
    error_log("registrar_assinatura_contrato: raw POST data: " . print_r($_POST, true));
    error_log("registrar_assinatura_contrato: reserva_id=" . ($reserva_id_fornecido ?: 'null'));
    error_log("registrar_assinatura_contrato: reservas_ids_raw=" . ($reservas_ids_fornecidas_raw ?: 'null'));
    error_log("registrar_assinatura_contrato: reservas_ids_decoded=" . ($reservas_ids_fornecidas ? json_encode($reservas_ids_fornecidas) : 'null'));

    if ($reservas_ids_fornecidas && is_array($reservas_ids_fornecidas) && !empty($reservas_ids_fornecidas)) {
        // Se IDs de múltiplas reservas foram fornecidos, registra apenas para essas reservas
        $reservas_ids = $reservas_ids_fornecidas;
        error_log("registrar_assinatura_contrato: usando reservas_ids fornecidas, total=" . count($reservas_ids));
    } elseif ($reserva_id_fornecido) {
        // Se um ID de reserva específico foi fornecido, registra apenas para essa reserva
        $reservas_ids = [$reserva_id_fornecido];
        error_log("registrar_assinatura_contrato: usando reserva_id unica=" . $reserva_id_fornecido);
    } else {
        // Caso contrário, obter todas as reservas do usuário para registrar a assinatura
        $reservas_query = $conn->prepare("SELECT id FROM reservas WHERE user_id = ? AND (status = 'pendente' OR status = 'confirmado') ORDER BY data ASC");
        $reservas_query->bind_param("i", $user_id);
        $reservas_query->execute();
        $reservas_result = $reservas_query->get_result();

        $reservas_ids = [];
        while ($reserva = $reservas_result->fetch_assoc()) {
            $reservas_ids[] = $reserva['id'];
        }
        error_log("registrar_assinatura_contrato: usando todas as reservas, total=" . count($reservas_ids));
    }

    // Registrar a assinatura para cada reserva ativa do usuário
    foreach ($reservas_ids as $reserva_id) {
        // Verificar se já existe uma assinatura registrada para esta reserva
        $check_query = $conn->prepare("SELECT id FROM contratos_assinados WHERE user_id = ? AND reserva_id = ?");
        $check_query->bind_param("is", $user_id, $reserva_id);
        $check_query->execute();
        $check_result = $check_query->get_result();

        if ($check_result->num_rows === 0) {
            // Registrar a assinatura do contrato
            $insert_query = $conn->prepare("INSERT INTO contratos_assinados (user_id, reserva_id) VALUES (?, ?)");
            $insert_query->bind_param("is", $user_id, $reserva_id);
            
            if (!$insert_query->execute()) {
                throw new Exception("Erro ao registrar assinatura: " . $insert_query->error);
            }
        }
    }

    if ($reservas_ids_fornecidas && is_array($reservas_ids_fornecidas) && !empty($reservas_ids_fornecidas)) {
        echo json_encode(['success' => true, 'message' => 'Contrato assinado com sucesso para ' . count($reservas_ids_fornecidas) . ' reserva' . (count($reservas_ids_fornecidas) > 1 ? 's' : '')]);
    } elseif ($reserva_id_fornecido) {
        echo json_encode(['success' => true, 'message' => 'Contrato assinado com sucesso para a reserva específica']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Contrato assinado com sucesso para todas as reservas ativas']);
    }
} catch (Exception $e) {
    error_log("Erro ao registrar assinatura do contrato: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>