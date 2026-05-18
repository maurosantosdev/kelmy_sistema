<?php
// Script de debug detalhado para o contrato

// Simular uma sessão de usuário para testes
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir um ID de usuário para testes (substitua por um ID real do seu banco de dados)
if (!isset($_SESSION['user_id'])) {
    // Buscar o ID de um usuário existente no banco
    require_once 'php/db_connect.php';
    $temp_conn = $conn; // Salvar a conexão temporária
    $result = $temp_conn->query("SELECT id FROM usuarios LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        echo "INFO: Simulando sessão para usuário ID: " . $row['id'] . "\n";
    } else {
        echo "ERRO: Nenhum usuário encontrado no banco de dados\n";
        exit();
    }
    $temp_conn->close();
}

require_once 'php/db_connect.php';

// Obter o ID da reserva da URL (se fornecido)
$reserva_id_fornecido = isset($_GET['reserva_id']) ? $_GET['reserva_id'] : null;

// Obter IDs das reservas da URL (se fornecido como JSON)
$reservas_ids_json = isset($_GET['reservas_ids']) ? $_GET['reservas_ids'] : null;
$reservas_ids = null;

if ($reservas_ids_json) {
    $reservas_ids_array = json_decode($reservas_ids_json, true);
    if (is_array($reservas_ids_array)) {
        $reservas_ids = $reservas_ids_array;
    }
}

echo "<h2>DEBUG DETALHADO DO CONTRATO</h2>\n";
echo "<p><strong>Session user_id:</strong> " . $_SESSION['user_id'] . "</p>\n";
echo "<p><strong>reserva_id_fornecido:</strong> " . ($reserva_id_fornecido ?: 'null') . "</p>\n";
echo "<p><strong>reservas_ids_json:</strong> " . ($reservas_ids_json ?: 'null') . "</p>\n";
echo "<p><strong>reservas_ids:</strong> " . ($reservas_ids ? json_encode($reservas_ids) : 'null') . "</p>\n";

// Obter informações do usuário
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT nome, data_nascimento, rg, cpf, rua, numero, bairro, cidade FROM usuarios WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

echo "<h3>Informações do Usuário:</h3>\n";
if ($user) {
    echo "<pre>" . print_r($user, true) . "</pre>\n";
} else {
    echo "<p>Usuário não encontrado!</p>\n";
}

// Obter informações das reservas do usuário (pendentes ou confirmadas)
if ($reservas_ids) {
    // Se IDs de múltiplas reservas foram fornecidos, buscar essas reservas específicas
    if (!empty($reservas_ids)) {
        $placeholders = str_repeat('?,', count($reservas_ids) - 1) . '?';
        $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem, r.payment_confirmed_at FROM reservas r WHERE r.user_id = ? AND r.id IN ($placeholders) AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC");
        $types = 'i' . str_repeat('s', count($reservas_ids));
        $params = array_merge([$user_id], $reservas_ids);
        $reservas_query->bind_param($types, ...$params);
    } else {
        // Caso o array esteja vazio, buscar todas as reservas pendentes ou confirmadas
        $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem, r.payment_confirmed_at FROM reservas r WHERE r.user_id = ? AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC");
        $reservas_query->bind_param("i", $user_id);
    }
} elseif ($reserva_id_fornecido) {
    // Se um ID de reserva específico foi fornecido, buscar apenas essa reserva
    $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem, r.payment_confirmed_at FROM reservas r WHERE r.user_id = ? AND r.id = ? AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC");
    $reservas_query->bind_param("is", $user_id, $reserva_id_fornecido);
} else {
    // Se nenhum ID específico foi fornecido, buscar todas as reservas pendentes ou confirmadas
    $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem, r.payment_confirmed_at FROM reservas r WHERE r.user_id = ? AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC");
    $reservas_query->bind_param("i", $user_id);
}

if (!$reservas_query->execute()) {
    echo "<p>Erro ao executar a consulta de reservas: " . $reservas_query->error . "</p>\n";
    $reservas = [];
} else {
    $reservas_result = $reservas_query->get_result();

    if (!$reservas_result) {
        echo "<p>Erro ao obter resultados da consulta de reservas: " . $conn->error . "</p>\n";
        $reservas = [];
    } else {
        $reservas = [];
        while ($reserva = $reservas_result->fetch_assoc()) {
            $reservas[] = $reserva;
        }
    }
}

echo "<h3>Reservas encontradas:</h3>\n";
echo "<pre>" . print_r($reservas, true) . "</pre>\n";

// Verificar se o contrato já foi assinado para cada reserva individualmente
$contrato_assinado = false;
$reservas_com_contrato_assinado = [];

if (!empty($reservas)) {
    foreach ($reservas as $reserva) {
        $assinatura_query = $conn->prepare("SELECT COUNT(*) as total FROM contratos_assinados WHERE user_id = ? AND reserva_id = ?");
        $assinatura_query->bind_param("is", $user_id, $reserva['id']);
        $assinatura_query->execute();
        $assinatura_result = $assinatura_query->get_result();
        $assinatura = $assinatura_result->fetch_assoc();

        if ($assinatura['total'] > 0) {
            $reservas_com_contrato_assinado[] = $reserva['id'];
        }
    }

    // O contrato está considerado assinado se todas as reservas atuais já foram assinadas
    $contrato_assinado = count($reservas_com_contrato_assinado) == count($reservas);

    // Calcular o número real de diárias considerando apenas as datas sendo assinadas agora
    if (count($reservas) > 0) {
        $num_diarias_to_display = count($reservas);
    } else {
        $num_diarias_to_display = 0;
    }
} else {
    $num_diarias_to_display = 0;
}

echo "<h3>Status do contrato:</h3>\n";
echo "<p><strong>contrato_assinado:</strong> " . ($contrato_assinado ? 'true' : 'false') . "</p>\n";
echo "<p><strong>reservas_com_contrato_assinado:</strong> " . json_encode($reservas_com_contrato_assinado) . "</p>\n";
echo "<p><strong>num_diarias_to_display:</strong> " . $num_diarias_to_display . "</p>\n";

// Obter informações da tabela descricao_contrato
$descricao_query = $conn->prepare("SELECT descricao, qtd, valor_unitario, valor_total FROM descricao_contrato");
if (!$descricao_query) {
    echo "<p>Erro na preparação da consulta de descrição do contrato: " . $conn->error . "</p>\n";
    $descricao_itens = [];
} else {
    if (!$descricao_query->execute()) {
        echo "<p>Erro ao executar a consulta de descrição do contrato: " . $descricao_query->error . "</p>\n";
        $descricao_itens = [];
    } else {
        $descricao_result = $descricao_query->get_result();
        if (!$descricao_result) {
            echo "<p>Erro ao obter resultados da consulta de descrição do contrato: " . $conn->error . "</p>\n";
            $descricao_itens = [];
        } else {
            $descricao_itens = [];
            while ($item = $descricao_result->fetch_assoc()) {
                $descricao_itens[] = $item;
            }
        }
    }
}

echo "<h3>Itens do contrato:</h3>\n";
echo "<pre>" . print_r($descricao_itens, true) . "</pre>\n";

$conn->close();

echo "<h3>Resultado final:</h3>\n";
if (count($reservas) > 0) {
    echo "<p style='color: green;'>✓ O contrato deve aparecer com " . count($reservas) . " reserva(s).</p>\n";
} else {
    echo "<p style='color: red;'>✗ Nenhuma reserva encontrada - o contrato não aparecerá.</p>\n";
    echo "<p>Provavelmente o problema está relacionado ao parâmetro enviado na URL ou à ausência de reservas válidas para o usuário.</p>\n";
}
?>