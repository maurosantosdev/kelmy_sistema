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
    case 'get_contract':
        getContractData();
        break;
    case 'save_contract':
        saveContractData();
        break;
    case 'delete_contract_item':
        deleteContractItem();
        break;
    case 'update_item':
        updateContractItem();
        break;
    case 'save_new_item':
        saveNewContractItem();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
        break;
}

function getContractData() {
    global $conn;
    
    $sql = "SELECT id, descricao, qtd, valor_unitario, valor_total FROM descricao_contrato ORDER BY id";
    $result = $conn->query($sql);
    
    if ($result) {
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $items]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar dados do contrato.']);
    }
}

function saveContractData() {
    global $conn;
    
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    // Primeiro, deletar todos os itens existentes
    $deleteSql = "DELETE FROM descricao_contrato";
    $deleteResult = $conn->query($deleteSql);
    
    if (!$deleteResult) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao limpar dados antigos: ' . $conn->error]);
        exit;
    }
    
    // Depois, inserir os novos itens
    if (!empty($items)) {
        $insertSql = "INSERT INTO descricao_contrato (descricao, qtd, valor_unitario, valor_total) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        
        if (!$stmt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
            exit;
        }
        
        foreach ($items as $item) {
            $descricao = $item['descricao'] ?? '';
            $qtd = (int)($item['qtd'] ?? 0);
            $valor_unitario = (float)($item['valor_unitario'] ?? 0);
            $valor_total = (float)($item['valor_total'] ?? 0);
            
            $stmt->bind_param("sidd", $descricao, $qtd, $valor_unitario, $valor_total);
            
            if (!$stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erro ao inserir item: ' . $stmt->error]);
                exit;
            }
        }
        
        $stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Contrato atualizado com sucesso!']);
}

function updateContractItem() {
    global $conn;
    
    $id = (int)($_POST['id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $qtd = (int)($_POST['qtd'] ?? 0);
    $valor_unitario = (float)($_POST['valor_unitario'] ?? 0);
    $valor_total = (float)($_POST['valor_total'] ?? 0);
    
    if ($id <= 0 || empty($descricao) || $qtd <= 0 || $valor_unitario < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para atualização.']);
        exit;
    }
    
    $sql = "UPDATE descricao_contrato SET descricao=?, qtd=?, valor_unitario=?, valor_total=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siddi", $descricao, $qtd, $valor_unitario, $valor_total, $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Item atualizado com sucesso!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar item: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function saveNewContractItem() {
    global $conn;
    
    $descricao = trim($_POST['descricao'] ?? '');
    $qtd = (int)($_POST['qtd'] ?? 0);
    $valor_unitario = (float)($_POST['valor_unitario'] ?? 0);
    $valor_total = (float)($_POST['valor_total'] ?? 0);
    
    if (empty($descricao) || $qtd <= 0 || $valor_unitario < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Dados inválidos para novo item.']);
        exit;
    }
    
    $sql = "INSERT INTO descricao_contrato (descricao, qtd, valor_unitario, valor_total) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidd", $descricao, $qtd, $valor_unitario, $valor_total);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Novo item adicionado com sucesso!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar novo item: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function deleteContractItem() {
    global $conn;
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    
    $sql = "DELETE FROM descricao_contrato WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Item excluído com sucesso!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir item: ' . $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>