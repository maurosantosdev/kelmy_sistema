<?php
require_once 'db_connect.php';

// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_financial_data':
        getFinancialData();
        break;
    case 'get_status_data':
        getStatusData();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
        break;
}

function getFinancialData() {
    global $conn;

    // Obter dados do mês atual
    $current_month = date('m');
    $current_year = date('Y');

    // Obter dados do mês anterior
    $previous_month = date('m', strtotime('-1 month'));
    $previous_year = date('Y', strtotime('-1 month'));

    // Consulta para obter o total do mês atual
    // Filtrando por status confirmado e pendente (reservas ativas)
    $current_sql = "SELECT SUM(valor) as total FROM reservas WHERE MONTH(data) = ? AND YEAR(data) = ? AND (status = 'confirmado' OR status = 'pendente')";
    $current_stmt = $conn->prepare($current_sql);
    $current_stmt->bind_param("ii", $current_month, $current_year);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $current_data = $current_result->fetch_assoc();
    $current_total = $current_data['total'] ? floatval($current_data['total']) : 0;

    // Consulta para obter o total do mês anterior
    $previous_sql = "SELECT SUM(valor) as total FROM reservas WHERE MONTH(data) = ? AND YEAR(data) = ? AND (status = 'confirmado' OR status = 'pendente')";
    $previous_stmt = $conn->prepare($previous_sql);
    $previous_stmt->bind_param("ii", $previous_month, $previous_year);
    $previous_stmt->execute();
    $previous_result = $previous_stmt->get_result();
    $previous_data = $previous_result->fetch_assoc();
    $previous_total = $previous_data['total'] ? floatval($previous_data['total']) : 0;

    // Obter nomes dos meses
    $monthNames = [
        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
        '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
        '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
    ];

    $response = [
        'success' => true,
        'data' => [
            'current_month' => [
                'month' => $current_month,
                'year' => $current_year,
                'month_name' => $monthNames[$current_month],
                'total' => $current_total
            ],
            'previous_month' => [
                'month' => $previous_month,
                'year' => $previous_year,
                'month_name' => $monthNames[$previous_month],
                'total' => $previous_total
            ]
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
}

// Nova função para pegar dados de status para o gráfico de pizza
function getStatusData() {
    global $conn;

    // Contar reservas por status
    $sql = "SELECT status, COUNT(*) as count, SUM(valor) as total_value FROM reservas GROUP BY status";
    $result = $conn->query($sql);

    $statusData = [
        'success' => true,
        'data' => []
    ];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $statusData['data'][] = [
                'status' => $row['status'],
                'count' => (int)$row['count'],
                'total_value' => (float)$row['total_value']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($statusData);
}

$conn->close();
?>