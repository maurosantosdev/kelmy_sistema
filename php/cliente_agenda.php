<?php
// Não inicia a sessão, pois não é necessário para o cliente
require 'db_connect.php'; // Apenas conecta ao banco
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];
$action = $_GET['action'] ?? '';

// Ação para buscar os preços de um determinado mês (aberta para o cliente)
if ($action == 'get_month') {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');

    // Prepara a query para buscar apenas as datas que estão com status 'ativo', 'reservado' ou 'pendente'
    $stmt = $conn->prepare("SELECT data, preco, status FROM agenda WHERE MONTH(data) = ? AND YEAR(data) = ? AND (status = 'ativo' OR status = 'reservado' OR status = 'pendente')");
    $stmt->bind_param("ss", $month, $year);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $prices = [];
        while($row = $result->fetch_assoc()){
            $status = $row['status'];
            
            // Se o status for 'reservado', verificar se foi confirmado há menos de 5 segundos
            if ($status === 'reservado') {
                // Verificar se há reservas confirmadas para esta data que foram confirmadas há menos de 5 segundos
                $check_reservation = $conn->prepare("SELECT payment_confirmed_at FROM reservas WHERE data = ? AND status = 'confirmado' ORDER BY payment_confirmed_at DESC LIMIT 1");
                $check_reservation->bind_param("s", $row['data']);
                $check_reservation->execute();
                $reservation_result = $check_reservation->get_result();
                
                if ($reservation_row = $reservation_result->fetch_assoc()) {
                    $payment_confirmed_at = $reservation_row['payment_confirmed_at'];
                    $current_time = date('Y-m-d H:i:s');
                    $time_diff = strtotime($current_time) - strtotime($payment_confirmed_at);
                    
                    // Se foi confirmado há menos de 5 segundos, mostrar como 'pendente' temporariamente
                    if ($time_diff < 5) {
                        $status = 'pendente'; // Por 5 segundos, ainda mostra como pendente no calendário
                    }
                }
            }
            
            // Formata o preço para o padrão brasileiro antes de enviar
            $row['preco'] = number_format($row['preco'], 2, ',', '.');
            $prices[$row['data']] = ['preco' => $row['preco'], 'status' => $status];
        }
        $response['success'] = true;
        $response['prices'] = $prices;
    } else {
        $response['message'] = "Erro ao executar a consulta ao banco de dados.";
    }
    $stmt->close();
} else {
    $response['message'] = 'Ação desconhecida.';
}

$conn->close();
echo json_encode($response);
?>