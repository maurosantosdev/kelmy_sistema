<?php
// Linhas para forçar a exibição de qualquer erro do PHP. Essencial para diagnóstico.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connect.php';

// Inicia a sessão para verificar autenticação
session_start();

header('Content-Type: application/json');

// --- Bloco de Segurança: Verifica se o Admin está logado ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$action = $_GET['action'] ?? '';
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/repo_limpo/uploads/';

// --- ESTRUTURA LÓGICA PRINCIPAL COM IF/ELSEIF/ELSE ---

// --- AÇÃO: BUSCAR INFORMAÇÕES (GET) ---
if ($action == 'get_info') {
    $result = $conn->query("SELECT texto_info, fotos FROM informacoes WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $decoded_fotos = json_decode($data['fotos'] ?? '[]', true);
        $data['fotos'] = is_array($decoded_fotos) ? $decoded_fotos : [];
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum dado encontrado na tabela informacoes.']);
    }
}

// --- AÇÃO: SALVAR TODAS AS INFORMAÇÕES (SAVE) ---
elseif ($action == 'save_info' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        echo json_encode(['success' => false, 'message' => 'ERRO DE SERVIDOR: A pasta /uploads não existe ou não tem permissão de escrita.']);
        exit;
    }

    $info_texto = $_POST['info_texto'] ?? '';
    $ordered_media = json_decode($_POST['order'] ?? '[]', true);
    
    if (!is_array($ordered_media)) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: A ordem das mídias é inválida.']);
        exit;
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4'];
    $has_uploads = isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0]);

    if ($has_uploads) {
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            $error_code = $_FILES['fotos']['error'][$key];
            if ($error_code === UPLOAD_ERR_OK) {
                $file_name = $_FILES['fotos']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_file_name = uniqid() . '-' . basename($file_name);
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_file_name)) {
                        $ordered_media[] = $new_file_name;
                    } else {
                        echo json_encode(['success' => false, 'message' => "Erro crítico ao mover o arquivo '{$file_name}'."]);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => "Erro: O formato do arquivo '{$file_name}' não é permitido."]);
                    exit;
                }
            } elseif ($error_code !== UPLOAD_ERR_NO_FILE) {
                echo json_encode(['success' => false, 'message' => "Erro no upload do arquivo '{$_FILES['fotos']['name'][$key]}'. O arquivo pode ser muito grande."]);
                exit;
            }
        }
    }

    $media_json = json_encode(array_values($ordered_media));
    $stmt = $conn->prepare("UPDATE informacoes SET texto_info = ?, fotos = ? WHERE id = 1");
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro na preparação da query SQL: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $info_texto, $media_json);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Informações atualizadas com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao executar a query no banco: ' . $stmt->error]);
    }
    $stmt->close();
}

// --- AÇÃO: DELETAR UMA MÍDIA (DELETE) ---
elseif ($action == 'delete_image' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $filename = $_POST['filename'] ?? '';
    if (!empty($filename)) {
        if (file_exists($upload_dir . $filename)) {
            unlink($upload_dir . $filename);
        }
        $result = $conn->query("SELECT fotos FROM informacoes WHERE id = 1");
        $row = $result->fetch_assoc();
        $media = json_decode($row['fotos'] ?? '[]', true);
        if (($key = array_search($filename, $media)) !== false) {
            unset($media[$key]);
        }
        $media_json = json_encode(array_values($media));
        $stmt = $conn->prepare("UPDATE informacoes SET fotos = ? WHERE id = 1");
        $stmt->bind_param("s", $media_json);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Mídia removida.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum nome de arquivo fornecido.']);
    }
}

// --- Se nenhuma ação corresponder ---
else {
    echo json_encode(['success' => false, 'message' => "Ação desconhecida ou método de requisição inválido."]);
}

$conn->close();
?>