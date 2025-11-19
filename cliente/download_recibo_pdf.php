<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Retornar erro se não estiver autenticado
    die('Usuário não autenticado');
}

if (!isset($_GET['reserva_id'])) {
    die('ID da reserva não fornecido');
}

$reserva_id = $_GET['reserva_id'];

// Conectar ao banco de dados
require_once '../php/db_connection.php';

try {
    // Obter dados da reserva com informações do cliente
    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.data,
            r.valor,
            r.status,
            r.payment_confirmed_at,
            u.nome AS cliente_nome,
            u.cpf AS cliente_documento
        FROM reservas r
        JOIN usuarios u ON r.user_id = u.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reserva_id, $_SESSION['user_id']]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        die('Reserva não encontrada ou não pertence ao usuário');
    }

    // Verificar se a reserva está confirmada e tem data de pagamento
    if ($reserva['status'] !== 'confirmado' || !$reserva['payment_confirmed_at']) {
        die('Apenas reservas confirmadas com pagamento realizado podem emitir recibo');
    }

    // Processar dados da reserva
    $data_reserva = $reserva['data'];
    $valor_pago = 'R$ ' . number_format(floatval($reserva['valor']), 2, ',', '.');
    $cliente_nome = $reserva['cliente_nome'];
    $cliente_documento = $reserva['cliente_documento'];
    $valor_pago_extenso = numero_extenso(floatval($reserva['valor'])); // Converter valor para extenso

    // Converter a data do formato Y-m-d para d/m/Y
    $dataFormatada = date('d/m/Y', strtotime($data_reserva));
    $dataPartes = explode('/', $dataFormatada);
    if (count($dataPartes) == 3) {
        $diaReserva = $dataPartes[0];
        $mesReserva = $dataPartes[1];
        $anoReserva = $dataPartes[2];

        $dataInicio = $diaReserva . ' de ' . getMesExtenso($mesReserva) . ' de ' . $anoReserva;

        // Calcular data final (dia seguinte)
        $dataFinal = new DateTime("{$anoReserva}-{$mesReserva}-{$diaReserva}");
        $dataFinal->add(new DateInterval('P1D')); // Adiciona 1 dia
        $dataFim = $dataFinal->format('d') . ' de ' . getMesExtenso($dataFinal->format('m')) . ' de ' . $dataFinal->format('Y');
    } else {
        die('Formato de data inválido');
    }

    // Obter data de emissão
    $dataEmissao = date('d') . ' de ' . getMesExtenso(date('m')) . ' de ' . date('Y');

    // Determinar tipo de documento
    $documentoTipo = strlen($cliente_documento) > 14 ? 'CNPJ' : 'CPF';

    // Gerar o conteúdo HTML do recibo para converter para PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Recibo de Aluguel por Temporada - Chácara Recanto do Sossego</title>
        <style>
            body {
                background-color: #f6f6f6;
                font-family: Arial, sans-serif;
                font-size: 10px;
                margin: 0;
                padding: 0;
            }

            .receipt-content {
                font-family: Arial, sans-serif;
                font-size: 10px;
                line-height: 1.4;
                padding: 15px;
                background-color: #ffffff;
                max-width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }

            .receipt-title {
                text-align: center;
                font-weight: bold;
                margin-bottom: 15px;
                font-size: 12px;
                color: #2c3e50;
                border-bottom: 1px solid #3498db;
                padding-bottom: 8px;
            }

            .receipt-section {
                margin-bottom: 10px;
                padding: 5px 0;
            }

            .receipt-data {
                margin: 8px 0;
                padding: 6px;
                background-color: #f8f9fa;
                border-left: 3px solid #3498db;
                border-radius: 0 3px 3px 0;
            }

            .logo-section {
                text-align: center;
                margin-bottom: 20px;
            }

            .company-info {
                text-align: center;
                margin: 10px 0;
            }

            .signature-section {
                margin-top: 30px;
                text-align: center;
            }

            .signature-line {
                display: flex;
                justify-content: space-between;
                margin-top: 40px;
                gap: 10px;
            }

            .signature-line div {
                flex: 1;
                text-align: center;
            }

            .signature-item {
                text-align: center;
                min-width: 40%;
                flex: 1;
            }

            .signature-label {
                font-weight: bold;
                margin-bottom: 3px;
                white-space: nowrap;
                padding-top: 20px;
                border-top: 1px solid #ccc;
                font-size: 10px;
            }

            .signature-name {
                margin-top: 5px;
                font-weight: normal;
                font-size: 10px;
            }

            .receipt-details {
                margin: 15px 0;
                padding: 10px;
                background-color: #f0f8ff;
                border: 1px solid #b0c4de;
                border-radius: 4px;
            }

            .receipt-field {
                margin: 5px 0;
            }

            .receipt-field-label {
                font-weight: bold;
                display: inline-block;
                width: 120px;
            }

            .receipt-field-value {
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div style="background-color: #f6f6f6; padding: 10px 0;">
            <div class="receipt-content" style="margin-top: 10px;">

                <div class="logo-section" style="text-align: center; margin-bottom: 15px;">
                    <img src="../assets/imagens/logo_cima.jpeg" alt="Logo da Chácara" style="width: auto; height: auto; max-width: 120px; max-height: 80px; margin: 0 auto 8px; display: block;">
                    <p style="font-weight: bold; margin: 4px 0 0 0; font-size: 11px;">Chácara Recanto do Sossego</p>
                    <p style="margin: 2px 0 0 0; font-size: 9px;">BR 401, km12, lote 03, vicinal Igarapé Azul</p>
                    <p style="margin: 2px 0 0 0; font-size: 9px;">Cidade de Santa Cecília, Boa Vista – RR (95) 99124-4142</p>
                </div>

                <div class="receipt-title">
                    RECIBO DE ALUGUEL POR TEMPORADA
                </div>

                <div class="receipt-section">
                    <div style="text-align: right; font-size: 10px;">
                        Boa Vista RR, <?php echo $dataEmissao; ?>
                    </div>

                    <div class="receipt-data">
                        <div class="receipt-details">
                            <div class="receipt-field">
                                <span class="receipt-field-label">Recebemos de:</span>
                                <span class="receipt-field-value"><?php echo $cliente_nome; ?></span>
                            </div>

                            <div class="receipt-field">
                                <span class="receipt-field-label"><?php echo $documentoTipo; ?>:</span>
                                <span class="receipt-field-value"><?php echo $cliente_documento; ?></span>
                            </div>

                            <div class="receipt-field">
                                <span class="receipt-field-label">Valor:</span>
                                <span class="receipt-field-value"><?php echo $valor_pago; ?></span>
                            </div>

                            <div class="receipt-field">
                                <span class="receipt-field-label">Valor por extenso:</span>
                                <span class="receipt-field-value"><?php echo $valor_pago_extenso; ?></span>
                            </div>
                        </div>

                        <div class="receipt-description">
                            <p>Referente a locação da Chácara Recanto do Sossego de 09:00 horas da manhã do dia <?php echo $dataInicio; ?> até as 08:00 horas da manhã do dia <?php echo $dataFim; ?> sendo 1 (uma) diária.</p>

                            <p>Declaro ainda que o valor estipulado acima foi pago na data presente em parcela única em moeda corrente neste país.</p>

                            <p>O presente recibo nada declara quanto ao pagamento de outras despesas relacionadas ao imóvel.</p>
                        </div>
                    </div>

                    <div class="signature-section">
                        <div class="signature-line">
                            <div class="signature-item">
                                <div class="signature-label">_________________________________<br>Assinatura do Locatário</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    $html_content = ob_get_contents();
    ob_end_clean();

    // Verificar se a biblioteca TCPDF está instalada
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';

        // Verificar se TCPDF está disponível
        if (class_exists('TCPDF')) {
            // Criar instância do TCPDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Definir informações do documento
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('Recibo de Aluguel por Temporada - Chácara Recanto do Sossego');
            $pdf->SetSubject('Recibo de Aluguel');
            $pdf->SetKeywords('Recibo, PDF, Chácara');

            // Definir fonte padrão do TCPDF (fonte core que não requer arquivos externos)
            $pdf->SetFont('courier', '', 10);

            // Remover headers e footers
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Definir margens
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // Definir informações do autor
            $pdf->SetAuthor('Chácara Recanto do Sossego');

            // Adicionar uma página
            $pdf->AddPage();

            // Adicionar o logo manualmente usando o método Image do TCPDF antes de escrever o HTML
            $logo_path = '../assets/imagens/logo_cima.jpeg';
            if (file_exists($logo_path)) {
                // Adiciona a imagem do logo - ajuste as coordenadas e dimensões conforme necessário
                $pdf->Image($logo_path, 80, 30, 40, 20, 'JPEG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }

            // Escrever o conteúdo HTML
            $pdf->writeHTML($html_content, true, false, true, false, '');

            // Enviar o PDF para visualização no navegador (em vez de download)
            $pdf->Output('recibo_chacara_' . $reserva_id . '.pdf', 'I');
        } else {
            // Se o TCPDF não estiver instalado, exibir o HTML e mostrar instruções para salvar como PDF
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="recibo_chacara_' . $reserva_id . '.html"');
            echo $html_content;
            echo '<script>
                alert("Para salvar como PDF, use a função \'Salvar como PDF\' do seu navegador (Ctrl+P ou Cmd+P).");
            </script>';
        }
    } else {
        // Se o autoload do Composer não existir, exibir o HTML e mostrar instruções para salvar como PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="recibo_chacara_' . $reserva_id . '.html"');
        echo $html_content;
        echo '<script>
            alert("Para salvar como PDF, use a função \'Salvar como PDF\' do seu navegador (Ctrl+P ou Cmd+P).");
        </script>';
    }

} catch (Exception $e) {
    die('Erro ao gerar recibo: ' . $e->getMessage());
}

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

// Função para obter o mês por extenso
function getMesExtenso($mes) {
    $meses = [
        '01' => 'janeiro',
        '02' => 'fevereiro',
        '03' => 'março',
        '04' => 'abril',
        '05' => 'maio',
        '06' => 'junho',
        '07' => 'julho',
        '08' => 'agosto',
        '09' => 'setembro',
        '10' => 'outubro',
        '11' => 'novembro',
        '12' => 'dezembro'
    ];

    return isset($meses[$mes]) ? $meses[$mes] : $mes;
}
?>