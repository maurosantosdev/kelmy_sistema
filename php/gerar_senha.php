<?php
// Defina a senha que você quer usar
$senha_texto_puro = '1234567890';

// Criptografa a senha usando o mesmo método do script de login
$senha_criptografada = hash('sha512', $senha_texto_puro);

// Exibe a senha criptografada na tela
echo "<h1>Senha Criptografada (SHA-512)</h1>";
echo "<p>Sua senha '1234567890' criptografada é:</p>";
echo "<textarea rows='5' cols='130' readonly>" . $senha_criptografada . "</textarea>";
echo "<p>Copie o texto acima e cole no campo 'senha' do seu banco de dados.</p>";
?>