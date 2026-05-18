<?php
require 'php/db_connect.php';

echo "Testing database connection and contract data...\n";

// Check if there are any records in descricao_contrato
$query = "SELECT * FROM descricao_contrato";
$result = $conn->query($query);

if ($result) {
    echo "descricao_contrato table query successful\n";
    echo "Number of rows: " . $result->num_rows . "\n";
    
    if ($result->num_rows > 0) {
        echo "Sample data from descricao_contrato:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: " . $row['id'] . ", Descricao: " . $row['descricao'] . ", Qtd: " . $row['qtd'] . "\n";
        }
    } else {
        echo "No data found in descricao_contrato table\n";
    }
} else {
    echo "Error querying descricao_contrato: " . $conn->error . "\n";
}

// Check if there are any reservations for the current user
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "\nCurrent user ID: " . $user_id . "\n";
    
    $reservas_query = "SELECT r.id, r.data, r.valor, r.status FROM reservas r WHERE r.user_id = ? AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC";
    $stmt = $conn->prepare($reservas_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $reservas_result = $stmt->get_result();
    
    if ($reservas_result) {
        echo "Number of reservations for user: " . $reservas_result->num_rows . "\n";
        
        if ($reservas_result->num_rows > 0) {
            while ($reserva = $reservas_result->fetch_assoc()) {
                echo "- Reserva ID: " . $reserva['id'] . ", Data: " . $reserva['data'] . ", Valor: " . $reserva['valor'] . ", Status: " . $reserva['status'] . "\n";
            }
        } else {
            echo "No reservations found for user\n";
        }
    } else {
        echo "Error querying reservations: " . $conn->error . "\n";
    }
} else {
    echo "\nNo user is currently logged in\n";
    
    // Check if there are any reservations in the system
    $all_reservas_query = "SELECT r.id, r.data, r.valor, r.status, r.user_id FROM reservas r WHERE r.status = 'pendente' OR r.status = 'confirmado' ORDER BY r.data ASC LIMIT 5";
    $all_reservas_result = $conn->query($all_reservas_query);
    
    if ($all_reservas_result) {
        echo "Sample reservations in the system:\n";
        while ($reserva = $all_reservas_result->fetch_assoc()) {
            echo "- User ID: " . $reserva['user_id'] . ", Reserva ID: " . $reserva['id'] . ", Data: " . $reserva['data'] . ", Valor: " . $reserva['valor'] . ", Status: " . $reserva['status'] . "\n";
        }
    }
}

// Check contratos_assinados table
$contratos_query = "SELECT * FROM contratos_assinados LIMIT 5";
$contratos_result = $conn->query($contratos_query);

if ($contratos_result) {
    echo "\ncontratos_assinados table - Number of rows: " . $contratos_result->num_rows . "\n";
    
    if ($contratos_result->num_rows > 0) {
        while ($row = $contratos_result->fetch_assoc()) {
            echo "- ID: " . $row['id'] . ", User ID: " . $row['user_id'] . ", Reserva ID: " . $row['reserva_id'] . ", Data: " . $row['data_assinatura'] . "\n";
        }
    } else {
        echo "No contracts signed yet\n";
    }
} else {
    echo "Error querying contratos_assinados: " . $conn->error . "\n";
}

$conn->close();
?>