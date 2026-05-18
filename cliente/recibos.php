<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // Retornar erro se não estiver autenticado
    die('Usuário não autenticado');
}

if (!isset($_GET['reservas_ids'])) {
    die('IDs das reservas não fornecidos');
}

$reservas_ids_input = $_GET['reservas_ids'];

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

if (!is_array($reservas_ids) || empty($reservas_ids)) {
    die('Array de IDs de reservas inválido ou vazio: ' . $reservas_ids_input);
}

// Conectar ao banco de dados
require_once '../php/db_connection.php';

try {
    // Obter dados das reservas com informações do cliente
    $placeholders = str_repeat('?,', count($reservas_ids) - 1) . '?';
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
        WHERE r.id IN ($placeholders) AND r.user_id = ?
        ORDER BY r.data
    ");
    $params = array_merge($reservas_ids, [$_SESSION['user_id']]);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$reservas) {
        die('Nenhuma reserva encontrada ou nenhuma das reservas solicitadas pertence ao usuário');
    }

    // Validar que todas as reservas estão confirmadas e têm pagamento realizado
    foreach ($reservas as $reserva) {
        if ($reserva['status'] !== 'confirmado') {
            die('A reserva ' . $reserva['id'] . ' não está confirmada (status: ' . $reserva['status'] . '). Apenas reservas confirmadas podem emitir recibo.');
        }
        if (!$reserva['payment_confirmed_at']) {
            die('A reserva ' . $reserva['id'] . ' não tem pagamento confirmado. Apenas reservas com pagamento realizado podem emitir recibo.');
        }
    }

    // Gerar o conteúdo HTML do recibo para converter para PDF

    // Função para verificar o percentual de pagamento
    function getPaymentPercentage($pdo, $reservas_ids) {
        try {
            // Verificar se os IDs das reservas correspondem aos IDs nos links de pagamento
            $placeholders = str_repeat('?,', count($reservas_ids) - 1) . '?';

            // Consulta para verificar se os IDs das reservas estão nos links de pagamento
            // A coluna reservation_ids pode conter múltiplos IDs separados por vírgula, então usamos FIND_IN_SET
            $sql = "SELECT r.id, r.payment_percentage FROM reservas r ";
            $sql .= "WHERE r.id IN ($placeholders) ";
            $sql .= "AND EXISTS (SELECT 1 FROM mp_payment_links mpl WHERE FIND_IN_SET(r.id, mpl.reservation_ids) > 0) ";
            $sql .= "LIMIT 1"; // Pegamos apenas o primeiro registro com percentual

            $stmt = $pdo->prepare($sql);
            $stmt->execute($reservas_ids);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar se há resultado e retornar o percentual de pagamento
            if ($result && $result['payment_percentage'] !== null) {
                return floatval($result['payment_percentage']);
            }

            return null; // Retorna null se não encontrar correspondência
        } catch (Exception $e) {
            error_log("Erro ao verificar percentual de pagamento: " . $e->getMessage());
            return null;
        }
    }

    // Obter o percentual de pagamento
    $payment_percentage = getPaymentPercentage($pdo, $reservas_ids);

    // Debug: registrar o percentual de pagamento encontrado
    error_log("Percentual de pagamento encontrado: " . ($payment_percentage !== null ? $payment_percentage : 'null'));

    // Debug: verificar se a imagem existe
    $debug_img_path = $_SERVER['DOCUMENT_ROOT'] . '/chacararecantodosossegorr.com.br/repo_limpo/assets/imagens/logo.jpeg';
    if (!file_exists($debug_img_path)) {
        error_log("Imagem de marca d'água não encontrada: " . $debug_img_path);
        // Tentar caminho relativo
        $debug_img_path = __DIR__ . '/../assets/imagens/logo.jpeg';
        if (!file_exists($debug_img_path)) {
            error_log("Imagem de marca d'água também não encontrada no caminho relativo: " . $debug_img_path);
        }
    } else {
        error_log("Imagem de marca d'água encontrada: " . $debug_img_path);
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Recibo de Aluguel por Temporada - Chácara Recanto do Sossego</title>
        <style>
            body {
                background-color: #ffffff;
                font-family: Arial, sans-serif;
                font-size: 9px;
                margin: 0;
                padding: 0;
            }

            .receipt-content {
                font-family: Arial, sans-serif;
                font-size: 9px;
                line-height: 1.3;
                padding: 10px 10px 10px 10px;
                background-color: #ffffff;
                max-width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }

            .page-top-padding {
                height: 10mm;
                display: block;
            }

            .receipt-title {
                text-align: center;
                font-weight: bold;
                margin-top: 5px;
                margin-bottom: 8px;
                font-size: 11px;
                color: #2c3e50;
                border-bottom: 1px solid #3498db;
                padding-bottom: 5px;
            }

            .receipt-section {
                margin-bottom: 8px;
                padding: 4px 0;
            }

            .receipt-data {
                margin: 6px 0;
                padding: 5px;
                background-color: #f8f9fa;
                border-left: 3px solid #3498db;
                border-radius: 0 3px 3px 0;
            }

            .logo-section {
                text-align: center;
                margin-bottom: 6px;
            }

            .logo-section img {
                max-width: 100px;
                height: auto;
                display: block;
                margin: 0 auto 10px auto;
            }

            .company-info {
                text-align: center;
                margin: 8px 0;
            }

            .signature-section {
                margin-top: 15px;
                text-align: center;
            }

            .signature-line {
                display: block;
                text-align: center;
                margin-top: 10px;
            }

            .signature-line div {
                text-align: center;
            }

            .signature-item {
                text-align: center;
                display: block;
            }

            .signature-label {
                font-weight: bold;
                margin-bottom: 2px;
                white-space: nowrap;
                padding-top: 15px;
                border-top: 1px solid #ccc;
                font-size: 9px;
            }

            .signature-name {
                margin-top: 3px;
                font-weight: normal;
                font-size: 9px;
            }

            .receipt-details {
                margin: 10px 0;
                padding: 8px;
                background-color: #f0f8ff;
                border: 1px solid #b0c4de;
                border-radius: 3px;
            }

            .receipt-field {
                margin: 4px 0;
            }

            .receipt-field-label {
                font-weight: bold;
                display: inline-block;
                width: 100px;
            }

            .receipt-field-value {
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div style="background-color: #ffffff; padding: 5px 0;">
            <div class="receipt-content" style="margin-top: 2px;">
                <div class="page-top-padding"></div>

                <table style="width: 100%; border: none; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: center; padding: 0;">
                            <p style="font-weight: bold; margin: 0; font-size: 25px; line-height: 1.1; text-align: center !important; display: block;">Chácara Recanto do Sossego</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 0;">
                            <p style="margin: 1px 0 0 0; font-size: 8px; line-height: 1.1; text-align: center !important; display: block;">BR 401, km12, lote 03, vicinal Igarapé Azul</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 0;">
                            <p style="margin: 1px 0 0 0; font-size: 8px; line-height: 1.1; text-align: center !important; display: block;">Cidade de Santa Cecília, Boa Vista – RR (95) 99124-4142</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 8px 0 5px 0;">
                            <div class="receipt-title" style="text-align: center !important; margin: 0; padding: 0; line-height: 1.1; font-size: 15px; display: block;">
                                RECIBO DE ALUGUEL POR TEMPORADA
                            </div>
                        </td>
                    </tr>
                </table>

                <?php
                // Processar todas as reservas para gerar um recibo único
                $cliente_nome = $reservas[0]['cliente_nome'];
                $cliente_documento = $reservas[0]['cliente_documento'];

                // Determinar tipo de documento
                $documentoTipo = strlen($cliente_documento) > 14 ? 'CNPJ' : 'CPF';

                // Obter data de emissão
                $dataEmissao = date('d') . ' de ' . getMesExtenso(date('m')) . ' de ' . date('Y');

                // Ordenar as reservas por data para encontrar a primeira e última data
                $datas_reservas = [];
                $valores_totais = 0;

                foreach ($reservas as $reserva) {
                    // Converter a data do formato Y-m-d para d/m/Y
                    $dataFormatada = date('d/m/Y', strtotime($reserva['data']));
                    $dataPartes = explode('/', $dataFormatada);
                    if (count($dataPartes) == 3) {
                        $diaReserva = $dataPartes[0];
                        $mesReserva = $dataPartes[1];
                        $anoReserva = $dataPartes[2];

                        $data_completa = $anoReserva . '-' . $mesReserva . '-' . $diaReserva;
                        $datas_reservas[] = $data_completa;

                        $valores_totais += floatval($reserva['valor']);
                    } else {
                        die('Formato de data inválido na reserva ID ' . $reserva['id']);
                    }
                }

                // Ordenar as datas para encontrar a primeira e a última
                sort($datas_reservas);

                // Converter datas para formato por extenso
                $primeira_data = new DateTime($datas_reservas[0]);
                $ultima_data = new DateTime(end($datas_reservas));

                // Adicionar um dia à última data para calcular a hora de devolução
                $ultima_data->add(new DateInterval('P1D'));

                $dataInicio = $primeira_data->format('d') . ' de ' . getMesExtenso($primeira_data->format('m')) . ' de ' . $primeira_data->format('Y');
                $dataFim = $ultima_data->format('d') . ' de ' . getMesExtenso($ultima_data->format('m')) . ' de ' . $ultima_data->format('Y');

                // Calcular o número total de diárias
                $total_diarias = count($datas_reservas);

                // Calcular o valor pago com base no percentual de pagamento
                if ($payment_percentage !== null) {
                    $valor_pago_calculado = $valores_totais * ($payment_percentage / 100);
                    $valor_pago = 'R$ ' . number_format($valor_pago_calculado, 2, ',', '.');
                    $forma_pagamento = $payment_percentage . '% (' . getPorcentagemExtenso($payment_percentage) . ')';
                } else {
                    $valor_pago = 'R$ ' . number_format($valores_totais, 2, ',', '.');
                    $forma_pagamento = null;
                }

                $valor_pago_extenso = numero_extenso($valores_totais);
                ?>

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

                            <?php if ($forma_pagamento !== null): ?>
                                <div class="receipt-field">
                                    <span class="receipt-field-label">Forma de Pagamento:</span>
                                    <span class="receipt-field-value"><?php echo $forma_pagamento; ?><?php echo ($payment_percentage == 50) ? ' de entrada.' : ''; ?></span>
                                </div>

                                <div class="receipt-field">
                                    <span class="receipt-field-label">Valor pago:</span>
                                    <span class="receipt-field-value"><?php echo $valor_pago; ?></span>
                                </div>
                            <?php else: ?>
                                <div class="receipt-field">
                                    <span class="receipt-field-label">Valor:</span>
                                    <span class="receipt-field-value"><?php echo $valor_pago; ?></span>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="receipt-description" style="margin-top: 8px;">
                            <p>Referente a locação da Chácara Recanto do Sossego de 09:00 horas da manhã do dia <?php echo $dataInicio; ?> até as 08:00 horas da manhã do dia <?php echo $dataFim; ?> sendo <?php echo $total_diarias; ?> diária(s).</p>

                            <?php if ($forma_pagamento !== null): ?>
                                <?php if ($payment_percentage == 50): ?>
                                    <p>Declaro ainda que o valor estipulado acima foi pago na data presente com a forma de pagamento de <?php echo $forma_pagamento; ?> em moeda corrente
                                    neste país e os outros 50%, 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada.</p>
                                <?php elseif ($payment_percentage == 100): ?>
                                    <p>Declaro ainda que o valor estipulado acima foi pago na data presente com a forma de pagamento de <?php echo $forma_pagamento; ?> em moeda corrente
                                    neste país, totalizando assim a reserva efetivada.</p>
                                <?php else: ?>
                                    <p>Declaro ainda que o valor estipulado acima foi pago na data presente em parcela única em moeda corrente neste país.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Declaro ainda que o valor estipulado acima foi pago na data presente em parcela única em moeda corrente neste país.</p>
                            <?php endif; ?>

                            <p>O presente recibo nada declara quanto ao pagamento de outras despesas relacionadas ao imóvel.</p>
                        </div>
                    </div>

                    <div class="signature-section">
                        <table style="width: 100%; text-align: center !important; margin: 10px auto 0; border: none; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: center !important; padding: 0; margin: 0;">
                                    <div style="text-align: center !important; display: block; margin-top: 15px;">
                                        <?php
                                        // Caminho para a imagem de assinatura
                                        $assinatura_path = '../assets/imagens/assinatura.png';
                                        if (file_exists($assinatura_path)) {
                                            // Converter a imagem para base64 para garantir que ela seja incorporada ao PDF
                                            $imageData = base64_encode(file_get_contents($assinatura_path));
                                            echo '<img src="data:image/png;base64,' . $imageData . '" width="200" style="height: auto; margin-bottom: 5px;" alt="Assinatura">';
                                        }
                                        ?>
                                        <div class="signature-label" style="text-align: center !important; display: block; margin-top: 5px;">
                                            <span style="display: inline-block; border-top: 1px solid #000; min-width: 200px; max-width: 50%; height: 1px;"></span><br>
                                            Assinatura do Locatário
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
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
            // Criar instância do TCPDF padrão
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Definir informações do documento
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('Recibo de Aluguel por Temporada - Chácara Recanto do Sossego');
            $pdf->SetSubject('Recibo de Aluguel');
            $pdf->SetKeywords('Recibo, PDF, Chácara');

            // Definir fonte padrão usando a configuração padrão do TCPDF
            $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 10);

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

            // Adicionar marca d'água após adicionar a página, mas antes do conteúdo
            // Tenta carregar o arquivo de imagem e converter para base64 para garantir que funcione
            $watermark_path = $_SERVER['DOCUMENT_ROOT'] . '/chacararecantodosossegorr.com.br/repo_limpo/assets/imagens/logo.jpeg';

            // Se o caminho acima não funcionar, tentar outros formatos
            if (!file_exists($watermark_path)) {
                $watermark_path = dirname(__FILE__) . '/../assets/imagens/logo.jpeg';
            }

            if (!file_exists($watermark_path)) {
                // Último fallback - tentar logo_cima.jpeg
                $watermark_path = $_SERVER['DOCUMENT_ROOT'] . '/chacararecantodosossegorr.com.br/repo_limpo/assets/imagens/logo_cima.jpeg';
            }

            if (!file_exists($watermark_path)) {
                $watermark_path = dirname(__FILE__) . '/../assets/imagens/logo_cima.jpeg';
            }

            if (file_exists($watermark_path)) {
                // Converter imagem para base64 e adicionar como marca d'água
                $imageData = file_get_contents($watermark_path);
                if ($imageData !== false) {
                    $pdf->SetAlpha(0.15);
                    $img_width = 150; // Tamanho fixo para garantir visibilidade
                    $img_height = 0; // O TCPDF calculará automaticamente a altura proporcional
                    $page_width = $pdf->getPageWidth();
                    $page_height = $pdf->getPageHeight();

                    // Calcular posição central exata
                    $x_pos = ($page_width - $img_width) / 2;

                    // Para centralizar verticalmente, vamos estimar a altura da imagem após o redimensionamento
                    // Assumindo uma proporção comum para logos (mais largos que altos)
                    $estimated_img_height = $img_width * 0.5; // Ajustar conforme necessário para o formato do logo
                    $y_pos = ($page_height - $estimated_img_height) / 2 - 80; // Subir a imagem 80 pixels

                    // Adiciona a imagem como marca d'água na posição centralizada
                    $pdf->Image('@' . $imageData, $x_pos, $y_pos, $img_width, $img_height, 'JPEG', '', '', false, 300, '', false, false, 0, false, false, false);
                    $pdf->SetAlpha(1);
                }
            }

            // Escrever o conteúdo HTML
            $pdf->writeHTML($html_content, true, false, true, false, '');

            // Enviar o PDF para visualização no navegador (em vez de download)
            $pdf->Output('recibos_chacara_' . time() . '.pdf', 'I');
        } else {
            // Se o TCPDF não estiver instalado, exibir o HTML e mostrar instruções para salvar como PDF
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="recibos_chacara_' . time() . '.html"');
            echo $html_content;
            echo '<script>
                alert("Para salvar como PDF, use a função \'Salvar como PDF\' do seu navegador (Ctrl+P ou Cmd+P).");
            </script>';
        }
    } else {
        // Se o autoload do Composer não existir, exibir o HTML e mostrar instruções para salvar como PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="recibos_chacara_' . time() . '.html"');
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

// Função para converter porcentagem em extenso
function getPorcentagemExtenso($porcentagem) {
    $porcentagens = [
        1 => 'um por cento',
        2 => 'dois por cento',
        3 => 'três por cento',
        4 => 'quatro por cento',
        5 => 'cinco por cento',
        10 => 'dez por cento',
        15 => 'quinze por cento',
        20 => 'vinte por cento',
        25 => 'vinte e cinco por cento',
        30 => 'trinta por cento',
        40 => 'quarenta por cento',
        50 => 'cinquenta por cento',
        60 => 'sessenta por cento',
        70 => 'setenta por cento',
        75 => 'setenta e cinco por cento',
        80 => 'oitenta por cento',
        90 => 'noventa por cento',
        100 => 'cem por cento'
    ];

    if (isset($porcentagens[$porcentagem])) {
        return $porcentagens[$porcentagem];
    } else {
        // Para valores não previstos, converter numericamente
        $extenso = numero_extenso($porcentagem);
        return $extenso . ' por cento';
    }
}
?>