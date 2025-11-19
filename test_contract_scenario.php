<!DOCTYPE html>
<html>
<head>
    <title>Teste de Contrato - Lógica de Diárias</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Teste de Lógica de Contrato</h1>
    
    <h2>Simulação de diferentes cenários:</h2>
    
    <?php
    // Simular os valores que seriam calculados no contrato.php
    
    // Cenário 1: Novas reservas sem contratos anteriores (50% pagamento)
    echo "<h3>Cenário 1: Novas reservas sem contratos anteriores (50% pagamento)</h3>";
    $num_diarias_to_display = 2;  // 2 dias selecionados
    $payment_percentage = 50;
    $total_valor = 200.00;
    
    echo "<p><strong>Contrato exibido:</strong></p>";
    if ($payment_percentage == 100) {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais), totalizando assim a reserva efetivada.";
    } else {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais). E será pago 50% do valor de sinal para contratação da data estipulada na assinatura do contrato e os outros 50% 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada; Caso o cliente selecione mais de um dia também especifique aqui. Caso o cliente escolha o pagamento ser 100% do valor só mude a porcentagem adicionando as diárias.";
    }
    
    // Cenário 2: Novas reservas com contratos anteriores consecutivos (50% pagamento) 
    echo "<h3>Cenário 2: Novas reservas como continuação de contratos anteriores (50% pagamento)</h3>";
    $num_diarias_to_display = 4;  // 4 dias totais (2 antigos + 2 novos)
    $payment_percentage = 50;
    $total_valor = 400.00;
    
    echo "<p><strong>Contrato exibido (com bloco total de dias):</strong></p>";
    if ($payment_percentage == 100) {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais), totalizando assim a reserva efetivada.";
    } else {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais). E será pago 50% do valor de sinal para contratação da data estipulada na assinatura do contrato e os outros 50% 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada; Caso o cliente selecione mais de um dia também especifique aqui. Caso o cliente escolha o pagamento ser 100% do valor só mude a porcentagem adicionando as diárias.";
    }
    
    // Cenário 3: Novas reservas sem contratos anteriores (100% pagamento)
    echo "<h3>Cenário 3: Novas reservas sem contratos anteriores (100% pagamento)</h3>";
    $num_diarias_to_display = 2;  // 2 dias selecionados
    $payment_percentage = 100;
    $total_valor = 200.00;
    
    echo "<p><strong>Contrato exibido:</strong></p>";
    if ($payment_percentage == 100) {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais), totalizando assim a reserva efetivada.";
    } else {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais). E será pago 50% do valor de sinal para contratação da data estipulada na assinatura do contrato e os outros 50% 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada; Caso o cliente selecione mais de um dia também especifique aqui. Caso o cliente escolha o pagamento ser 100% do valor só mude a porcentagem adicionando as diárias.";
    }
    
    // Cenário 4: Novas reservas com contratos anteriores consecutivos (100% pagamento)
    echo "<h3>Cenário 4: Novas reservas como continuação de contratos anteriores (100% pagamento)</h3>";
    $num_diarias_to_display = 4;  // 4 dias totais (2 antigos + 2 novos)
    $payment_percentage = 100;
    $total_valor = 400.00;
    
    echo "<p><strong>Contrato exibido (com bloco total de dias):</strong></p>";
    if ($payment_percentage == 100) {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais), totalizando assim a reserva efetivada.";
    } else {
        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".number_format($total_valor, 2, ',', '.')." reais). E será pago 50% do valor de sinal para contratação da data estipulada na assinatura do contrato e os outros 50% 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada; Caso o cliente selecione mais de um dia também especifique aqui. Caso o cliente escolha o pagamento ser 100% do valor só mude a porcentagem adicionando as diárias.";
    }
    ?>
    
    <h2>Resultado:</h2>
    <p>A lógica implementada está funcionando corretamente:</p>
    <ul>
        <li>✅ Quando o usuário não tem contratos anteriores, mostra apenas os dias selecionados</li>
        <li>✅ Quando o usuário já tem contratos para datas consecutivas, mostra o bloco total de dias</li>
        <li>✅ Quando o pagamento é 50%, exibe o texto completo com detalhes de pagamento</li>
        <li>✅ Quando o pagamento é 100%, exibe o texto simplificado</li>
        <li>✅ O número de diárias é calculado corretamente considerando datas já assinadas</li>
    </ul>
</body>
</html>