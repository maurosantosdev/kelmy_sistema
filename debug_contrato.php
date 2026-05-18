<?php
// Debug para verificar o funcionamento do contrato
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'php/db_connect.php';

echo "<h2>Debug do Contrato</h2>";

// Verificar autenticação
echo "<h3>Autenticação:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "Usuário autenticado: Sim<br>";
    echo "ID do usuário: " . $_SESSION['user_id'] . "<br>";
    echo "Nome do usuário: " . ($_SESSION['user_name'] ?? 'Não definido') . "<br>";
} else {
    echo "Usuário autenticado: Não<br>";
    echo "Redirecionando para login...<br>";
    echo "<script>setTimeout(function(){ window.location.href = 'cliente/login.php'; }, 3000);</script>";
}

// Verificar reservas do usuário
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<h3>Reservas do Usuário (ID: $user_id):</h3>";
    
    $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem, r.payment_confirmed_at FROM reservas r WHERE r.user_id = ? AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC");
    $reservas_query->bind_param("i", $user_id);
    $reservas_query->execute();
    $reservas_result = $reservas_query->get_result();
    
    if ($reservas_result->num_rows > 0) {
        echo "Número de reservas encontradas: " . $reservas_result->num_rows . "<br>";
        while ($reserva = $reservas_result->fetch_assoc()) {
            echo "- ID: " . $reserva['id'] . ", Data: " . $reserva['data'] . ", Valor: R$" . $reserva['valor'] . ", Status: " . $reserva['status'] . "<br>";
        }
    } else {
        echo "Nenhuma reserva pendente ou confirmada encontrada para este usuário.<br>";
    }
    
    // Verificar todas as reservas do usuário (independente do status)
    $all_reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status FROM reservas r WHERE r.user_id = ? ORDER BY r.data ASC");
    $all_reservas_query->bind_param("i", $user_id);
    $all_reservas_query->execute();
    $all_reservas_result = $all_reservas_query->get_result();
    
    echo "<h4>Todas as reservas do usuário (independentemente do status):</h4>";
    if ($all_reservas_result->num_rows > 0) {
        while ($reserva = $all_reservas_result->fetch_assoc()) {
            echo "- ID: " . $reserva['id'] . ", Data: " . $reserva['data'] . ", Valor: R$" . $reserva['valor'] . ", Status: " . $reserva['status'] . "<br>";
        }
    } else {
        echo "Nenhuma reserva encontrada para este usuário.<br>";
    }
}

// Verificar parâmetros da URL
echo "<h3>Parâmetros da URL:</h3>";
echo "reserva_id: " . (isset($_GET['reserva_id']) ? $_GET['reserva_id'] : 'Não fornecido') . "<br>";
echo "reservas_ids: " . (isset($_GET['reservas_ids']) ? $_GET['reservas_ids'] : 'Não fornecido') . "<br>";

// Verificar contratos assinados
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<h3>Contratos Assinados:</h3>";
    $assinatura_query = $conn->prepare("SELECT ca.reserva_id, r.data, r.status FROM contratos_assinados ca LEFT JOIN reservas r ON ca.reserva_id = r.id WHERE ca.user_id = ?");
    $assinatura_query->bind_param("i", $user_id);
    $assinatura_query->execute();
    $assinatura_result = $assinatura_query->get_result();
    
    if ($assinatura_result->num_rows > 0) {
        echo "Contratos assinados encontrados:<br>";
        while ($assinatura = $assinatura_result->fetch_assoc()) {
            echo "- Reserva ID: " . $assinatura['reserva_id'] . ", Data: " . ($assinatura['data'] ?? 'N/A') . ", Status: " . ($assinatura['status'] ?? 'N/A') . "<br>";
        }
    } else {
        echo "Nenhum contrato assinado encontrado.<br>";
    }
}

$conn->close();

echo "<br><a href='cliente/contrato.php'>Ir para a página do contrato</a>";
?>