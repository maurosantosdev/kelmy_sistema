<?php
// Script de diagnóstico para verificar o problema das diárias
session_start();
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1; // Valor padrão

require 'php/db_connect.php';

echo "<h2>Diagnóstico do Problema de Diárias no Contrato</h2>\n";

// Dados da URL que você mencionou
// contrato.php?reservas_ids=["res_691c7acfa7b6e7.92719286","res_691c7acfa7b5f0.70438958"]
// Vamos simular a obtenção dessas reservas

$reservas_ids = ['res_691c7acfa7b6e7.92719286', 'res_691c7acfa7b5f0.70438958'];
$user_id = $_SESSION['user_id'];

echo "<p><strong>Reservas solicitadas:</strong> " . json_encode($reservas_ids) . "</p>\n";

// Obter informações das reservas específicas
if (!empty($reservas_ids)) {
    $placeholders = str_repeat('?,', count($reservas_ids) - 1) . '?';
    $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem FROM reservas r WHERE r.user_id = ? AND r.id IN ($placeholders) ORDER BY r.data ASC");
    $types = 'i' . str_repeat('s', count($reservas_ids));
    $params = array_merge([$user_id], $reservas_ids);
    $reservas_query->bind_param($types, ...$params);
} else {
    $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem FROM reservas r WHERE r.user_id = ? ORDER BY r.data ASC");
    $reservas_query->bind_param("i", $user_id);
}

$reservas_query->execute();
$reservas_result = $reservas_query->get_result();

$reservas = [];
while ($reserva = $reservas_result->fetch_assoc()) {
    $reservas[] = $reserva;
}

echo "<p><strong>Reservas retornadas:</strong> " . count($reservas) . "</p>\n";
foreach ($reservas as $reserva) {
    echo "<p>- ID: {$reserva['id']}, Data: {$reserva['data']}, Valor: R$ {$reserva['valor']}, Status: {$reserva['status']}, Payment %: {$reserva['payment_percentage']}, Tipo Porcentagem: {$reserva['tipo_porcentagem']}</p>\n";
}

// Função para obter datas com contratos assinados
function getSignedContractDatesForUser($conn, $user_id, $payment_percentage = null) {
    $sql = "SELECT r.data FROM reservas r 
            JOIN contratos_assinados ca ON r.id = ca.reserva_id 
            WHERE r.user_id = ?";
    
    $params = [$user_id];
    $types = "i";
    
    if ($payment_percentage !== null) {
        $sql .= " AND (r.payment_percentage = ? OR r.tipo_porcentagem = ?)";
        $params[] = $payment_percentage;
        $params[] = strval($payment_percentage);
        $types .= "is";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $signed_dates = [];
    while ($row = $result->fetch_assoc()) {
        $signed_dates[] = $row['data'];
    }
    
    $stmt->close();
    return $signed_dates;
}

// Calcular o número real de diárias considerando datas já assinadas
if (count($reservas) > 0) {
    // Obter o percentual de pagamento das reservas (assumindo que todas têm o mesmo percentual)
    $payment_percentage = $reservas[0]['payment_percentage'];
    
    echo "<p><strong>Payment percentage das reservas atuais:</strong> $payment_percentage%</p>\n";

    // Obter todas as datas com contratos já assinados com o mesmo percentual
    $signed_dates = getSignedContractDatesForUser($conn, $user_id, $payment_percentage);

    echo "<p><strong>Datas já com contratos assinados (mesmo % pagamento):</strong> " . implode(', ', $signed_dates) . "</p>\n";

    // Pegar as datas das reservas que estão sendo assinadas agora
    $current_reservation_dates = array_map(function($reserva) {
        return new DateTime($reserva['data']);
    }, $reservas);
    
    echo "<p><strong>Datas atuais (sendo assinadas agora):</strong> ";
    foreach ($current_reservation_dates as $date) {
        echo $date->format('Y-m-d') . " ";
    }
    echo "</p>\n";

    // Converter datas assinadas para objetos DateTime
    $signed_date_objects = array_map(function($date) {
        return new DateTime($date);
    }, $signed_dates);

    // Verificar se as datas atuais são uma extensão direta das datas já assinadas
    $num_diarias_to_display = count($current_reservation_dates);

    echo "<p><strong>Diárias iniciais (só datas atuais):</strong> $num_diarias_to_display</p>\n";

    if (!empty($signed_date_objects) && !empty($current_reservation_dates)) {
        // Ordenar datas assinadas
        usort($signed_date_objects, function($a, $b) {
            return $a->getTimestamp() - $b->getTimestamp();
        });

        echo "<p><strong>Datas assinadas ordenadas:</strong> ";
        foreach ($signed_date_objects as $date) {
            echo $date->format('Y-m-d') . " ";
        }
        echo "</p>\n";

        $last_signed_date = end($signed_date_objects);
        $first_current_date = $current_reservation_dates[0];

        echo "<p><strong>Última data assinada:</strong> " . $last_signed_date->format('Y-m-d') . "</p>\n";
        echo "<p><strong>Primeira data atual:</strong> " . $first_current_date->format('Y-m-d') . "</p>\n";

        // Verificar se as datas atuais são uma continuação direta das datas assinadas
        $expected_date = clone $last_signed_date;
        $expected_date->modify('+1 day');

        echo "<p><strong>Data esperada (última assinada + 1 dia):</strong> " . $expected_date->format('Y-m-d') . "</p>\n";

        if ($first_current_date->format('Y-m-d') == $expected_date->format('Y-m-d')) {
            echo "<p><strong>RESULTADO:</strong> As datas atuais SÃO uma continuação direta das datas assinadas!</p>\n";
            // Sim, é uma continuação direta, então combinar todas as datas para o cálculo
            $all_reservation_dates_for_calc = array_merge($signed_date_objects, $current_reservation_dates);
            // Ordenar todas as datas
            usort($all_reservation_dates_for_calc, function($a, $b) {
                return $a->getTimestamp() - $b->getTimestamp();
            });
            // Contar o bloco total consecutivo que inclui datas já assinadas e as novas
            $num_diarias_to_display = count($all_reservation_dates_for_calc);
            echo "<p><strong>Todas as datas combinadas:</strong> ";
            foreach ($all_reservation_dates_for_calc as $date) {
                echo $date->format('Y-m-d') . " ";
            }
            echo "</p>\n";
        } else {
            echo "<p><strong>RESULTADO:</strong> As datas atuais NÃO são uma continuação direta das datas assinadas.</p>\n";
        }
    }
    
    echo "<p><strong>Diárias totais a exibir:</strong> $num_diarias_to_display</p>\n";
}

$conn->close();
?>