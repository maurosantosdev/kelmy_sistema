<?php
require 'db_connect.php';

// Inicia a sessão para verificar autenticação
session_start();

header('Content-Type: application/json');

// Protege a página, verificando se o admin está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$response = ['success' => false, 'message' => ''];
$action = $_GET['action'] ?? '';

// Ação para buscar os preços de um determinado mês
if ($action == 'get_month') {
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');

    $stmt = $conn->prepare("SELECT data, preco, status FROM agenda WHERE MONTH(data) = ? AND YEAR(data) = ?");
    $stmt->bind_param("ss", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prices = [];
    while($row = $result->fetch_assoc()){
        $prices[$row['data']] = ['preco' => $row['preco'], 'status' => $row['status']];
    }
    
    echo json_encode(['success' => true, 'prices' => $prices]);
    $stmt->close();
}
// Ação para salvar um novo período de preços
elseif ($action == 'save_period' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    $preco = $_POST['preco'] ?? 0;

    if (empty($data_inicio) || empty($data_fim) || $preco <= 0) {
        $response['message'] = 'Todos os campos são obrigatórios.';
        echo json_encode($response);
        exit;
    }
    
    $currentDate = new DateTime($data_inicio);
    $endDate = new DateTime($data_fim);

    $stmt = $conn->prepare("
        INSERT INTO agenda (data, preco, status) VALUES (?, ?, 'ativo')
        ON DUPLICATE KEY UPDATE preco = VALUES(preco), status = VALUES(status)
    ");
    
    while ($currentDate <= $endDate) {
        $dateStr = $currentDate->format('Y-m-d');
        $stmt->bind_param("sd", $dateStr, $preco);
        $stmt->execute();
        $currentDate->modify('+1 day');
    }
    
    $response['success'] = true;
    $response['message'] = 'Período salvo com sucesso!';
    $stmt->close();
    echo json_encode($response);
}

// --- NOVA AÇÃO PARA DELETAR UMA DATA ---
elseif ($action == 'delete_date' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $date_to_delete = $_POST['date'] ?? '';

    if (empty($date_to_delete)) {
        $response['message'] = 'Nenhuma data fornecida para exclusão.';
    } else {
        // Usa um prepared statement para segurança
        $stmt = $conn->prepare("DELETE FROM agenda WHERE data = ?");
        $stmt->bind_param("s", $date_to_delete);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Data removida com sucesso!';
            } else {
                $response['message'] = 'Nenhum registro encontrado para a data especificada.';
            }
        } else {
            $response['message'] = 'Erro ao tentar remover a data.';
        }
        $stmt->close();
    }
    echo json_encode($response);
}

// Ação para fazer logout
elseif ($action == 'logout') {
    session_start();
    session_destroy();
    echo json_encode(['success' => true]);
}
else {
    $response['message'] = 'Ação desconhecida.';
    echo json_encode($response);
}

$conn->close();
?>