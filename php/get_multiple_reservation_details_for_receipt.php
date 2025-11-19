<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Retornar erro se não estiver autenticado
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Pegar os IDs das reservas via GET
$reservas_ids_json = $_GET['reservas_ids'] ?? '';
if (empty($reservas_ids_json)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'IDs das reservas inválidos']);
    exit();
}

$reservas_ids = json_decode($reservas_ids_json, true);
if (!is_array($reservas_ids) || empty($reservas_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'IDs das reservas inválidos']);
    exit();
}

// Conectar ao banco de dados
require_once 'db_connect.php';

// Validar que todas as reservas pertencem ao usuário e estão confirmadas
$placeholders = str_repeat('?,', count($reservas_ids) - 1) . '?';
$stmt = $conn->prepare("
    SELECT r.*, u.nome, u.cpf, u.email
    FROM reservas r
    JOIN usuarios u ON r.user_id = u.id
    WHERE r.id IN ($placeholders) AND r.user_id = ?
    ORDER BY r.data ASC
");
$params = array_merge($reservas_ids, [$_SESSION['user_id']]);
$types = str_repeat('s', count($reservas_ids)) . 'i';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== count($reservas_ids)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Uma ou mais reservas não encontradas ou não pertencem ao usuário']);
    exit();
}

$reservas = [];
$total_valor_pago = 0;
$datas_reservas = [];
$cliente_nome = '';
$cliente_documento = '';

while ($reserva = $result->fetch_assoc()) {
    // Verificar se a reserva está confirmada e tem data de pagamento
    if ($reserva['status'] !== 'confirmado' || !$reserva['payment_confirmed_at']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Apenas reservas confirmadas com pagamento realizado podem emitir recibo']);
        exit();
    }
    
    $reservas[] = $reserva;
    $total_valor_pago += floatval($reserva['valor_pago'] ?? $reserva['valor']);
    $datas_reservas[] = $reserva['data'];
    
    if (empty($cliente_nome)) {
        $cliente_nome = $reserva['nome'];
        $cliente_documento = $reserva['cpf'] ?? 'N/A';
    }
}

// Ordenar datas
sort($datas_reservas);

// Calcular data início e fim
$data_inicio = $datas_reservas[0];
$data_fim = date('Y-m-d', strtotime(end($datas_reservas) . ' +1 day')); // Um dia após a última reserva

// Formatar datas para o recibo
$data_inicio_formatada = date('d/m/Y', strtotime($data_inicio));
$data_fim_formatada = date('d/m/Y', strtotime($data_fim));

// Calcular horas totais (24h * número de dias - 1)
$num_dias = count($datas_reservas);
$horas_totais = ($num_dias * 24) - 1;

// Formatar valor total
$valor_total_pago = 'R$ ' . number_format($total_valor_pago, 2, ',', '.');

// Retornar informações para o recibo combinado
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'num_reservas' => count($reservas),
    'reservas' => array_map(function($res) {
        return [
            'id' => $res['id'],
            'data' => date('d/m/Y', strtotime($res['data'])),
            'valor_pago' => 'R$ ' . number_format(floatval($res['valor_pago'] ?? $res['valor']), 2, ',', '.')
        ];
    }, $reservas),
    'data_inicio' => $data_inicio_formatada,
    'data_fim' => $data_fim_formatada,
    'horas_totais' => $horas_totais,
    'valor_pago' => $valor_total_pago,
    'cliente_nome' => $cliente_nome,
    'cliente_documento' => $cliente_documento,
    'numero_diarias' => $num_dias
]);

$stmt->close();
$conn->close();
?>