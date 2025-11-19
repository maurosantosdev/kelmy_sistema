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

// Obter o ID da reserva da URL
if (!isset($_GET['reserva_id'])) {
    die("ID da reserva não fornecido.");
}

$reserva_id = $_GET['reserva_id'];
$user_id = $_SESSION['user_id'];

// Verificar se a reserva pertence ao usuário autenticado
$reserva_query = $conn->prepare("SELECT r.id, r.data, r.valor, r.status, r.payment_percentage, r.tipo_porcentagem FROM reservas r WHERE r.id = ? AND r.user_id = ?");
$reserva_query->bind_param("is", $reserva_id, $user_id);
$reserva_query->execute();
$reserva = $reserva_query->get_result()->fetch_assoc();

if (!$reserva) {
    die("Reserva não encontrada ou não autorizada.");
}

// Verificar se o contrato foi assinado
$assinatura_query = $conn->prepare("SELECT COUNT(*) as total FROM contratos_assinados WHERE user_id = ? AND reserva_id = ?");
$assinatura_query->bind_param("ii", $user_id, $reserva_id);
$assinatura_query->execute();
$assinatura_result = $assinatura_query->get_result();
$assinatura = $assinatura_result->fetch_assoc();

if ($assinatura['total'] == 0) {
    die("Contrato não foi assinado ainda.");
}

// Obter informações do usuário
$user_query = $conn->prepare("SELECT nome, data_nascimento, rg, cpf, rua, numero, bairro, cidade FROM usuarios WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

// Obter informações da reserva específica
$reservas = [$reserva];

// Obter informações da tabela descricao_contrato
$descricao_query = $conn->prepare("SELECT descricao, qtd, valor_unitario, valor_total FROM descricao_contrato");
$descricao_query->execute();
$descricao_result = $descricao_query->get_result();

$descricao_itens = [];
while ($item = $descricao_result->fetch_assoc()) {
    $descricao_itens[] = $item;
}

$conn->close();

// Gerar o conteúdo HTML do contrato para converter para PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contrato de Locação - Chácara Recanto do Sossego</title>
    <style>
        body {
            background-color: #f6f6f6; /* Cor de fundo conforme solicitado */
            font-family: Arial, sans-serif;
            font-size: 10px; /* Reduzido para melhor ajuste em PDF */
        }
        
        .contract-content {
            font-family: Arial, sans-serif;
            font-size: 10px; /* Reduzido para melhor ajuste em PDF */
            line-height: 1.4; /* Um pouco menor para economizar espaço */
            padding: 15px; /* Reduzido para melhor aproveitamento em PDF */
            background-color: #ffffff; /* Fundo branco para o conteúdo do contrato */
            max-width: 210mm; /* Largura A4 para PDF */
            min-height: 297mm; /* Altura A4 para PDF */
            margin: 0 auto; /* Centralização */
            box-shadow: 0 0 10px rgba(0,0,0,0.1); /* Sombra leve */
        }
        
        .contract-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 12px; /* Reduzido para melhor ajuste em PDF */
            color: #2c3e50;
            border-bottom: 1px solid #3498db;
            padding-bottom: 8px;
        }
        
        .contract-section {
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .contract-clause {
            margin-bottom: 10px;
            padding: 6px;
            background-color: #f8f9fa;
            border-left: 3px solid #3498db;
            border-radius: 0 3px 3px 0;
        }
        
        .contract-clause strong {
            color: #2c3e50;
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
            font-size: 10px; /* Reduzido para melhor ajuste em PDF */
        }
        
        .signature-name {
            margin-top: 5px;
            font-weight: normal;
            font-size: 10px; /* Reduzido para melhor ajuste em PDF */
        }
        
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .contract-table th, .contract-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 9px; /* Reduzido para melhor ajuste em PDF */
        }
        
        .contract-table th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }
        
        .contract-table th:nth-child(2),
        .contract-table td:nth-child(2) {
            text-align: center;
            width: 60px;
        }
        
        .contract-table th:nth-child(3),
        .contract-table td:nth-child(3),
        .contract-table th:nth-child(4),
        .contract-table td:nth-child(4) {
            text-align: right;
            width: 90px;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div style="background-color: #f6f6f6; padding: 10px 0;">
        <div class="contract-content" style="margin-top: 10px;">
        
            <div class="logo-section" style="text-align: center; margin-bottom: 15px;">
                <img src="../assets/imagens/logo_cima.jpeg" alt="Logo da Chácara" style="width: auto; height: auto; max-width: 120px; max-height: 80px; margin: 0 auto 8px; display: block;">
                <p style="font-weight: bold; margin: 4px 0 0 0; font-size: 11px;">Chácara Recanto do Sossego</p>
            </div>
        
        <div class="contract-title">
            Contrato de Locação Temporária
        </div>
        
        <div class="contract-section">
            IMÓVEL: Chácara Recanto do sossego; localizada na BR 401, km12, lote 03, vicinal Igarapé Azul, Cidade de Santa Cecília, Boa Vista – RR.
        </div>
        
        <div class="contract-section">
            LOCADOR: Kelmy Araújo Vasconcelos, Brasileiro, Casado, portador do CPF
            00194282198, RG 1329705-8 SSP/MT, simplesmente denominado "LOCADOR"
            E do outro lado: LOCATÁRIO(A) <?php echo $user['nome']; ?>, Nascido(a) <?php echo date('d/m/Y', strtotime($user['data_nascimento'])); ?>, Inscrito no RG <?php echo $user['rg']; ?> e <?php echo $user['cpf']; ?>, Residente e domiciliado na <?php echo $user['rua']; ?>, <?php echo $user['numero']; ?>, <?php echo $user['bairro']; ?>, <?php echo $user['cidade']; ?>.
        </div>
        
        <div class="contract-section">
            As partes, acima qualificadas, ajustam a locação por temporada da chácara objeto do presente contrato mediante as cláusulas e condições seguintes:
        </div>
        
        <?php if(count($reservas) > 0): ?>
        <?php foreach($reservas as $index => $reserva): ?>
        <div class="contract-clause">
            <strong>CLÁUSULA PRIMEIRA (Reserva <?php echo $index + 1; ?>):</strong><br>
            O prazo de locação de temporada será de 23 (vinte e três) horas a partir das 09:00 horas do dia <?php echo date('d/m/Y', strtotime($reserva['data'])); ?>, terminando às 08:00 horas do dia <?php echo date('d/m/Y', strtotime($reserva['data'] . ' +1 day')); ?>, data em que locatário se obriga a restituir a chácara locada, completamente desocupado e nas condições de entrada;
        </div>
        
        <div class="contract-clause">
            <strong>CLÁUSULA SEGUNDA (Reserva <?php echo $index + 1; ?>):</strong><br>
            <?php
            $payment_percentage = $reserva['payment_percentage'] ?? 50;
            if ($payment_percentage == 100) {
                // 100% pago: texto mais curto e direto
                echo "O aluguel da temporada corresponde a 1 diária totalizando R$".number_format($reserva['valor'], 2, ',', '.')." (".number_format($reserva['valor'], 2, ',', '.')." reais), totalizando assim a reserva efetivada.";
            } else {
                // 50% pago: texto padrão com detalhes de pagamento
                echo "O aluguel da temporada corresponde a 1 diária totalizando R$".number_format($reserva['valor'], 2, ',', '.')." (".number_format($reserva['valor'], 2, ',', '.')." reais). E será pago 50% do valor de sinal para contratação da data estipulada na assinatura do contrato e os outros 50% 1 (um) dia antes da data de entrada, totalizando assim a reserva efetivada;";
                if(count($reservas) > 1):
                echo " Caso o cliente selecione mais de um dia também especifique aqui.";
                endif;
                echo " Caso o cliente escolha o pagamento ser 100% do valor só mude a porcentagem adicionando as diárias.";
            }
            ?>
        </div>
        <?php endforeach; ?>
        
        <?php if(count($reservas) > 0): ?>
        <div class="contract-clause">
            <strong>VALOR TOTAL:</strong><br>
            O valor total para <?php echo count($reservas); ?> diárias<?php if(count($reservas) > 1) echo 's'; ?> é de R$<?php 
            $total_valor = array_sum(array_column($reservas, 'valor')); 
            echo number_format($total_valor, 2, ',', '.'); 
            ?> (<?php echo number_format($total_valor, 2, ',', '.'); ?> reais).
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="contract-clause">
            <strong>CLÁUSULA PRIMEIRA:</strong><br>
            Nenhuma reserva encontrada.
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
                    <div class="signature-label">_________________________________<br>Locatário(a)</div>
                    <div class="signature-name"><?php echo $user['nome']; ?></div>
                </div>
                <div class="signature-item">
                    <div class="signature-label">_________________________________<br>Locador</div>
                    <div class="signature-name">Kelmy Araújo Vasconcelos</div>
                </div>
            </div>
            <?php if($assinatura['total'] > 0): ?>
            <div style="margin-top: 10px; text-align: center;">
                <div style="margin-top: 5px; padding: 3px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 3px; display: inline-block; font-size: 9px;">
                    <span style="color: #155724; font-weight: bold;">[Assinatura] Documento assinado digitalmente</span>
                </div>
            </div>
            <?php endif; ?>
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
        $pdf->SetTitle('Contrato de Locação - Chácara Recanto do Sossego');
        $pdf->SetSubject('Contrato de Locação');
        $pdf->SetKeywords('Contrato, PDF, Chácara');

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
        
        // Enviar o PDF para download
        $pdf->Output('contrato_chacara_' . $reserva_id . '.pdf', 'D');
    } else {
        // Se o TCPDF não estiver instalado, exibir o HTML e mostrar instruções para salvar como PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="contrato_chacara_' . $reserva_id . '.html"');
        echo $html_content;
        echo '<script>
            alert("Para salvar como PDF, use a função \'Salvar como PDF\' do seu navegador (Ctrl+P ou Cmd+P).");
        </script>';
    }
} else {
    // Se o autoload do Composer não existir, exibir o HTML e mostrar instruções para salvar como PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="contrato_chacara_' . $reserva_id . '.html"');
    echo $html_content;
    echo '<script>
        alert("Para salvar como PDF, use a função \'Salvar como PDF\' do seu navegador (Ctrl+P ou Cmd+P).");
    </script>';
}
?>