<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Redirecionar para página de login se não estiver autenticado
    header("Location: login.php");
    exit();
}

require '../php/db_connect.php';

// Função para converter números em extenso (até 1000)
function numero_extenso($numero) {
    $unidades = [
        0 => 'zero', 1 => 'um', 2 => 'dois', 3 => 'três', 4 => 'quatro', 5 => 'cinco',
        6 => 'seis', 7 => 'sete', 8 => 'oito', 9 => 'nove', 10 => 'dez',
        11 => 'onze', 12 => 'doze', 13 => 'treze', 14 => 'catorze', 15 => 'quinze',
        16 => 'dezesseis', 17 => 'dezessete', 18 => 'dezoito', 19 => 'dezenove', 20 => 'vinte',
        30 => 'trinta', 40 => 'quarenta', 50 => 'cinquenta', 60 => 'sessenta',
        70 => 'setenta', 80 => 'oitenta', 90 => 'noventa', 100 => 'cem',
        200 => 'duzentos', 300 => 'trezentos', 400 => 'quatrocentos', 500 => 'quinhentos',
        600 => 'seiscentos', 700 => 'setecentos', 800 => 'oitocentos', 900 => 'novecentos'
    ];

    if ($numero <= 20) {
        return $unidades[$numero];
    } elseif ($numero < 100) {
        $dezena = floor($numero / 10) * 10;
        $unidade = $numero % 10;
        if ($unidade == 0) {
            return $unidades[$dezena];
        } else {
            return $unidades[$dezena] . ' e ' . $unidades[$unidade];
        }
    } elseif ($numero < 1000) {
        $centena = floor($numero / 100) * 100;
        $resto = $numero % 100;
        
        if ($centena == 100 && $resto == 0) {
            return 'cem';
        } elseif ($resto == 0) {
            return $unidades[$centena];
        } else {
            return $unidades[$centena] . ' e ' . numero_extenso($resto);
        }
    } else {
        // Lida com números maiores que 1000
        $milhar = floor($numero / 1000);
        $resto = $numero % 1000;

        if ($milhar == 1) {
            if ($resto == 0) {
                return 'mil';
            } else {
                return 'mil e ' . numero_extenso($resto);
            }
        } else {
            if ($resto == 0) {
                return numero_extenso($milhar) . ' mil';
            } else {
                return numero_extenso($milhar) . ' mil e ' . numero_extenso($resto);
            }
        }
    }
}

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
    // Debug: Remover depois de testar
    error_log("DEBUG contrato.php - reservas_ids_json recebido: " . $reservas_ids_json);
    error_log("DEBUG contrato.php - reservas_ids decodificado: " . json_encode($reservas_ids));
}

// Obter informações do usuário
$user_id = $_SESSION['user_id'];
$user_query = $conn->prepare("SELECT nome, data_nascimento, rg, cpf, rua, numero, bairro, cidade FROM usuarios WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

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
$reservas_query->execute();
$reservas_result = $reservas_query->get_result();

$reservas = [];
while ($reserva = $reservas_result->fetch_assoc()) {
    $reservas[] = $reserva;
}


// Verificar se o contrato já foi assinado para todas as reservas
$contrato_assinado = false;
if (!empty($reservas)) {
    $placeholders = str_repeat('?,', count($reservas) - 1) . '?';
    $ids = array_column($reservas, 'id');

    $assinatura_query = $conn->prepare("SELECT COUNT(*) as total FROM contratos_assinados WHERE user_id = ? AND reserva_id IN ($placeholders)");
    $types = 'i' . str_repeat('s', count($ids));
    $assinatura_query->bind_param($types, $user_id, ...$ids);
    $assinatura_query->execute();
    $assinatura_result = $assinatura_query->get_result();
    $assinatura = $assinatura_result->fetch_assoc();

    // O contrato está assinado se já tiver sido assinado para todas as reservas ativas
    $contrato_assinado = $assinatura['total'] == count($reservas);

    // Calcular o número real de diárias considerando apenas as datas sendo assinadas agora
    if (count($reservas) > 0) {
        // O número de diárias para exibir é simplesmente o número de reservas atuais
        $num_diarias_to_display = count($reservas);
        // Debug: Remover depois de testar
        error_log("DEBUG contrato.php - Número de reservas: " . count($reservas) . " - IDs: " . json_encode(array_column($reservas, 'id')));
    } else {
        $num_diarias_to_display = 0;
    }
} else {
    $num_diarias_to_display = 0;
}

// Obter informações da tabela descricao_contrato
$descricao_query = $conn->prepare("SELECT descricao, qtd, valor_unitario, valor_total FROM descricao_contrato");
$descricao_query->execute();
$descricao_result = $descricao_query->get_result();

$descricao_itens = [];
while ($item = $descricao_result->fetch_assoc()) {
    $descricao_itens[] = $item;
}

$conn->close();

// Debug: verificar se há erros antes de começar a renderizar o HTML
if (isset($num_diarias_to_display)) {
    // Tudo OK
} else {
    $num_diarias_to_display = count($reservas); // Valor padrão
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contrato de Locação - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="../assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <style>
        body {
            background-color: #f6f6f6; /* Cor de fundo conforme solicitado */
            font-family: Arial, sans-serif;
        }
        
        .contract-content {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            padding: 30px; /* Margem mais adequada para tela */
            background-color: #ffffff; /* Fundo branco para o conteúdo do contrato */
            max-width: 800px; /* Largura mais adequada para tela */
            margin: 20px auto; /* Margem superior e centralização */
            box-shadow: 0 0 10px rgba(0,0,0,0.1); /* Sombra leve */
            border-radius: 8px; /* Cantos arredondados para melhor aparência */
        }
        
        .contract-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 18px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .contract-section {
            margin-bottom: 15px;
            padding: 8px 0;
        }
        
        .contract-clause {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            border-radius: 0 4px 4px 0;
        }
        
        .contract-clause strong {
            color: #2c3e50;
        }
        
        .signature-section {
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-line {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            gap: 20px;
        }
        
        .signature-line div {
            flex: 1;
            text-align: center;
        }
        
        .signature-item {
            text-align: center;
            min-width: 40%;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            white-space: nowrap;
            padding-top: 5px;
        }
        
        .signature-name {
            margin-top: 10px;
            font-weight: normal;
        }

        .signature-section-content {
            position: relative;
        }
        
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .contract-table th, .contract-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .contract-table th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }
        
        .contract-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .contract-table tr:hover {
            background-color: #e8f4fc;
        }
        
        .contract-table th:nth-child(2),
        .contract-table td:nth-child(2) {
            text-align: center;
            width: 80px;
        }
        
        .contract-table th:nth-child(3),
        .contract-table td:nth-child(3),
        .contract-table th:nth-child(4),
        .contract-table td:nth-child(4) {
            text-align: right;
            width: 120px;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .custom-checkbox {
            margin-right: 12px;
        }
        
        @media print {
            body, html {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            .contract-content {
                box-shadow: none;
                padding: 15mm;
                border: none;
                max-width: 100%;
                width: 100%;
                margin: 0;
            }
            .ui-header {
                display: none;
            }
            .ui-footer {
                display: none;
            }
        }
        
        /* Responsive design for mobile devices */
        @media (max-width: 768px) {
            .contract-content {
                padding: 15px;
                margin: 10px;
                font-size: 12px;
            }
            
            .signature-line {
                flex-direction: column;
                gap: 30px;
            }
            
            .contract-table {
                display: block;
                overflow-x: auto;
            }
            
            .contract-title {
                font-size: 16px;
            }
            
            .contract-clause {
                padding: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .contract-content {
                padding: 10px;
                margin: 5px;
                font-size: 11px;
            }
            
            .contract-title {
                font-size: 14px;
            }
            
            .signature-label {
                font-size: 10px;
            }
        }
        
        .ui-page {
            margin: 0 auto;
        }
        
        .ui-content {
            padding: 0;
            background-color: #f6f6f6;
        }
        
        #contratoPageCliente {
            background-color: #f6f6f6;
        }
        
        .contract-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .contract-section {
            margin-bottom: 15px;
        }
        
        .contract-clause {
            margin-bottom: 10px;
        }
        
        .signature-section {
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-line {
            margin: 40px 0;
            text-align: center;
        }
        
        .signature-line {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            gap: 20px;
        }
        
        .signature-line div {
            flex: 1;
            text-align: center;
        }
        
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .contract-table th, .contract-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .contract-table th {
            background-color: #f2f2f2;
        }
        
        .contract-table th:nth-child(2),
        .contract-table td:nth-child(2) {
            text-align: center;
            width: 80px;
        }
        
        .contract-table th:nth-child(3),
        .contract-table td:nth-child(3),
        .contract-table th:nth-child(4),
        .contract-table td:nth-child(4) {
            text-align: right;
            width: 120px;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-placeholder {
            width: 150px;
            height: 100px;
            background-color: #f0f0f0;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #ccc;
        }
        
        .custom-checkbox {
            margin-right: 12px;
        }
        

        

        
        .signature-item {
            text-align: center;
            min-width: 40%;
            flex: 1;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            white-space: nowrap;
        }
        
        .signature-name {
            margin-top: 10px;
            font-weight: normal;
        }

        .signature-section-content {
            position: relative;
        }
    </style>
</head>
<body>
    </style>
</head>
<body>

<div data-role="page" id="contratoPageCliente">

    <div data-role="header" data-position="fixed">
        <h1 id="header-title-contrato">Contrato de Locação</h1>
        <a href="#" id="logout-link-contrato" class="ui-btn-right ui-btn ui-corner-all">Sair</a>
    </div>

    <div role="main" class="ui-content" style="padding: 0; margin: 0;">
        <div style="background-color: #f6f6f6; padding: 15px 0;">
            <div class="contract-content">
                
                <div class="logo-section" style="text-align: center; margin-bottom: 20px;">
                    <img src="../assets/imagens/logo_cima.jpeg" alt="Logo da Chácara" style="width: auto; height: auto; max-width: 150px; max-height: 100px; margin: 0 auto 10px; display: block;">
                    <p style="font-weight: bold; margin: 5px 0 0 0;">Chácara Recanto do Sossego</p>
                </div>
                
                <div class="contract-title">
                    Contrato de Locação Temporária
                </div>
                
                <div class="contract-section">
                    <strong>IMÓVEL:</strong> Chácara Recanto do sossego; localizada na BR 401, km12, lote 03, vicinal Igarapé Azul, Cidade de Santa Cecília, Boa Vista – RR.
                </div>
                
                <div class="contract-section">
                    <strong>LOCADOR:</strong> Kelmy Araújo Vasconcelos, Brasileiro, Casado, portador do CPF
                    00194282198, RG 1329705-8 SSP/MT, simplesmente denominado "LOCADOR"
                    <br><br>
                    <strong>E do outro lado:</strong> LOCATÁRIO(A) <?php echo $user['nome']; ?>, Nascido(a) <?php echo date('d/m/Y', strtotime($user['data_nascimento'])); ?>, Inscrito no RG <?php echo $user['rg']; ?> e <?php echo $user['cpf']; ?>, Residente e domiciliado na <?php echo $user['rua']; ?>, <?php echo $user['numero']; ?>, <?php echo $user['bairro']; ?>, <?php echo $user['cidade']; ?>.
                </div>
                
                <div class="contract-section">
                    As partes, acima qualificadas, ajustam a locação por temporada da chácara objeto do presente contrato mediante as cláusulas e condições seguintes:
                </div>
                
                <?php if(count($reservas) > 0): ?>
                <?php
                // Calcular horas totais com base na duração das reservas atuais
                $total_horas = 0;
                $data_inicio = null;
                $data_fim = null;

                if ($num_diarias_to_display == 1) {
                    // Para uma única reserva, calcular duração individual
                    $data_inicio = $reservas[0]['data'];
                    $data_fim = date('Y-m-d', strtotime($data_inicio . ' +1 day'));
                    $total_horas = 23; // Uma diária = 23 horas (09:00 do dia até 08:00 do dia seguinte)
                } else {
                    // Para múltiplas reservas, calcular duração total apenas das atuais
                    $current_reservation_dates = array_map(function($reserva) {
                        return new DateTime($reserva['data']);
                    }, $reservas);

                    // Apenas datas atuais, ordenar e usar
                    usort($current_reservation_dates, function($a, $b) {
                        return $a->getTimestamp() - $b->getTimestamp();
                    });
                    $first_date = $current_reservation_dates[0];
                    $last_date = end($current_reservation_dates);

                    $data_inicio = $first_date->format('Y-m-d');
                    $data_fim = date('Y-m-d', strtotime($last_date->format('Y-m-d') . ' +1 day'));

                    // Calcular horas totais: (24 horas * número total de diárias) - 1
                    $total_horas = (24 * $num_diarias_to_display) - 1;
                }
                ?>
                                <div class="contract-clause">
                    <strong>CLÁUSULA PRIMEIRA:</strong><br>
                    <?php
                    // Calcular as horas com base no número total de diárias (considerando datas já assinadas)
                    if ($num_diarias_to_display > 1) {
                        $horas_calculadas = (24 * $num_diarias_to_display) - 1;
                        echo "O prazo de locação de temporada será de $horas_calculadas (".numero_extenso($horas_calculadas).") horas a partir das 09:00 horas do dia ".date('d/m/Y', strtotime($data_inicio)).", terminando às 08:00 horas do dia ".date('d/m/Y', strtotime($data_fim)).", data em que locatário se obriga a restituir a chácara locada, completamente desocupado e nas condições de entrada;";
                    } else {
                        $reserva = $reservas[0]; // Usar a primeira reserva para mostrar informação individual
                        echo "O prazo de locação de temporada será de 23 (vinte e três) horas a partir das 09:00 horas do dia ".date('d/m/Y', strtotime($reserva['data'])).", terminando às 08:00 horas do dia ".date('d/m/Y', strtotime($reserva['data'] . ' +1 day')).", data em que locatário se obriga a restituir a chácara locada, completamente desocupado e nas condições de entrada;";
                    }
                    ?>
                </div>

                <div class="contract-clause">
                    <strong>CLÁUSULA SEGUNDA:</strong><br>
                    <?php
                    // Informações de pagamento e valor total
                    $total_valor = array_sum(array_column($reservas, 'valor'));

                    // Determinar o texto baseado no tipo_porcentagem
                    // Verificar se todas as reservas têm o mesmo tipo_porcentagem
                    $tipo_porcentagens = array_unique(array_column($reservas, 'tipo_porcentagem'));

                    // Assuming all reservations in the contract have the same tipo_porcentagem
                    $tipo_porcentagem_atual = !empty($tipo_porcentagens) ? $tipo_porcentagens[0] : '50';

                    if ($tipo_porcentagem_atual == '100') {
                        // 100% payment: texto mais curto e direto
                        $valor_extenso = numero_extenso(intval($total_valor));
                        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".$valor_extenso." reais), totalizando assim a reserva efetivada.";
                    } else {
                        // 50% payment or other: texto padrão com detalhes de pagamento
                        $valor_extenso = numero_extenso(intval($total_valor));
                        echo "O aluguel da temporada corresponde a ".$num_diarias_to_display." diária".($num_diarias_to_display > 1 ? 's' : '')." totalizando R$".number_format($total_valor, 2, ',', '.')." (".$valor_extenso." reais). E será pago 50% do valor de sinal para contratação da data estipulada na assinatura do contrato e os outros 50% 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada;";
                        if(count($reservas) > 1):
                        echo " Caso o cliente selecione mais de um dia também especifique aqui.";
                        endif;
                        echo " Caso o cliente escolha o pagamento ser 100% do valor só mude a porcentagem adicionando as diárias.";
                    }
                    ?>
                </div>
                
                <!-- Valor total das reservas após a segunda cláusula -->
                <div class="contract-section" style="font-weight: bold; text-align: center; margin: 15px 0; padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc;">
                    <p>Valor Total das Reservas: R$<?php echo number_format(array_sum(array_column($reservas, 'valor')), 2, ',', '.'); ?> (<?php echo numero_extenso(intval(array_sum(array_column($reservas, 'valor')))); ?> reais)</p>
                </div>

                <?php else: ?>
                <div class="contract-clause">
                    <strong>CLÁUSULA PRIMEIRA:</strong><br>
                    Nenhuma reserva encontrada.
                </div>
                
                <!-- Valor total das reservas quando não há reservas -->
                <div class="contract-section" style="font-weight: bold; text-align: center; margin: 15px 0; padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc;">
                    <p>Valor Total das Reservas: R$0,00 (zero reais)</p>
                </div>
                
                <?php endif; ?>

                <div class="contract-clause">
                    Em caso de desistência por parte do LOCATÁRIO(A) os 50% pagos como sinal não será devolvido, sendo assim esse valor considerado como Multa a favor do
                    locador; A(O) LOCADOR(A) não se responsabiliza no caso de o evento não se realizar por motivos que não possam ser acarretados ao mesmo e, portanto, não
                    devolverá o pagamento.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA TERCEIRA:</strong><br>
                    A diária estabelecida é para o número máximo de 200 (duzentas) pessoas, não podendo esse limite ser ultrapassado. O descumprimento de qualquer das cláusulas
                    do presente contrato, ensejará a sua rescisão,
                    perdendo o locatário todas as diárias pagas.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA QUARTA:</strong><br>
                    Não é permitida qualquer alteração ou modificação nas disposições do imóvel, que deverá devolver ao locador no perfeito estado em que foi encontrado. O(A)
                    LOCADOR(A) não se responsabiliza pelo extravio ou danos a quaisquer objetos deixados pelo(a) LOCATÁRIO(A) nas dependências e imediações do da chácara
                    locado, antes, durante e após o evento.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA QUINTA:</strong><br>
                    O locatário, assim que ingressar no sítio receberá uma relação de todos os objetos encontrados no mesmo, e de posse desse inventário, conferirá todos os bens
                    junto com o locador e, estando de acordo o assinará, ficando com uma cópia, responsabilizando-se totalmente por qualquer dano que venha por ventura a causar.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA SEXTA:</strong><br>
                    O locatário, desde já, faculta ao locador examinar o imóvel locado, quando este achar necessário;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA SETIMA:</strong><br>
                    Em caso de desistência da presente locação de temporada pelo locatário, perderá este todas as diárias pagas;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA OITAVA:</strong><br>
                    No último dia da temporada, o locador fará a vistoria no imóvel juntamente com o locatário, quando deverão ser devolvidas as chaves;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA NONA:</strong><br>
                    O locatário receberá uma cópia do regulamento interno contendo as normas e
                    orientações do sítio que, uma vez de pleno acordo, deverá ser assinado e faz parte integrante deste contrato, no ambiente interno da chácara há vários avisos de
                    permissões e proibições sendo obrigatório o LOCATÁRIO(A) atentar-se e fazer ser respeitados;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA:</strong><br>
                    As partes contratantes exigirão reciprocamente recibos protocolados ou termos de recebimento e entrega, pois todo e qualquer tipo de prova far-se-á por meio de,
                    apenas, de prova documental;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA PRIMEIRA:</strong><br>
                    O locatário responderá por qualquer dano ao imóvel, se não provar caso fortuito, força
                    maior ou propagação do imóvel, de conformidade com artigo 1.208 e seu parágrafo único do Código Civil. No caso de incêndio do imóvel locado, ficará o presente
                    contrato rescindido de pleno direito, independente de notificação.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA SEGUNDA:</strong><br>
                    Caso locatário não desocupe o imóvel no dia estipulado no contrato, estará obrigado a pagar as horas que excederem ao dia e horário fixado a sua saída, no valor
                    de R$150,00 a hora; todos os outros gastos que se fizerem necessários com relação à acomodação das pessoas que ocupariam o imóvel, mas foram impedidas de
                    faze-lo devido a sua permanência abusiva no mesmo, e se necessário ainda recorrer ao Poder Judiciário para que assim deixe o imóvel, arcará com o pagamento
                    das despesas e custas judiciais, assim como honorários advocatícios na base de 20 % sob o valor do débito;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA TERCEIRA:</strong><br>
                    Fica PROIBIDO o uso de aparelhagem de SOM do tipo "paredão", o uso de som fica autorizado desde que respeite o descanso dos vizinhos e de quem se sentir
                    incomodado. O volume permitido do som diurno (das 07:00 as 22:00 horas) não poderá ultrapassar os 50 decibéis, e em horário noturno (das 22:00 das 07:00 horas)
                    não poderá ultrapassar os 30 decibéis.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA QUARTA:</strong><br>
                    A chácara não possui salva-vidas, ficando autorizado o uso do serviço por conta e responsabilidade do LOCATÁRIO(A) tal como também sua contratação;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA QUINTA:</strong><br>
                    O LOCADOR não se responsabiliza por eventuais "acidentes" que venha acontecer no perímetro da chácara tais como qualquer incidente com animais silvestres e
                    peçonhentos;
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA SEXTA:</strong><br>
                    É de responsabilidade exclusiva do(a) LOCATÁRIO(A) a condução do comportamento de seus convidados, bem como caberá a(o) mesmo(a) a exigência de que
                    seja retirado o convidado que infringir regras de conduta.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA SETIMA:</strong><br>
                    O(A) LOCATÁRIO(A) será responsável por quaisquer multas às quais tenha dado causa, por desobediência às convenções ou às normas de civilidade e vizinhança
                    vigentes no local da chácara.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA OITAVA:</strong><br>
                    Fica estabelecido ainda que o(a) LOCATÁRIO(A) seguirá rigorosamente as Leis Federais e Municipais, como intensidade do som (lei do silêncio), permanência de
                    menores, uso de bebidas alcoólicas, cumprindo ainda a política da boa vizinhança, não incomodando terceiros, na proximidade da locação.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA DÉCIMA NONA:</strong><br>
                    Os veículos particulares, nos dias de eventos, poderão estacionar nos espaços internos e externos da chácara locado, porém, o(a) LOCADOR(A) exime-se de
                    qualquer responsabilidade em relação a danos e/ou furto de veículos estacionados tanto nos espaços internos, quanto externos (áreas públicas) do da chácara
                    alugado.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA VIGÉSIMA:</strong><br>
                    Fica absolutamente proibido o consumo de alimentos e uso de objetos de vidro (copos, garrafas etc.) dentro da piscina.
                </div>
                
                <div class="contract-clause">
                    <strong>CLÁUSULA VIGÉSIMA PRIMEIRA:</strong><br>
                    Fica acordado que o(a) LOCATÁRIO(A) se responsabilizará pelos móveis e equipamentos se danificado, efetuando a entrega nas mesmas condições em que os
                    encontrou, conforme laudo de vistoria, salvo desgastes naturais, sob pena de indenização dos valores unitários de cada, em caso de prejuízos ao(à) LOCADOR(A).
                    Constitui-se objeto deste contrato a locação do espaço semicoberto constituído das seguintes estruturas, itens e seus respectivos valores em caso de indenização:
                </div>
                
                <?php if(count($descricao_itens) > 0): ?>
                <table class="contract-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qtd</th>
                            <th>Valor Unitário</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($descricao_itens as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                            <td><?php echo $item['qtd']; ?></td>
                            <td>R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($item['valor_total'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <?php if(!$contrato_assinado): ?>
                <div class="signature-section">
                    <?php
                    $meses = [
                        '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril',
                        '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto',
                        '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
                    ];
                    $mes_atual = date('m');
                    $nome_mes = $meses[$mes_atual] ?? 'mês desconhecido';
                    ?>
                    Boa Vista – RR, <?php echo date('d'); ?> de <?php echo $nome_mes; ?> de <?php echo date('Y'); ?>
                    <!-- Checkbox e botão de assinatura -->
                    <div style="margin-bottom: 20px;">
                        <input class="custom-checkbox" type="checkbox" id="aceiteContrato" required>
                        <label for="aceiteContrato">
                            Li e concordo com todos os termos do contrato
                        </label>
                    </div>

                    <div class="d-grid gap-2" style="margin-bottom: 20px;">
                        <button class="btn btn-secondary btn-lg" type="button" id="btnAssinarContrato" disabled>
                            <i class="fas fa-signature"></i> Assinar Contrato
                        </button>
                    </div>

                    <!-- Mensagem de sucesso após assinatura -->
                    <div id="mensagemAssinatura" class="alert alert-success mt-3" style="display: none; margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> Contrato assinado com sucesso!
                    </div>

                    <div class="signature-line">
                        <div class="signature-item">
                            <div class="signature-name"><?php echo $user['nome']; ?></div>
                            <div class="signature-label">_________________________________<br>Locatário(a)</div>
                        </div>
                        <div class="signature-item">
                            <img src="../assets/imagens/assinatura.png" alt="Assinatura do Locador" style="max-width: 150px; max-height: 60px; display: block; margin: 0 auto 10px auto;">
                            <div class="signature-name">Kelmy Araújo Vasconcelos</div>
                            <div class="signature-label">_________________________________<br>Locador</div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="signature-section">
                    <?php
                    $meses = [
                        '01' => 'janeiro', '02' => 'fevereiro', '03' => 'março', '04' => 'abril',
                        '05' => 'maio', '06' => 'junho', '07' => 'julho', '08' => 'agosto',
                        '09' => 'setembro', '10' => 'outubro', '11' => 'novembro', '12' => 'dezembro'
                    ];
                    $mes_atual = date('m');
                    $nome_mes = $meses[$mes_atual] ?? 'mês desconhecido';
                    ?>
                    Boa Vista – RR, <?php echo date('d'); ?> de <?php echo $nome_mes; ?> de <?php echo date('Y'); ?>
                    <div class="signature-line">
                        <div class="signature-item">
                            <?php if($contrato_assinado): ?>
                            <div style="margin-top: 10px;">
                                <span class="btn btn-success btn-sm"><i class="fas fa-signature"></i> Documento assinado digitalmente</span>
                            </div>
                            <?php endif; ?>
                            <div class="signature-name"><?php echo $user['nome']; ?></div>
                            <div class="signature-label">_________________________________<br>Locatário(a)</div>
                        </div>
                        <div class="signature-item">
                            <img src="../assets/imagens/assinatura.png" alt="Assinatura do Locador" style="max-width: 150px; max-height: 60px; display: block; margin: 0 auto 10px auto;">
                            <div class="signature-name">Kelmy Araújo Vasconcelos</div>
                            <div class="signature-label">_________________________________<br>Locador</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!$contrato_assinado): ?>
                <!-- Mensagem de contrato já assinado -->
                <?php if ($reserva_id_fornecido): ?>
                <div class="alert alert-info">
                    <i class="fas fa-check-circle"></i> <strong>Contrato já assinado!</strong> Você já assinou o contrato para esta reserva.
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-check-circle"></i> <strong>Contrato já assinado!</strong> Você já assinou o contrato para <?php echo count($reservas); ?> reserva<?php echo count($reservas) > 1 ? 's' : ''; ?>.
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-ajax="false" data-icon="home">Inicio</a></li>
                <li><a href="reserva.php" data-ajax="false" data-icon="grid">Reserve</a></li>
                <li><a href="suas_reservas.php" data-ajax="false" data-icon="calendar">Reservas</a></li>
                <li><a href="perfil.php" data-ajax="false" data-icon="user">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/jquery.mobile-1.4.5.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Função para carregar o nome do usuário e atualizar o cabeçalho
function loadUserName() {
    $.ajax({
        url: '../php/get_user_info.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.user_name) {
                document.getElementById('header-title-contrato').textContent = 'Olá ' + response.user_name;
            } else {
                document.getElementById('header-title-contrato').textContent = 'Contrato de Locação';
            }
        },
        error: function() {
            document.getElementById('header-title-contrato').textContent = 'Contrato de Locação';
        }
    });
}

<?php if(!$contrato_assinado): ?>
$(document).on('pageinit', '#contratoPageCliente', function() {
    // Carregar o nome do usuário para atualizar o cabeçalho
    loadUserName();
    
    // Habilitar/desabilitar botão com base no checkbox
    $('#aceiteContrato').on('change', function() {
        $('#btnAssinarContrato').prop('disabled', !this.checked);
    });
    
    // Ação do botão de assinatura
    $('#btnAssinarContrato').on('click', function() {
        if ($('#aceiteContrato').is(':checked')) {
            // Desabilitar o botão durante o processamento
            $('#btnAssinarContrato').prop('disabled', true).text('Processando...').addClass('disabled');
            
            // Registrar a assinatura no banco de dados
            var requestData = {};
            <?php if ($reservas_ids): ?>
            requestData.reservas_ids = <?php echo json_encode($reservas_ids); ?>;
            <?php elseif (isset($_GET['reserva_id']) && !empty($_GET['reserva_id'])): ?>
            requestData.reserva_id = '<?php echo $_GET['reserva_id']; ?>';
            <?php else: ?>
            requestData.reserva_id = '';
            <?php endif; ?>
            
            console.log('Enviando dados:', requestData);
            
            $.ajax({
                url: '../php/registrar_assinatura_contrato.php',
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Mostrar mensagem de sucesso
                        $('#mensagemAssinatura').show();
                        
                        // Esconder o formulário de assinatura
                        $('#aceiteContrato').parent().hide();
                        $('#btnAssinarContrato').hide();
                        
                        // Redirecionar após um breve delay
                        setTimeout(function() {
                            window.location.href = 'perfil.php';
                        }, 2000);
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao registrar assinatura: ' + response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        // Reabilitar o botão
                        $('#btnAssinarContrato').prop('disabled', false).text('Assinar Contrato').removeClass('disabled');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', xhr.responseText);
                    console.error('Status:', status);
                    console.error('Error:', error);
                    
                    Swal.fire({
                        title: 'Erro de Comunicação',
                        text: 'Erro na comunicação com o servidor: ' + error + '. Tente novamente.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    // Reabilitar o botão
                    $('#btnAssinarContrato').prop('disabled', false).text('Assinar Contrato').removeClass('disabled');
                }
            });
        } else {
            Swal.fire({
                title: 'Atenção!',
                text: 'Por favor, leia e aceite os termos do contrato antes de assinar.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        }
    });
});
<?php endif; ?>

// Adicionar manipulador de logout
$(document).on('pageinit', '#contratoPageCliente', function() {
    $('#logout-link-contrato').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../php/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Limpar possíveis dados da sessão no frontend e redirecionar de forma limpa
                    window.location.replace('login.php');
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao fazer logout. Por favor, tente novamente.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                // Mesmo em caso de erro, redirecionar para login
                window.location.href = 'login.php';
            }
        });
    });
});
</script>

</body>
</html>