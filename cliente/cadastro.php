<?php
// Verificar se o usuário está autenticado
session_start();
if (isset($_SESSION['user_id'])) {
    // Se o usuário já estiver logado, redirecionar para a página de reserva
    header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="../assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div data-role="page" id="cadastroPageCliente">
    <div data-role="header" data-position="fixed">
        <h1>Cadastro</h1>
    </div>

    <div role="main" class="ui-content">

        <div class="card">
            <h2>Cadastro</h2>
            <form id="cadastroForm" method="post" action="../php/cliente_cadastro.php" data-transition="none">
                <label for="nome">Nome Completo:</label>
                <input type="text" name="nome" id="nome" required>
                
                <label for="rg">RG:</label>
                <input type="text" name="rg" id="rg" placeholder="00.000.000-X" required>
                
                <label for="cpf">CPF:</label>
                <input type="text" name="cpf" id="cpf" placeholder="000.000.000-00" required>
                
                <label for="estado_civil">Estado Civil:</label>
                <select name="estado_civil" id="estado_civil" required>
                    <option value="">Selecione...</option>
                    <option value="solteiro">Solteiro(a)</option>
                    <option value="casado">Casado(a)</option>
                    <option value="divorciado">Divorciado(a)</option>
                    <option value="viuvo">Viúvo(a)</option>
                    <option value="separado">Separado(a)</option>
                </select>
                
                <label for="rua">Rua:</label>
                <input type="text" name="rua" id="rua" required>
                
                <label for="numero">Número:</label>
                <input type="text" name="numero" id="numero" required>
                
                <label for="bairro">Bairro:</label>
                <input type="text" name="bairro" id="bairro" required>
                
                <label for="cep">CEP:</label>
                <input type="text" name="cep" id="cep" placeholder="00000-000" required>
                
                <label for="cidade">Cidade:</label>
                <input type="text" name="cidade" id="cidade" required>
                
                <label for="telefone">Telefone:</label>
                <input type="tel" name="telefone" id="telefone" placeholder="(00) 00000-0000" required>
                
                <label for="data_nascimento">Data de Nascimento:</label>
                <input type="date" name="data_nascimento" id="data_nascimento" required>
                
                <label for="email">E-mail:</label>
                <input type="email" name="email" id="email" required>
                
                <label for="senha">Senha:</label>
                <input type="password" name="senha" id="senha" required>
                
                <button type="submit" class="ui-btn ui-btn-b">Cadastrar</button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="login.php" class="ui-btn">Já tem conta? Faça login</a>
        </div>

    </div>

    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-icon="home">Inicio</a></li>
                <li><a href="reserva.php" data-icon="grid">Reserve</a></li>
                <li><a href="suas_reservas.php" data-icon="calendar">Reservas</a></li>
                <li><a href="perfil.php" data-icon="user">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/jquery.mobile-1.4.5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="cadastro.js"></script>



</body>
</html>