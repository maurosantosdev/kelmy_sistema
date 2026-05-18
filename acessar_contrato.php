<?php
// Script para forçar o acesso à página do contrato com o usuário logado
session_start();

// Forçar login do usuário com reserva
$_SESSION['user_id'] = 105;
$_SESSION['user_email'] = 'mauro@example.com'; // substituir com email real se necessário
$_SESSION['user_name'] = 'MAURO DENISON SANTOS SILVA';

// Redirecionar para o contrato
header("Location: cliente/contrato.php");
exit();
?>