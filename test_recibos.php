<?php
// Script de teste para verificar a decodificação de reservas_ids

// Simular diferentes tipos de entradas que podem chegar via URL
$test_inputs = [
    '["res_6912a3f2010734.85378154","res_6912a3f2010630.79297144"]', // JSON normal
    '%5B%22res_6912a3f2010734.85378154%22%2C%22res_6912a3f2010630.79297144%22%5D', // URL codificada
    '["res_6912a3f2010734.85378154","\\"res_6912a3f2010630.79297144\\""]', // Com aspas escapadas
];

echo "Testando a decodificação de reservas_ids...\n\n";

foreach ($test_inputs as $index => $reservas_ids_input) {
    echo "Teste " . ($index + 1) . ": $reservas_ids_input\n";
    
    // Aplicar a mesma lógica de decodificação usada no recibos.php
    // Primeiro, tentar decodificar diretamente como JSON
    $reservas_ids = json_decode($reservas_ids_input, true);

    // Se a decodificação direta falhar, tentar manipular a string para corrigir possíveis problemas de codificação
    if (!is_array($reservas_ids) || empty($reservas_ids)) {
        // Tentar decodificar com urldecode
        $reservas_ids = json_decode(urldecode($reservas_ids_input), true);
    }

    // Se ainda não funcionar, verificar se é uma string que parece um array JSON
    if (!is_array($reservas_ids) || empty($reservas_ids)) {
        // Remover possíveis aspas extras e tentar decodificar novamente
        $cleaned_input = trim($reservas_ids_input, '"\'');
        $reservas_ids = json_decode($cleaned_input, true);
        
        // Se ainda não funcionar, tentar decodificar após urldecode
        if (!is_array($reservas_ids) || empty($reservas_ids)) {
            $reservas_ids = json_decode(urldecode($cleaned_input), true);
        }
    }

    // Como último recurso, tentar usar parse_str ou outra abordagem se o JSON estiver mal formado
    if (!is_array($reservas_ids) || empty($reservas_ids)) {
        // Tentar extrair os IDs com expressão regular
        $pattern = '/res_[a-f0-9_\.]+/';
        preg_match_all($pattern, $reservas_ids_input, $matches);
        if (!empty($matches[0])) {
            $reservas_ids = $matches[0];
        }
    }

    if (is_array($reservas_ids) && !empty($reservas_ids)) {
        echo "  Resultado: [" . implode(', ', $reservas_ids) . "]\n";
        echo "  Status: SUCESSO\n\n";
    } else {
        echo "  Status: FALHOU\n\n";
    }
}
?>