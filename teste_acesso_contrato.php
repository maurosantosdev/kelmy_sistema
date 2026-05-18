<?php
// Script para testar o acesso ao contrato com login direto
session_start();

// Forçar login de um usuário existente (substitua com um usuário real do sistema)
// Primeiro vamos verificar os usuários existentes via código PHP
require 'php/db_connect.php';

echo "<h2>Teste de Acesso ao Contrato</h2>";

// Buscar um usuário existente
$stmt = $conn->query("SELECT id, nome, email FROM usuarios LIMIT 1");
if ($user = $stmt->fetch_assoc()) {
    echo "Usuário encontrado: " . $user['nome'] . " (ID: " . $user['id'] . ")<br>";
    
    // Forçar login do usuário
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nome'];
    
    echo "Sessão criada para o usuário: " . $user['nome'] . "<br>";
    
    // Agora tentar acessar as mesmas verificações feitas no contrato.php
    $user_id = $_SESSION['user_id'];
    
    // Obter informações do usuário
    $user_query = $conn->prepare("SELECT nome, data_nascimento, rg, cpf, rua, numero, bairro, cidade FROM usuarios WHERE id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_info = $user_query->get_result()->fetch_assoc();
    
    echo "<h3>Informações do Usuário:</h3>";
    echo "Nome: " . $user_info['nome'] . "<br>";
    echo "CPF: " . $user_info['cpf'] . "<br>";
    
    // Obter informações das reservas do usuário (pendentes ou confirmadas)
    $reservas_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem, r.payment_confirmed_at FROM reservas r WHERE r.user_id = ? AND (r.status = 'pendente' OR r.status = 'confirmado') ORDER BY r.data ASC");
    $reservas_query->bind_param("i", $user_id);
    $reservas_query->execute();
    $reservas_result = $reservas_query->get_result();

    $reservas = [];
    while ($reserva = $reservas_result->fetch_assoc()) {
        $reservas[] = $reserva;
    }
    
    echo "<h3>Reservas do Usuário:</h3>";
    if (count($reservas) > 0) {
        echo "Número de reservas: " . count($reservas) . "<br>";
        foreach ($reservas as $reserva) {
            echo "- ID: " . $reserva['id'] . ", Data: " . $reserva['data'] . ", Valor: R$" . $reserva['valor'] . ", Status: " . $reserva['status'] . "<br>";
        }
    } else {
        echo "Nenhuma reserva pendente ou confirmada encontrada.<br>";
    }
    
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
        echo "<h3>Status do Contrato:</h3>";
        echo "Contrato assinado: " . ($contrato_assinado ? 'Sim' : 'Não') . "<br>";
        echo "Reservas com contrato assinado: " . count($reservas_com_contrato_assinado) . "/" . count($reservas) . "<br>";
    } else {
        echo "<h3>Status do Contrato:</h3>";
        echo "Sem reservas, não é possível determinar status do contrato.<br>";
    }
    
    echo "<br><a href='cliente/contrato.php' target='_blank'>Clique aqui para abrir o contrato (em nova aba)</a><br>";
    echo "<small>Lembre-se: o contrato só aparecerá se houver reservas associadas ao usuário logado</small>";
} else {
    echo "Nenhum usuário encontrado no sistema. Por favor, cadastre um usuário primeiro.";
}

$conn->close();
?>