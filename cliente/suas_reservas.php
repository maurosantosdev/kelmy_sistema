<?php
// Verificar se o usuário está autenticado
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e se o usuário ainda existe no banco de dados
if (!isset($_SESSION['user_id'])) {
    // Redirecionar para página de login se não estiver autenticado
    header("Location: login.php");
    exit();
} else {
    // Conectar ao banco de dados para verificar se o usuário ainda existe
    require_once '../php/db_connect.php';

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Usuário não existe mais no banco, limpar a sessão
        $_SESSION = array();

        // Se a sessão usa um cookie para persistência, exclua-o também
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        // Redirecionar para login
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Suas Reservas - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.8em;
            font-weight: 500;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            color: white;
            min-width: 60px;
        }

        .w-100 {
            width: 100% !important;
        }

        .status-pending {
            background-color: #f0ad4e !important; /* Cor amarela para pendente */
        }

        .status-approved {
            background-color: #5cb85c !important; /* Cor verde para confirmado */
        }

        .status-cancelled {
            background-color: #d9534f !important; /* Cor vermelha para cancelado */
        }

        .status-other {
            background-color: #777 !important; /* Cor padrão para outros status */
        }

        .payment-summary-item {
            background-color: #d4edda !important; /* Fundo verde claro para o resumo de pagamento */
            border: 1px solid #c3e6cb !important;
            border-radius: 8px !important;
        }

        .divider {
            background-color: transparent !important;
            padding: 0 !important;
            margin: 5px 0 !important;
            border: none !important;
        }

        .divider hr {
            border: 0;
            border-top: 1px solid #ddd;
            margin: 5px 0;
        }

        .btn-lg {
            padding: 12px 20px !important;
            font-size: 1.1em !important;
        }

        /* Estilos para remover texturas e garantir texto branco nos botões */
        .btn-success {
            background-color: #28a745 !important;
            color: white !important;
            border: none !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .btn-primary {
            background-color: #007bff !important;
            color: white !important;
            border: none !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        .btn-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
            border: none !important;
            box-shadow: none !important;
            text-shadow: none !important;
            font-weight: normal !important;
        }

        .btn-danger {
            background-color: #dc3545 !important;
            color: white !important;
            border: none !important;
            box-shadow: none !important;
            text-shadow: none !important;
            font-weight: normal !important;
        }

        .btn-info {
            background-color: #17a2b8 !important;
            color: white !important;
            border: none !important;
            box-shadow: none !important;
            text-shadow: none !important;
            font-weight: normal !important;
        }

        /* Estilo para container de botões lado a lado */
        .button-container {
            display: flex !important;
            gap: 10px !important;
            flex-wrap: wrap !important;
            margin-top: 10px !important;
        }

        .button-container .btn {
            flex: 1 !important;
            min-width: 150px !important;
        }

        /* Estilos para os cards de reserva */
        .payment-summary-item {
            background-color: #ffffff !important;
            border: 1px solid #e9ecef !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
            transition: box-shadow 0.3s ease !important;
        }

        .payment-summary-item:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1) !important;
        }

        .payment-summary-item h3 {
            color: #2c3e50 !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
        }

        @media (max-width: 768px) {
            .payment-summary-item h3 {
                font-size: 0.95rem !important;
            }
        }

        .table th {
            border-top: none !important;
            font-weight: 600 !important;
            color: #495057 !important;
        }

        .table td {
            vertical-align: middle !important;
            padding: 0.75rem 0.5rem !important;
        }

        .table tr {
            border-bottom: 1px solid #e9ecef !important;
        }

        .table tr:last-child {
            border-bottom: none !important;
        }

        /* Responsividade para dispositivos móveis */
        @media (max-width: 768px) {
            .card-body {
                padding: 1rem !important;
            }

            .payment-summary-item {
                padding: 1rem !important;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem !important;
                font-size: 0.85rem !important;
            }

            .btn {
                padding: 0.4rem 0.75rem !important;
                font-size: 0.8rem !important;
            }

            .h6, h6 {
                font-size: 0.95rem !important;
            }
        }

        @media (max-width: 576px) {
            #pix-modal-content {
                margin: 10px !important;
                max-width: calc(100% - 20px) !important;
            }

            .pix-details {
                font-size: 0.9rem !important;
            }

            .pix-details strong {
                font-size: 0.9rem !important;
            }

            .table-responsive {
                font-size: 0.85rem !important;
            }

            .table th,
            .table td {
                padding: 0.4rem 0.2rem !important;
            }

            #pix-key-input {
                font-size: 0.8rem !important;
            }
        }

        .status-badge {
            padding: 0.3em 0.6em !important;
            font-size: 0.85em !important;
            border-radius: 20px !important;
        }

        .status-pendente {
            background-color: #f0ad4e !important;
            color: white !important;
        }

        .status-confirmado {
            background-color: #5cb85c !important;
            color: white !important;
        }

        .status-cancelado {
            background-color: #d9534f !important;
            color: white !important;
        }

        .btn-lg {
            padding: 10px 16px !important;
            font-size: 1rem !important;
            border-radius: 8px !important;
        }

        .btn {
            border-radius: 8px !important;
            padding: 0.5rem 1rem !important;
        }

        /* Estilos para o modal PIX */

        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Correções para o footer fixo */
        body {
            padding-bottom: 60px; /* Espaço para o footer fixo */
        }

        /* Estilos responsivos para o cabeçalho com nome do usuário */
        header.bg-white.text-dark.py-3 {
            padding: 0.75rem 0 !important;
        }

        header .h5 {
            font-size: 1rem;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.6rem 0 !important;
            }

            header .h5 {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.5rem 0 !important;
            }

            header .h5 {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 400px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.4rem 0 !important;
            }

            header .h5 {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 350px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.3rem 0 !important;
            }

            header .h5 {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            header.bg-white.text-dark.py-3 {
                padding: 0.25rem 0 !important;
            }

            header .h5 {
                font-size: 0.7rem;
            }
        }

        /* Estilo para o container do header */
        .d-flex.justify-content-between.align-items-center {
            gap: 0.25rem;
        }

        @media (max-width: 400px) {
            .d-flex.justify-content-between.align-items-center {
                gap: 0.2rem;
            }
        }

        @media (max-width: 350px) {
            .d-flex.justify-content-between.align-items-center {
                gap: 0.15rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            .d-flex.justify-content-between.align-items-center {
                gap: 0.1rem;
            }
        }

        /* Estilo específico para o botão no header */
        header .btn.btn-light.btn-sm {
            min-height: 1.5rem;
            min-width: auto;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            header .btn.btn-light.btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            header .btn.btn-light.btn-sm {
                padding: 0.15rem 0.3rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 400px) {
            header .btn.btn-light.btn-sm {
                padding: 0.12rem 0.25rem;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 350px) {
            header .btn.btn-light.btn-sm {
                padding: 0.1rem 0.2rem;
                font-size: 0.65rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            header .btn.btn-light.btn-sm {
                min-height: 1.1rem;
                padding: 0.05rem 0.15rem !important;
                font-size: 0.6rem;
            }
        }
    </style>
</head>
<body>

<div id="suasReservasPageCliente" class="container-fluid">
    <header class="bg-white text-dark py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0" id="header-title">Suas Reservas</h1>
                <a href="#" id="logout-link-reservas" class="btn btn-light btn-sm">Sair</a>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 30px; padding-bottom: 80px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4 pb-2 border-bottom">
                    <h2 class="h5 mb-0 text-dark">
                        <i class="fas fa-calendar-check me-2"></i>Suas Reservas
                    </h2>
                </div>
                <div id="reservas-list">
                    <!-- As reservas serão carregadas aqui via JAVASCRIPT -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="text-muted mt-3" style="font-size: 0.9rem;">Carregando suas reservas...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<footer class="fixed-bottom bg-dark text-white py-2">
    <div class="container-fluid px-0">
        <nav class="row g-0 text-center">
            <div class="col-3 py-2">
                <a href="../index.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-home mb-1"></i>
                    <small>Inicio</small>
                </a>
            </div>
            <div class="col-3 py-2">
                <a href="reserva.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-calendar-alt mb-1"></i>
                    <small>Reserve</small>
                </a>
            </div>
            <div class="col-3 py-2">
                <a href="suas_reservas.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center active">
                    <i class="fas fa-calendar mb-1"></i>
                    <small>Reservas</small>
                </a>
            </div>
            <div class="col-3 py-2">
                <a href="perfil.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-user mb-1"></i>
                    <small>Perfil</small>
                </a>
            </div>
        </nav>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Variável para armazenar o ID do temporizador de fechamento automático do modal PIX
let pixModalCloseTimer = null;

// Função para carregar as reservas do usuário
function loadUserReservations() {
    console.log('Carregando reservas do usuário...');
    $.ajax({
        url: '../php/get_user_reservations.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Reservas carregadas com sucesso:', response);
            // Adicionar o nome do cliente no cabeçalho
            if (response.user_name) {
                document.getElementById('header-title').textContent = 'Olá ' + response.user_name;
            } else {
                document.getElementById('header-title').textContent = 'Suas Reservas';
            }

            // Verificar se veio da confirmação de pagamento e mostrar mensagem
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('payment_confirmed') === '1') {
                // Exibir notificação de confirmação de pagamento
                Swal.fire({
                    title: '🎉 Ótima notícia!',
                    text: 'Seu pagamento foi confirmado e suas reservas estão marcadas como confirmadas. Aproveite seu momento especial na Chácara Recanto do Sossego!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });

                // Remover o parâmetro da URL para que a mensagem não apareça novamente ao recarregar
                window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
            }
            // Exibir mensagem de confirmação se houve mudança recente de status
            else if(response.recent_status_change === true) {
                // Exibir notificação de confirmação de pagamento
                Swal.fire({
                    title: '🎉 Ótima notícia!',
                    text: 'Seu pagamento foi confirmado e suas reservas estão marcadas como confirmadas. Aproveite seu momento especial na Chácara Recanto do Sossego!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            }

            // Verificar se há reservas com confirmação recente mesmo sem o parâmetro na URL
            if (response.reservations && Array.isArray(response.reservations)) {
                let hasRecentConfirmation = false;

                // Verificar se alguma reserva tem status confirmado e payment_confirmed_at recente
                response.reservations.forEach(function(reserva) {
                    if (reserva.status === 'confirmado' && reserva.payment_confirmed_at) {
                        const confirmedAt = new Date(reserva.payment_confirmed_at);
                        const now = new Date();
                        const diffMinutes = Math.floor((now - confirmedAt) / 60000); // diferença em minutos

                        // Considerar como recente se foi confirmado nos últimos 10 minutos
                        if (diffMinutes < 10) {
                            hasRecentConfirmation = true;
                        }
                    }
                });

                // Também verificar usando o campo formatado para maior precisão
                if (!hasRecentConfirmation) {
                    response.reservations.forEach(function(reserva) {
                        if (reserva.status === 'confirmado' && reserva.payment_confirmed_at_formatted) {
                            // Parse da data formatada (formato DD/MM/YYYY HH:MM:SS)
                            const dateStr = reserva.payment_confirmed_at_formatted;
                            const [datePart, timePart] = dateStr.split(' ');
                            if (datePart && timePart) {
                                const [day, month, year] = datePart.split('/');
                                const [hour, minute, second] = timePart.split(':');
                                const confirmedAt = new Date(year, month - 1, day, hour, minute, second);
                                const now = new Date();
                                const diffMinutes = Math.floor((now - confirmedAt) / 60000); // diferença em minutos

                                // Considerar como recente se foi confirmado nos últimos 10 minutos
                                if (diffMinutes < 10) {
                                    hasRecentConfirmation = true;
                                }
                            }
                        }
                    });
                }

                // Se houver confirmação recente e não estamos vindo da página de reserva, exibir mensagem
                if (hasRecentConfirmation && !urlParams.get('payment_confirmed')) {
                    Swal.fire({
                        title: '🎉 Ótima notícia!',
                        text: 'Detectamos uma confirmação de pagamento recente! Suas reservas estão marcadas como confirmadas. Aproveite seu momento especial na Chácara Recanto do Sossego!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                }
            }

            if(response.success && response.reservations.length > 0) {
                // Ordenar todas as reservas por data em ordem crescente (mais antiga primeiro)
                try {
                    response.reservations.sort((a, b) => {
                        // Converter datas no formato dd/mm/yyyy para yyyy-mm-dd para comparação
                        if (a.data_reserva && b.data_reserva) {
                            const [dayA, monthA, yearA] = a.data_reserva.split('/');
                            const [dayB, monthB, yearB] = b.data_reserva.split('/');
                            const dateA = new Date(`${yearA}-${monthA}-${dayA}`);
                            const dateB = new Date(`${yearB}-${monthB}-${dayB}`);
                            return dateA - dateB;
                        }
                        // Se não tiver o campo data_reserva, tentar com outro formato de data
                        return 0;
                    });
                } catch (e) {
                    console.error('Erro ao ordenar reservas:', e);
                    // Se der erro na ordenação, continuar sem ordenar
                }

                // Filtrar e calcular total das reservas pendentes
                let reservasPendentes = response.reservations.filter(reserva => reserva.status === 'pendente');
                let totalPendente = 0;

                reservasPendentes.forEach(function(reserva) {
                    // Remover o R$, pontos e vírgulas e converter para número
                    let valor = parseFloat(reserva.valor_formatado.replace('R$ ', '').replace(/\./g, '').replace(',', '.'));
                    totalPendente += valor;
                });

                let html = '<div class="list-group">';

                // Agora, vamos ordenar todas as reservas por data
                let todasReservas = response.reservations;
                try {
                    todasReservas.sort((a, b) => {
                        if (a.data_reserva && b.data_reserva) {
                            const [dayA, monthA, yearA] = a.data_reserva.split('/');
                            const [dayB, monthB, yearB] = b.data_reserva.split('/');
                            const dateA = new Date(`${yearA}-${monthA}-${dayA}`);
                            const dateB = new Date(`${yearB}-${monthB}-${dayB}`);
                            return dateA - dateB;
                        }
                        return 0;
                    });
                } catch (e) {
                    console.error('Erro ao ordenar todas as reservas:', e);
                }


                // Agrupar todas as reservas por ID de pagamento (mp_payment_id) do Mercado Pago
                let gruposPorPagamento = [];
                let gruposProcessados = new Set();

                // Primeiro, agrupar as reservas que têm o mesmo mp_payment_id
                for (let i = 0; i < todasReservas.length; i++) {
                    const reserva = todasReservas[i];

                    // Se a reserva tem um pagamento associado e ainda não foi processada
                    if (reserva.mp_payment_id && !gruposProcessados.has(reserva.mp_payment_id)) {
                        // Encontrar todas as reservas com o mesmo ID de pagamento
                        const reservasMesmoPagamento = todasReservas.filter(r => r.mp_payment_id === reserva.mp_payment_id);

                        if (reservasMesmoPagamento.length > 0) {
                            gruposPorPagamento.push(reservasMesmoPagamento);
                            gruposProcessados.add(reserva.mp_payment_id);
                        }
                    }
                }

                // Depois, adicionar reservas que não têm pagamento associado como grupos individuais
                todasReservas.forEach(reserva => {
                    if (!reserva.mp_payment_id) {
                        gruposPorPagamento.push([reserva]);
                    }
                });

                // Exibir cada grupo como um pagamento/contrato diferente
                gruposPorPagamento.forEach(function(grupo) {
                    if (grupo.length > 0) {
                        // Calcular o valor total para este grupo (contrato)
                        let grupoTotal = 0;
                        grupo.forEach(function(reserva) {
                            if (reserva.valor_formatado) {
                                let valor = parseFloat(reserva.valor_formatado.replace('R$ ', '').replace(/\./g, '').replace(',', '.'));
                                if (!isNaN(valor)) {
                                    grupoTotal += valor;
                                }
                            }
                        });

                        // Determinar o status principal do grupo (priorizando pendente > confirmado > cancelado)
                        const statuses = grupo.map(r => r.status);
                        let grupoStatus = 'outro';
                        if (statuses.includes('pendente')) {
                            grupoStatus = 'pendente';
                        } else if (statuses.includes('confirmado')) {
                            grupoStatus = 'confirmado';
                        } else if (statuses.includes('cancelado')) {
                            grupoStatus = 'cancelado';
                        }

                        // Verificar se todas as reservas do grupo estão assinadas
                        const todasAssinadas = grupo.every(r => r.contrato_assinado);
                        const todasComPagamento = grupo.every(r => r.payment_confirmed_at);

                        html += '<div class="payment-summary-item border rounded-3 p-4 mb-4">';

                        // Título do contrato baseado no status
                        let statusIcon = '';
                        let statusColor = '';
                        if (grupoStatus === 'pendente') {
                            statusIcon = '<i class="fas fa-clock text-warning me-2"></i>';
                            statusColor = 'text-warning';
                        } else if (grupoStatus === 'confirmado' && !todasAssinadas) {
                            statusIcon = '<i class="fas fa-file-contract text-info me-2"></i>';
                            statusColor = 'text-info';
                        } else if (grupoStatus === 'confirmado' && todasAssinadas) {
                            statusIcon = '<i class="fas fa-check-circle text-success me-2"></i>';
                            statusColor = 'text-success';
                        } else if (grupoStatus === 'cancelado') {
                            statusIcon = '<i class="fas fa-times-circle text-danger me-2"></i>';
                            statusColor = 'text-danger';
                        } else {
                            statusIcon = '<i class="fas fa-file-alt text-secondary me-2"></i>';
                            statusColor = 'text-secondary';
                        }

                        html += '<div class="d-flex justify-content-between align-items-start mb-3">';
                        html += '<h3 class="h6 mb-0 ' + statusColor + '">' + statusIcon;
                        if (grupoStatus === 'pendente') {
                            html += 'Pagamento Pendente (' + grupo.length + ' diária' + (grupo.length > 1 ? 's' : '') + ')';
                        } else if (grupoStatus === 'confirmado' && !todasAssinadas) {
                            html += 'Contrato Não Assinado (' + grupo.length + ' diária' + (grupo.length > 1 ? 's' : '') + ')';
                        } else if (grupoStatus === 'confirmado' && todasAssinadas) {
                            html += 'Contrato Assinado (' + grupo.length + ' diária' + (grupo.length > 1 ? 's' : '') + ')';
                        } else if (grupoStatus === 'cancelado') {
                            html += 'Reserva Cancelada (' + grupo.length + ' diária' + (grupo.length > 1 ? 's' : '') + ')';
                        } else {
                            html += 'Contrato (' + grupo.length + ' diária' + (grupo.length > 1 ? 's' : '') + ')';
                        }
                        html += '</h3>';

                        if (grupoTotal > 0) {
                            html += '<div class="text-end">';
                            html += '<p class="mb-0 text-muted small">Valor Total</p>';
                            html += '<h5 class="mb-0 text-success">R$ ' + grupoTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</h5>';
                            html += '</div>';
                        }
                        html += '</div>';

                        // Adicionar detalhes das reservas do grupo
                        html += '<div class="table-responsive">';
                        html += '<table class="table table-borderless mb-0">';
                        html += '<thead class="table-light">';
                        html += '<tr><th>Data</th><th>Status</th><th>Valor</th><th>Confirmação</th><th>Observações</th></tr>';
                        html += '</thead><tbody>';

                        grupo.forEach(function(reserva) {
                            html += '<tr>';
                            html += '<td><i class="fas fa-calendar-day text-primary me-1"></i> ' + reserva.data_formatada + '</td>';
                            html += '<td><span class="badge status-badge status-' + reserva.status + '">' + reserva.status_formatado + '</span></td>';
                            html += '<td><i class="fas fa-money-bill-wave text-success me-1"></i> ' + reserva.valor_formatado + '</td>';
                            if(reserva.payment_confirmed_at) {
                                // Usar o campo formatado do backend se disponível
                                let formattedDate = reserva.payment_confirmed_at_formatted || reserva.payment_confirmed_at;

                                // Converter a data para o formato adequado se estiver no formato ISO
                                try {
                                    // Verifica se a data está no formato ISO (YYYY-MM-DD HH:MM:SS)
                                    const dateObj = new Date(reserva.payment_confirmed_at);
                                    if (isNaN(dateObj.getTime())) {
                                        // Se não for uma data válida, usar o valor formatado do backend
                                        formattedDate = reserva.payment_confirmed_at_formatted || reserva.payment_confirmed_at;
                                    } else {
                                        formattedDate = dateObj.toLocaleString('pt-BR');
                                    }
                                } catch (e) {
                                    formattedDate = reserva.payment_confirmed_at_formatted || reserva.payment_confirmed_at;
                                }
                                html += '<td><i class="fas fa-check-circle text-success me-1"></i> ' + formattedDate + '</td>';
                            } else {
                                html += '<td>-</td>';
                            }
                            if(reserva.observacoes) {
                                html += '<td><i class="fas fa-sticky-note text-muted me-1"></i> ' + escapeHtml(reserva.observacoes) + '</td>';
                            } else {
                                html += '<td>-</td>';
                            }
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                        html += '</div>';

                        html += '<div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">';

                        // Botões diferentes com base no status do grupo
                        if (grupoStatus === 'pendente') {
                            html += '<button class="btn btn-success pagamento-unico-btn flex-fill" data-reservas-ids=\'' + JSON.stringify(grupo.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(grupo.map(r => r.data_formatada)) + '\' data-valor-total="' + grupoTotal + '"><i class="fas fa-credit-card me-1"></i>' + (grupo.length > 1 ? 'Pagar (' + grupo.length + ')' : 'Pagar') + '</button>';
                        } else if (grupoStatus === 'confirmado' && !todasAssinadas) {
                            if (todasComPagamento) {
                                html += '<button class="btn btn-primary emitir-todos-recibos-btn flex-fill" data-reservas-ids=\'' + JSON.stringify(grupo.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(grupo.map(r => r.data_formatada)) + '\' data-reservas-valores=\'' + JSON.stringify(grupo.map(r => r.valor_formatado)) + '\'><i class="fas fa-file-invoice me-1"></i>Recibo</button>';
                            }
                            html += '<button class="btn btn-info assinar-todos-btn flex-fill" data-reservas-ids=\'' + JSON.stringify(grupo.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(grupo.map(r => r.data_formatada)) + '\'><i class="fas fa-signature me-1"></i>Assinar</button>';
                        } else if (grupoStatus === 'confirmado' && todasAssinadas) {
                            html += '<button class="btn btn-primary emitir-todos-recibos-btn flex-fill" data-reservas-ids=\'' + JSON.stringify(grupo.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(grupo.map(r => r.data_formatada)) + '\' data-reservas-valores=\'' + JSON.stringify(grupo.map(r => r.valor_formatado)) + '\'><i class="fas fa-file-invoice me-1"></i>Recibo</button>';
                            html += '<button class="btn btn-warning ver-contrato-todos-btn flex-fill" data-reservas-ids=\'' + JSON.stringify(grupo.map(r => r.id)) + '\'><i class="fas fa-file-contract me-1"></i>Ver Contrato</button>';
                        }

                        // Botão de exclusão para pendentes e canceladas
                        if (grupoStatus === 'pendente' || grupoStatus === 'cancelado') {
                            html += '<button class="btn btn-outline-danger excluir-todas-btn flex-fill" data-reservas-ids=\'' + JSON.stringify(grupo.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(grupo.map(r => r.data_formatada)) + '\'><i class="fas fa-trash-alt me-1"></i>Excluir</button>';
                        }

                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="card-footer bg-transparent border-0 pt-0"><div class="divider my-2"><hr></div></div>';
                    }
                });


                html += '</div>';
                $('#reservas-list').html(html);
            } else {
                $('#reservas-list').html(`
                    <div class="text-center py-5">
                        <div class="mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border-radius: 50%;">
                            <i class="fas fa-calendar-times text-muted" style="font-size: 1.5rem;"></i>
                        </div>
                        <h5 class="text-muted mb-2" style="font-size: 1rem;">Você ainda não possui reservas</h5>
                        <p class="text-muted mb-4" style="font-size: 0.9rem;">Faça sua primeira reserva para começar a planejar sua estadia conosco</p>
                        <a href="reserva.php" class="btn btn-primary px-4" style="font-size: 0.9rem;">
                            <i class="fas fa-calendar-plus me-2"></i>Fazer Reserva
                        </a>
                    </div>
                `);
            }
        },
        error: function() {
            $('#reservas-list').html(`
                <div class="alert alert-light border text-center py-4" role="alert">
                    <div class="mx-auto mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border-radius: 50%;">
                        <i class="fas fa-exclamation-triangle text-muted" style="font-size: 1.2rem;"></i>
                    </div>
                    <h5 class="text-muted mb-2" style="font-size: 1rem;">Erro ao carregar as reservas</h5>
                    <p class="text-muted mb-3" style="font-size: 0.9rem;">Ocorreu um problema ao carregar suas reservas. Por favor, tente novamente mais tarde.</p>
                    <button type="button" class="btn btn-outline-primary px-4" style="font-size: 0.9rem;" onclick="loadUserReservas()">
                        <i class="fas fa-sync-alt me-2"></i>Tentar Novamente
                    </button>
                </div>
            `);
        }
    });
}

// Adicionar manipulador de logout quando o documento estiver pronto
$(document).ready(function() {
    // Adicionar manipulador de logout
    $('#logout-link-reservas').on('click', function(e) {
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

    // Carregar reservas do usuário quando a página é carregada
    loadUserReservations();
});

// Adicionar manipulador para o botão "Realizar Pagamento"
$(document).on('click', '.realizar-pagamento-btn', function() {
    console.log('Botão Realizar Pagamento clicado');
    const reservaId = $(this).data('reserva-id');
    const reservaData = $(this).data('reserva-data');
    const reservaValor = $(this).data('reserva-valor');

    console.log('Dados da reserva:', { reservaId, reservaData, reservaValor });

    // Atualizar os elementos do modal com os dados da reserva
    $('#pix-data').text(reservaData);
    $('#pix-valor').text(reservaValor);

    // Armazenar o ID da reserva no modal para uso posterior
    $('#pixModal').data('reserva-id', reservaId);

    // Exibir o modal de pagamento PIX
    var bootstrapModal = new bootstrap.Modal(document.getElementById('pixModal'));
    bootstrapModal.show();
    console.log('Modal exibido, chamando gerarPixParaReserva');

    // Gerar QR Code PIX para esta reserva específica
    gerarPixParaReserva(reservaId, reservaData, reservaValor);

    // Atualizar o layout após mostrar o modal
    setTimeout(function() {
        $.mobile.resetActivePageHeight();
        $(document).trigger('updatelayout');
    }, 100);
});

// Adicionar manipulador para o botão "Pagamento Único"
$(document).on('click', '.pagamento-unico-btn', function() {
    console.log('Botão Pagamento Único clicado');
    const reservasIds = $(this).data('reservas-ids');
    const reservasDatas = $(this).data('reservas-datas');
    const valorTotal = $(this).data('valor-total');

    console.log('Dados das reservas:', { reservasIds, reservasDatas, valorTotal });

    // Formatar o valor total para exibição
    const valorFormatado = 'R$ ' + valorTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Atualizar os elementos do modal com os dados combinados
    $('#pix-data').text('Várias datas (' + reservasDatas.length + ')');
    $('#pix-valor').text(valorFormatado);

    // Armazenar os IDs das reservas no modal para uso posterior
    $('#pixModal').data('reservas-ids', reservasIds);

    // Exibir o modal de pagamento PIX
    var bootstrapModal = new bootstrap.Modal(document.getElementById('pixModal'));
    bootstrapModal.show();
    console.log('Modal exibido, chamando gerarPixParaVariasReservas');

    // Gerar QR Code PIX para todas as reservas pendentes
    gerarPixParaVariasReservas(reservasIds, reservasDatas, valorTotal);

    // Atualizar o layout após mostrar o modal
    setTimeout(function() {
        $.mobile.resetActivePageHeight();
        $(document).trigger('updatelayout');
    }, 100);
});

// Função para gerar pagamento PIX para reserva específica
function gerarPixParaReserva(reservaId, reservaData, reservaValor) {
    console.log('Iniciando gerarPixParaReserva com dados:', { reservaId, reservaData, reservaValor });

    // Atualizar o botão de confirmação para mostrar que está processando
    $('#confirm-payment-btn').text('Gerando cobrança...').prop('disabled', true);

    // Criar cobrança no Mercado Pago para a reserva existente usando fetch
    fetch('../php/create_mp_payment_for_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reserva_id=' + encodeURIComponent(reservaId)
    })
    .then(async response => {
        console.log('Resposta recebida do servidor para criação de pagamento:', response.status);

        // Verificar se a resposta é ok antes de tentar converter para JSON
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro na requisição para criar pagamento:', response.status, errorText);
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText} - ${errorText}`);
        }
        // Verificar se a resposta é JSON antes de fazer o parse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            console.error('Resposta não é JSON:', responseText);
            throw new Error(`Resposta não é JSON: ${responseText}`);
        }
        return response.json();
    })
    .then(response => {
        console.log('Resposta JSON recebida:', response);

        if(response.success) {
            // Atualizar o modal com o QR Code do Mercado Pago
            if (response.qr_code_base64) {
                const container = $('#payment-status-container');
                container.html(`
                    <p><strong>Check-in:</strong> 09:00 | <strong>Check-out:</strong> 08:00</p>
                    <div id="pix-qrcode-container" style="display:flex; justify-content:center; align-items:center; margin: 15px 0; min-height: 200px;">
                        <img id="pix-qr-image" src="data:image/png;base64,${response.qr_code_base64}" alt="QR Code PIX" style="width: 180px; height: 180px; padding: 10px; background-color: white; border-radius: 8px; display: block;">
                    </div>
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                        <div class="pix-copy-paste">
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly onclick="this.select();" onfocus="this.select();">
                            <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <button id="close-pix-modal" class="ui-btn">Fechar</button>
                    <div id="payment-status-indicator" style="text-align: center; margin-top: 10px; display: none;">
                        <p>Verificando status do pagamento...</p>
                    </div>
                `);
            } else if (response.payment_url) {
                const container = $('#payment-status-container');
                container.html(`
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                        <div class="pix-copy-paste">
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly onclick="this.select();" onfocus="this.select();">
                            <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <button id="close-pix-modal" class="ui-btn">Fechar</button>
                    <div id="payment-status-indicator" style="text-align: center; margin-top: 10px; display: none;">
                        <p>Verificando status do pagamento...</p>
                    </div>
                `);
            } else {
                const container = $('#payment-status-container');
                container.html(`
                    <p style="color: #e74c3c; margin-bottom: 15px;">Erro: Não foi possível gerar o código PIX. Tente novamente mais tarde ou entre em contato com o suporte.</p>
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                        <div class="pix-copy-paste">
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly onclick="this.select();" onfocus="this.select();">
                            <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <button id="close-pix-modal" class="ui-btn">Fechar</button>
                `);
            }

            // Esconder o botão de confirmação já que agora o pagamento é verificado automaticamente
            $('#confirm-payment-btn').hide();

            // Iniciar verificação automática do status do pagamento após exibir o QR Code
            // Aguardar 3 segundos antes de começar a verificar automaticamente
            console.log('Iniciando verificação de status do pagamento em 3 segundos');
            setTimeout(() => {
                // Verificar se o modal ainda está aberto antes de iniciar a verificação
                const pixModalElement = document.getElementById('pixModal');
                if (pixModalElement.classList.contains('show')) {
                    console.log('Modal ainda está visível, iniciando checkPaymentStatusForReserva');
                    checkPaymentStatusForReserva(reservaId);
                } else {
                    console.log('Modal não está mais visível, não iniciando verificação');
                }
            }, 3000); // 3 segundos
        } else {
            console.error('Erro ao criar cobrança:', response.message);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao criar cobrança: ' + response.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
            $('#confirm-payment-btn').hide();
        }
    })
    .catch(error => {
        console.error('Erro na criação da cobrança:', error);
        // Verificar se é um erro de parsing JSON e exibir mensagem mais específica
        if (error instanceof SyntaxError) {
            Swal.fire({
                title: 'Erro de Comunicação',
                text: 'Erro de comunicação com o servidor: formato de resposta inválido.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire({
                title: 'Erro de Comunicação',
                text: 'Erro na comunicação com o servidor: ' + error.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
        $('#confirm-payment-btn').hide();
    });
}

// Função para gerar pagamento PIX para várias reservas
function gerarPixParaVariasReservas(reservasIds, reservasDatas, valorTotal) {
    console.log('Iniciando gerarPixParaVariasReservas com dados:', { reservasIds, reservasDatas, valorTotal });

    // Atualizar o botão de confirmação para mostrar que está processando
    $('#confirm-payment-btn').text('Gerando cobrança...').prop('disabled', true);

    // Criar cobrança no Mercado Pago para várias reservas existentes usando fetch
    fetch('../php/create_mp_payment_for_multiple_reservations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reservas_ids=' + encodeURIComponent(JSON.stringify(reservasIds))
    })
    .then(async response => {
        console.log('Resposta recebida do servidor para criação de pagamento múltiplo:', response.status);

        // Verificar se a resposta é ok antes de tentar converter para JSON
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro na requisição para criar pagamento múltiplo:', response.status, errorText);
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText} - ${errorText}`);
        }
        // Verificar se a resposta é JSON antes de fazer o parse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            console.error('Resposta não é JSON:', responseText);
            throw new Error(`Resposta não é JSON: ${responseText}`);
        }
        return response.json();
    })
    .then(response => {
        console.log('Resposta JSON recebida para pagamento múltiplo:', response);

        if(response.success) {
            // Atualizar o modal com o QR Code do Mercado Pago
            if (response.qr_code_base64) {
                const container = $('#payment-status-container');
                container.html(`
                    <p><strong>Check-in:</strong> 09:00 | <strong>Check-out:</strong> 08:00</p>
                    <div id="pix-qrcode-container" style="display:flex; justify-content:center; align-items:center; margin: 15px 0; min-height: 200px;">
                        <img id="pix-qr-image" src="data:image/png;base64,${response.qr_code_base64}" alt="QR Code PIX" style="width: 180px; height: 180px; padding: 10px; background-color: white; border-radius: 8px; display: block;">
                    </div>
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                        <div class="pix-copy-paste">
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly onclick="this.select();" onfocus="this.select();">
                            <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <button id="close-pix-modal" class="ui-btn">Fechar</button>
                    <div id="payment-status-indicator" style="text-align: center; margin-top: 10px; display: none;">
                        <p>Verificando status do pagamento...</p>
                    </div>
                `);
            } else if (response.payment_url) {
                const container = $('#payment-status-container');
                container.html(`
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                        <div class="pix-copy-paste">
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly onclick="this.select();" onfocus="this.select();">
                            <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <button id="close-pix-modal" class="ui-btn">Fechar</button>
                    <div id="payment-status-indicator" style="text-align: center; margin-top: 10px; display: none;">
                        <p>Verificando status do pagamento...</p>
                    </div>
                `);
            } else {
                const container = $('#payment-status-container');
                container.html(`
                    <p style="color: #e74c3c; margin-bottom: 15px;">Erro: Não foi possível gerar o código PIX. Tente novamente mais tarde ou entre em contato com o suporte.</p>
                    <div style="margin-top: 15px;">
                        <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                        <div class="pix-copy-paste">
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly onclick="this.select();" onfocus="this.select();">
                            <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <button id="close-pix-modal" class="ui-btn">Fechar</button>
                `);
            }

            // Esconder o botão de confirmação já que agora o pagamento é verificado automaticamente
            $('#confirm-payment-btn').hide();

            // Iniciar verificação automática do status do pagamento após exibir o QR Code
            // Aguardar 3 segundos antes de começar a verificar automaticamente
            console.log('Iniciando verificação de status do pagamento múltiplo em 3 segundos');
            setTimeout(() => {
                // Verificar se o modal ainda está aberto antes de iniciar a verificação
                const pixModalElement = document.getElementById('pixModal');
                if (pixModalElement.classList.contains('show')) {
                    console.log('Modal ainda está visível, iniciando checkPaymentStatusForMultipleReservas');
                    checkPaymentStatusForMultipleReservas(reservasIds);
                } else {
                    console.log('Modal não está mais visível, não iniciando verificação');
                }
            }, 3000); // 3 segundos
        } else {
            console.error('Erro ao criar cobrança múltipla:', response.message);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro ao criar cobrança: ' + response.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
            $('#confirm-payment-btn').hide();
        }
    })
    .catch(error => {
        console.error('Erro na criação da cobrança para múltiplas reservas:', error);
        // Verificar se é um erro de parsing JSON e exibir mensagem mais específica
        if (error instanceof SyntaxError) {
            Swal.fire({
                title: 'Erro de Comunicação',
                text: 'Erro de comunicação com o servidor: formato de resposta inválido.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire({
                title: 'Erro de Comunicação',
                text: 'Erro na comunicação com o servidor: ' + error.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
        $('#confirm-payment-btn').hide();
    });
}

// Função para verificar o status do pagamento para uma reserva específica
function checkPaymentStatusForReserva(reservaId) {
    console.log('Iniciando verificação de status para reserva:', reservaId);

    // Chamar endpoint para verificar status da reserva específica
    fetch('../php/check_reservation_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reserva_id=' + encodeURIComponent(reservaId)
    })
    .then(response => {
        console.log('Resposta recebida da verificação de status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos da verificação de status:', data);

        if (data.success) {
            if (data.confirmed) {
                console.log('Pagamento confirmado para reserva:', reservaId);

                // Cancelar qualquer temporizador de fechamento automático
                if (pixModalCloseTimer) {
                    clearTimeout(pixModalCloseTimer);
                    pixModalCloseTimer = null;
                }

                // Exibir mensagem de sucesso
                Swal.fire({
                    title: 'Pagamento confirmado!',
                    text: 'Sua reserva está confirmada.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });

                // Recarregar as reservas para atualizar o status
                loadUserReservations();

                // Atualizar o indicador de status no modal
                $('#payment-status-indicator').show().html('<p style="color: #27ae60;">Pagamento confirmado! Você pode fechar o modal quando quiser.</p>');
            } else {
                // Ainda aguardando confirmação
                console.log('Pagamento ainda não confirmado, continuando verificação');
                $('#payment-status-indicator').show().html('<p style="color: #f39c12;">Aguardando confirmação de pagamento...</p>');

                // Verificar novamente após 3 segundos
                setTimeout(() => {
                    console.log('Verificando status novamente após 3 segundos');
                    checkPaymentStatusForReserva(reservaId);
                }, 3000);
            }
        } else {
            // Erro na verificação
            console.error('Erro na verificação de status:', data.message);
            $('#payment-status-indicator').show().html('<p style="color: #e74c3c;">Erro na verificação: ' + data.message + '</p>');
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status do pagamento:', error);
        $('#payment-status-indicator').show().html('<p style="color: #e74c3c;">Erro de comunicação com o servidor</p>');
    });
}

// Função para fechar o modal
function closeModal() {
    console.log('Fechando modal do PIX');
    var bootstrapModal = bootstrap.Modal.getInstance(document.getElementById('pixModal'));
    if (bootstrapModal) {
        bootstrapModal.hide();
    } else {
        // Se não houver instância, tentar fechar manualmente
        const pixModalElement = document.getElementById('pixModal');
        pixModalElement.classList.remove('show');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        pixModalElement.style.display = 'none';
    }
    $('#payment-status-indicator').hide();

    // Após fechar o modal, forçar atualização do layout para garantir que o footer fique no lugar correto
    setTimeout(function() {
        $.mobile.resetActivePageHeight();
        $(document).trigger('updatelayout');
    }, 150);
}

// Eventos para o modal de pagamento PIX
$(document).on('click', '#close-pix-modal', closeModal);

// Função para verificar o status do pagamento para múltiplas reservas
function checkPaymentStatusForMultipleReservas(reservasIds) {
    console.log('Iniciando verificação de status para múltiplas reservas:', reservasIds);

    // Chamar endpoint para verificar status das reservas específicas
    fetch('../php/check_reservation_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reservas_ids=' + encodeURIComponent(JSON.stringify(reservasIds))
    })
    .then(response => {
        console.log('Resposta recebida da verificação de status múltipla:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos da verificação de status múltipla:', data);

        if (data.success) {
            if (data.all_confirmed) {
                console.log('Pagamentos confirmados para todas as reservas:', reservasIds);

                // Cancelar qualquer temporizador de fechamento automático
                if (pixModalCloseTimer) {
                    clearTimeout(pixModalCloseTimer);
                    pixModalCloseTimer = null;
                }

                // Exibir mensagem de sucesso
                Swal.fire({
                    title: 'Pagamento confirmado!',
                    text: 'Suas reservas estão confirmadas.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });

                // Recarregar as reservas para atualizar o status
                loadUserReservations();

                // Atualizar o indicador de status no modal
                $('#payment-status-indicator').show().html('<p style="color: #27ae60;">Pagamento confirmado! Você pode fechar o modal quando quiser.</p>');
            } else {
                // Ainda aguardando confirmação
                console.log('Pagamentos ainda não confirmados, continuando verificação');
                $('#payment-status-indicator').show().html('<p style="color: #f39c12;">Aguardando confirmação de pagamento...</p>');

                // Verificar novamente após 3 segundos
                setTimeout(() => {
                    console.log('Verificando status múltiplo novamente após 3 segundos');
                    checkPaymentStatusForMultipleReservas(reservasIds);
                }, 3000);
            }
        } else {
            // Erro na verificação
            console.error('Erro na verificação de status múltipla:', data.message);
            $('#payment-status-indicator').show().html('<p style="color: #e74c3c;">Erro na verificação: ' + data.message + '</p>');
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status do pagamento para múltiplas reservas:', error);
        $('#payment-status-indicator').show().html('<p style="color: #e74c3c;">Erro de comunicação com o servidor</p>');
    });
}



// Evento para copiar chave PIX
$(document).on('click', '#copy-pix-key-btn', function() {
    const pixKeyInput = document.getElementById('pix-key-input');
    if (pixKeyInput) {
        // Try to use modern Clipboard API first (for better iOS support)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(pixKeyInput.value).then(function() {
                // Mostrar feedback visual
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-check"></i>');
                setTimeout(() => {
                    $(this).html(originalText);
                }, 2000);

                Swal.fire({
                    title: 'Sucesso!',
                    text: 'Chave PIX copiada para a área de transferência!',
                    icon: 'success',
                    confirmButtonText: 'OK',
                });
            }.bind(this)).catch(function(err) {
                // Fallback to document.execCommand for older browsers
                console.error('Erro ao copiar com Clipboard API:', err);
                fallbackCopyText(pixKeyInput);
            });
        } else {
            // Fallback for browsers that don't support Clipboard API
            fallbackCopyText(pixKeyInput);
        }
    }
});

// Fallback function for copying text that works better on iOS
function fallbackCopyText(inputElement) {
    // Create a temporary textarea element since input.select() doesn't work well on iOS
    const textArea = document.createElement('textarea');
    textArea.value = inputElement.value;
    // Move textarea off-screen to prevent scrolling to the bottom
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    textArea.style.opacity = '0';
    textArea.style.pointerEvents = 'none';
    textArea.style.zIndex = '-999999';
    document.body.appendChild(textArea);

    // iOS specific approach - need to add to DOM before selection
    if (/iPad|iPhone|iPod|Macintosh|mac os/i.test(navigator.userAgent)) {
        // For iOS, we need to add the element, then focus and select
        textArea.focus();
        textArea.setSelectionRange(0, textArea.value.length);
    } else {
        // For other browsers, select works fine
        textArea.select();
    }

    try {
        // Copy the text
        let successful = false;
        if (navigator.clipboard && window.isSecureContext) {
            // Try using clipboard API as fallback if available
            navigator.clipboard.writeText(inputElement.value).then(() => {
                successful = true;
            }).catch(() => {
                // If clipboard API fails, fall back to execCommand
                successful = document.execCommand('copy');
            });
        } else {
            // Use older execCommand method
            successful = document.execCommand('copy');
        }

        if (successful) {
            // Mostrar feedback visual
            const originalText = $('#copy-pix-key-btn').html();
            $('#copy-pix-key-btn').html('<i class="fas fa-check"></i>');
            setTimeout(() => {
                $('#copy-pix-key-btn').html(originalText);
            }, 2000);

            Swal.fire({
                title: 'Sucesso!',
                text: 'Chave PIX copiada para a área de transferência!',
                icon: 'success',
                confirmButtonText: 'OK',
            });
        } else {
            // Fallback for when copy fails
            // On iOS, sometimes we need to show the text to let users manually select and copy
            // Create a more user-friendly manual copy interface
            const manualCopyHtml = '<div style="position: relative;">' +
                                  '<div style="background-color: #f1f1f1; padding: 10px; border-radius: 4px; margin-top: 10px; user-select: text; -webkit-user-select: text; overflow-wrap: break-word; word-break: break-all;">' +
                                  inputElement.value + '</div>' +
                                  '<button id="select-all-text-btn" style="margin-top: 8px; padding: 6px 10px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Selecionar Tudo</button>' +
                                  '</div>';

            Swal.fire({
                title: 'Cópia Manual Necessária',
                html: '<p>Selecione e copie manualmente o texto abaixo:</p>' + manualCopyHtml,
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3498db',
                didOpen: () => {
                    // Add event listener for the select all button
                    document.getElementById('select-all-text-btn').addEventListener('click', function() {
                        const textDiv = document.querySelector('div[style*="background-color: #f1f1f1"]');
                        if (textDiv) {
                            // Create temporary textarea for selection
                            const tempTextArea = document.createElement('textarea');
                            tempTextArea.value = inputElement.value;
                            tempTextArea.style.position = 'fixed';
                            tempTextArea.style.left = '-999999px';
                            tempTextArea.style.top = '-999999px';
                            tempTextArea.style.opacity = '0';
                            document.body.appendChild(tempTextArea);
                            tempTextArea.focus();
                            tempTextArea.select();

                            try {
                                const success = document.execCommand('copy');
                                if (success) {
                                    Swal.fire({
                                        title: 'Sucesso!',
                                        text: 'Chave PIX copiada para a área de transferência!',
                                        icon: 'success',
                                        confirmButtonText: 'OK',
                                    });
                                } else {
                                    // On iOS, just focus and select the text in the div
                                    if (window.getSelection) {
                                        const selection = window.getSelection();
                                        const range = document.createRange();
                                        range.selectNodeContents(textDiv);
                                        selection.removeAllRanges();
                                        selection.addRange(range);
                                    }
                                }
                            } catch (e) {
                                console.error('Error during select all:', e);
                            } finally {
                                document.body.removeChild(tempTextArea);
                            }
                        }
                    });
                }
            });
        }
    } catch (err) {
        console.error('Erro ao copiar texto:', err);
        // Show manual copy instructions for iOS
        const manualCopyHtml = '<div style="position: relative;">' +
                              '<div style="background-color: #f1f1f1; padding: 10px; border-radius: 4px; margin-top: 10px; user-select: text; -webkit-user-select: text; overflow-wrap: break-word; word-break: break-all;">' +
                              inputElement.value + '</div>' +
                              '<button id="select-all-text-btn" style="margin-top: 8px; padding: 6px 10px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Selecionar Tudo</button>' +
                              '</div>';

        Swal.fire({
            title: 'Cópia Manual Necessária',
            html: '<p>Selecione e copie manualmente o texto abaixo:</p>' + manualCopyHtml,
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3498db',
            didOpen: () => {
                // Add event listener for the select all button
                document.getElementById('select-all-text-btn').addEventListener('click', function() {
                    const textDiv = document.querySelector('div[style*="background-color: #f1f1f1"]');
                    if (textDiv) {
                        // Create temporary textarea for selection
                        const tempTextArea = document.createElement('textarea');
                        tempTextArea.value = inputElement.value;
                        tempTextArea.style.position = 'fixed';
                        tempTextArea.style.left = '-999999px';
                        tempTextArea.style.top = '-999999px';
                        tempTextArea.style.opacity = '0';
                        document.body.appendChild(tempTextArea);
                        tempTextArea.focus();
                        tempTextArea.select();

                        try {
                            const success = document.execCommand('copy');
                            if (success) {
                                Swal.fire({
                                    title: 'Sucesso!',
                                    text: 'Chave PIX copiada para a área de transferência!',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                });
                            } else {
                                // On iOS, just focus and select the text in the div
                                if (window.getSelection) {
                                    const selection = window.getSelection();
                                    const range = document.createRange();
                                    range.selectNodeContents(textDiv);
                                    selection.removeAllRanges();
                                    selection.addRange(range);
                                }
                            }
                        } catch (e) {
                            console.error('Error during select all:', e);
                        } finally {
                            document.body.removeChild(tempTextArea);
                        }
                    }
                });
            }
        });
    } finally {
        // Remove the temporary textarea
        document.body.removeChild(textArea);
    }
}

// Adicionar manipulador para o botão "Excluir Reserva"
$(document).on('click', '.excluir-reserva-btn', function() {
    const reservaId = $(this).data('reserva-id');
    const reservaData = $(this).data('reserva-data');
    
    // Confirmar com o usuário antes de excluir
    if (confirm('Tem certeza que deseja excluir a reserva para o dia ' + reservaData + '? Esta ação não poderá ser desfeita.')) {
        // Fazer a requisição JAVASCRIPT para excluir a reserva
        $.ajax({
            url: '../php/delete_reservation.php',
            type: 'POST',
            data: {
                reserva_id: reservaId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Reserva excluída com sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    // Recarregar as reservas para atualizar a lista
                    loadUserReservations();
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao excluir a reserva: ' + response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Erro de Comunicação',
                    text: 'Erro na comunicação com o servidor ao tentar excluir a reserva.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }
});

// Adicionar manipulador para o botão "Excluir Todas as Reservas do Grupo"
$(document).on('click', '.excluir-todas-btn', function() {
    const reservasIds = $(this).data('reservas-ids');
    const reservasDatas = $(this).data('reservas-datas');

    if (reservasIds && reservasIds.length > 0) {
        // Criar mensagem de confirmação com as datas
        const datasStr = reservasDatas.join(', ');
        const confirmMsg = `Tem certeza que deseja excluir ${reservasIds.length} reserva${reservasIds.length > 1 ? 's' : ''} para os dias ${datasStr}? Esta ação não poderá ser desfeita.`;

        if (confirm(confirmMsg)) {
            // Excluir cada reserva individualmente
            let exclusoesSucesso = 0;
            let exclusoesTotal = reservasIds.length;

            // Processar exclusões sequencialmente
            function excluirProxima(index) {
                if (index >= reservasIds.length) {
                    // Todas as exclusões foram processadas
                    let mensagemFinal = `Exclusão concluída: ${exclusoesSucesso} de ${exclusoesTotal} reserva${exclusoesTotal > 1 ? 's' : ''} excluída${exclusoesTotal > 1 ? 's' : ''} com sucesso.`;

                    if (exclusoesSucesso < exclusoesTotal) {
                        mensagemFinal += ` ${exclusoesTotal - exclusoesSucesso} reserva${exclusoesTotal - exclusoesSucesso > 1 ? 's' : ''} não puderam ser excluídas.`;
                    }

                    Swal.fire({
                        title: exclusoesSucesso > 0 ? 'Sucesso!' : 'Atenção!',
                        text: mensagemFinal,
                        icon: exclusoesSucesso > 0 ? 'success' : 'warning',
                        confirmButtonText: 'OK'
                    });

                    // Recarregar as reservas para atualizar a lista
                    loadUserReservations();
                    return;
                }

                const reservaId = reservasIds[index];
                $.ajax({
                    url: '../php/delete_reservation.php',
                    type: 'POST',
                    data: {
                        reserva_id: reservaId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            exclusoesSucesso++;
                        } else {
                            console.error('Erro ao excluir reserva ' + reservaId + ':', response.message);
                        }
                    },
                    error: function() {
                        console.error('Erro de comunicação ao excluir reserva ' + reservaId);
                    },
                    complete: function() {
                        excluirProxima(index + 1);
                    }
                });
            }

            excluirProxima(0);
        }
    }
});

// Adicionar manipulador para o botão "Assinar Contrato Individual"
$(document).on('click', '.assinar-contrato-btn', function() {
    const reservaId = $(this).data('reserva-id');
    const reservasIds = $(this).data('reservas-ids');

    if (reservaId) {
        // Confirmar com o usuário antes de prosseguir
        Swal.fire({
            title: 'Assinar contrato individual',
            text: 'Você está prestes a assinar o contrato para esta reserva. Deseja continuar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, assinar contrato',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Se houver múltiplas reservas para o mesmo pagamento, passar todas elas
                if (reservasIds && reservasIds.length > 0) {
                    // Redirecionar para a página de contrato com múltiplos IDs de reserva
                    window.open('contrato.php?reservas_ids=' + encodeURIComponent(JSON.stringify(reservasIds)), '_blank');
                } else {
                    // Redirecionar para a página de contrato com o ID da reserva específica
                    window.open('contrato.php?reserva_id=' + encodeURIComponent(reservaId), '_blank');
                }
            }
        });
    }
});

// Adicionar manipulador para o botão "Assinar Todos"
$(document).on('click', '.assinar-todos-btn', function() {
    const reservasIds = $(this).data('reservas-ids');
    
    if (reservasIds && reservasIds.length > 0) {
        // Confirmar com o usuário antes de prosseguir
        Swal.fire({
            title: 'Assinar todos os contratos',
            text: 'Você está prestes a assinar o(s) contrato(s). Deseja continuar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, assinar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirecionar para uma página que permite assinar todos os contratos de uma vez
                const reservasIdsParam = JSON.stringify(reservasIds);
                window.open('contrato.php?reservas_ids=' + encodeURIComponent(reservasIdsParam), '_blank');
            }
        });
    }
});

// Adicionar manipulador para o botão "Emitir Recibo"
$(document).on('click', '.emitir-recibo-btn', function() {
    const reservaId = $(this).data('reserva-id');
    console.log('Botão de recibo clicado, reserva ID:', reservaId, 'Tipo:', typeof reservaId);
    
    if (!reservaId || reservaId <= 0) {
        Swal.fire({
            title: 'Erro!',
            text: 'ID da reserva inválido (' + reservaId + ').',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Obter detalhes da reserva para gerar o recibo
    $.ajax({
        url: '../php/get_reservation_details_for_receipt.php',
        type: 'GET',
        data: { reserva_id: reservaId },
        dataType: 'json',
        success: function(response) {
            console.log('Resposta do servidor:', response);
            if (response.success) {
                // Abrir o PDF do recibo diretamente
                window.open('download_recibo_pdf.php?reserva_id=' + response.id, '_blank');
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao obter dados da reserva: ' + response.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição JAVASCRIPT:', error);
            console.error('Detalhes:', xhr.responseText);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro na comunicação com o servidor ao buscar dados da reserva. Verifique o console para mais detalhes.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});

// Adicionar manipulador para o botão "Emitir Todos os Recibos"
$(document).on('click', '.emitir-todos-recibos-btn', function() {
    const reservasIds = $(this).data('reservas-ids');
    const reservasDatas = $(this).data('reservas-datas');
    const reservasValores = $(this).data('reservas-valores');

    if (!reservasIds || reservasIds.length === 0) {
        Swal.fire({
            title: 'Erro!',
            text: 'Nenhuma reserva encontrada para emissão dos recibos.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Confirmar com o usuário antes de prosseguir
    Swal.fire({
        title: 'Emitir Recibos',
        text: 'Você está prestes a emitir o(s) recibo(s)' + (reservasIds.length > 1 ? 's' : '') + '. Deseja continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, emitir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Abrir o PDF combinado de todos os recibos
            window.open('recibos.php?reservas_ids=' + encodeURIComponent(JSON.stringify(reservasIds)), '_blank');
        }
    });
});

// Adicionar manipulador para o botão "Ver Contrato Assinado"
$(document).on('click', '.ver-contrato-btn', function() {
    const reservaId = $(this).data('reserva-id');
    const reservasIds = $(this).data('reservas-ids');

    if (reservaId || (reservasIds && reservasIds.length > 0)) {
        // Se houver múltiplas reservas para o mesmo pagamento, passar todas elas
        if (reservasIds && reservasIds.length > 0) {
            // Redirecionar para a página de contrato com múltiplos IDs de reserva
            window.open('contrato.php?reservas_ids=' + encodeURIComponent(JSON.stringify(reservasIds)), '_blank');
        } else {
            // Redirecionar para a página de contrato com o ID da reserva específica
            window.open('contrato.php?reserva_id=' + encodeURIComponent(reservaId), '_blank');
        }
    }
});

// Adicionar manipulador para o botão "Ver Contrato(s)" para múltiplas reservas
$(document).on('click', '.ver-contrato-todos-btn', function() {
    const reservasIds = $(this).data('reservas-ids');

    if (reservasIds && reservasIds.length > 0) {
        // Redirecionar para a página de contrato com múltiplos IDs de reserva
        const reservasIdsParam = JSON.stringify(reservasIds);
        window.open('contrato.php?reservas_ids=' + encodeURIComponent(reservasIdsParam), '_blank');
    }
});

// Função para gerar o recibo
    


// Função para obter o mês por extenso
function getMesExtenso(mes) {
    const meses = [
        'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
    ];
    return meses[parseInt(mes) - 1];
}

// Função para converter número para extenso (simplificada)
function numeroPorExtenso(numero) {
    // Esta é uma implementação completa para converter números para extenso em português
    const unidades = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
    const dezenas = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
    const especiais = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
    const centenas = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
    
    function converterAteCem(numero) {
        if (numero < 10) {
            return unidades[numero];
        } else if (numero >= 10 && numero < 20) {
            return especiais[numero - 10];
        } else {
            const dezena = Math.floor(numero / 10);
            const unidade = numero % 10;
            if (unidade === 0) {
                return dezenas[dezena];
            } else {
                return dezenas[dezena] + (unidade > 0 ? ' e ' + unidades[unidade] : '');
            }
        }
    }
    
    function converterAteMil(numero) {
        if (numero === 0) return 'zero';
        if (numero === 100) return 'cem';
        if (numero < 100) {
            return converterAteCem(numero);
        } else {
            const centena = Math.floor(numero / 100);
            const resto = numero % 100;
            if (resto === 0) {
                return centenas[centena];
            } else {
                return centenas[centena] + ' e ' + converterAteCem(resto);
            }
        }
    }
    
    function converterNumeroExtenso(num) {
        if (num === 0) return 'zero';
        
        const reais = Math.floor(num);
        const centavos = Math.round((num - reais) * 100);
        
        let extensoReais = '';
        let extensoCentavos = '';
        
        // Converter reais
        if (reais === 0) {
            extensoReais = 'zero reais';
        } else if (reais === 1) {
            extensoReais = 'um real';
        } else if (reais < 1000) {
            extensoReais = converterAteMil(reais) + ' reais';
        } else if (reais < 1000000) {
            const milhar = Math.floor(reais / 1000);
            const resto = reais % 1000;
            
            let extensoMilhar = '';
            if (milhar === 1) {
                extensoMilhar = 'mil';
            } else {
                extensoMilhar = converterAteMil(milhar) + ' mil';
            }
            
            if (resto === 0) {
                extensoReais = extensoMilhar;
            } else if (resto < 100) {
                extensoReais = extensoMilhar + ' e ' + converterAteMil(resto);
            } else {
                extensoReais = extensoMilhar + ', ' + converterAteMil(resto);
            }
            
            extensoReais += ' reais';
        } else { // milhões
            const milhoes = Math.floor(reais / 1000000);
            const resto = reais % 1000000;
            
            let extensoMilhoes = '';
            if (milhoes === 1) {
                extensoMilhoes = 'um milhão';
            } else {
                extensoMilhoes = converterAteMil(milhoes) + ' milhões';
            }
            
            if (resto === 0) {
                extensoReais = extensoMilhoes + ' de reais';
            } else if (resto < 1000) {
                extensoReais = extensoMilhoes + ' e ' + converterAteMil(resto) + ' reais';
            } else {
                const milharResto = Math.floor(resto / 1000);
                const centenasResto = resto % 1000;
                
                if (centenasResto === 0) {
                    extensoReais = extensoMilhoes + ' ' + converterAteMil(milharResto) + ' mil reais';
                } else {
                    extensoReais = extensoMilhoes + ', ' + converterAteMil(milharResto) + ' mil e ' + converterAteMil(centenasResto) + ' reais';
                }
            }
        }
        
        // Converter centavos
        if (centavos > 0) {
            if (centavos === 1) {
                extensoCentavos = 'um centavo';
            } else {
                extensoCentavos = converterAteMil(centavos) + ' centavos';
            }
        }
        
        // Combinar reais e centavos
        if (reais > 0 && centavos > 0) {
            return extensoReais + ' e ' + extensoCentavos;
        } else if (reais > 0) {
            return extensoReais;
        } else if (centavos > 0) {
            return extensoCentavos;
        } else {
            return 'zero reais';
        }
    }
    
    return converterNumeroExtenso(numero);
}

// Função para escapar HTML para evitar problemas de segurança
function escapeHtml(text) {
    if (typeof text !== 'string') {
        return '';
    }
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<!-- Modal de Pagamento PIX -->
<div class="modal fade" id="pixModal" tabindex="-1" aria-labelledby="pixModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="pixModalLabel">
                    <i class="fas fa-qrcode me-2"></i>Pagamento por PIX
                </h5>
        	<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <h6 class="card-title text-primary fw-bold">Detalhes da Reserva</h6>
                                <div class="reservation-info">
                                    <p class="mb-2"><i class="fas fa-calendar-check me-2 text-success"></i><span id="pix-data" class="fw-medium">Carregando datas...</span></p>
                                    <p class="mb-2"><i class="fas fa-clock me-2 text-info"></i><span class="text-muted">Check-in:</span> <span class="fw-medium">09:00</span> | <span class="text-muted">Check-out:</span> <span class="fw-medium">08:00</span></p>
                                    <p class="mb-0"><i class="fas fa-money-bill-wave me-2 text-warning"></i><span class="text-muted">Valor:</span> <span id="pix-valor" class="fw-bold text-success fs-5"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <h6 class="card-title text-success fw-bold">Forma de Pagamento</h6>
                                <div class="payment-method">
                                    <div class="d-flex justify-content-center mb-3">
                                        <div class="bg-light rounded-circle p-2">
                                            <i class="fas fa-qrcode text-success" style="font-size: 2rem;"></i>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-0">PIX Copia e Cola</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="payment-status-container" class="mt-4">
                    <div class="text-center mb-4">
                        <p class="lead mb-3">Escaneie o QR Code abaixo para realizar o pagamento</p>
                        <div id="pix-qrcode-container" class="d-flex justify-content-center">
                            <div id="pix-qrcode" class="border rounded p-3 bg-light">
                                <!-- QR Code será inserido aqui -->
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-4 mt-4">
                        <h6 class="text-center mb-3">Ou utilize a chave PIX</h6>
                        <div class="input-group">
                            <input type="text" id="pix-key-input" value="Aguardando geração do código..." readonly class="form-control form-control-lg text-center">
                            <button id="copy-pix-key-btn" class="btn btn-outline-success" type="button">
                                <i class="fas fa-copy me-1"></i>Copiar
                            </button>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted">Clique no botão para copiar a chave PIX</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button id="cancel-reservation-btn" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button id="confirm-payment-btn" class="btn btn-success btn-lg px-4">
                    <i class="fas fa-check-circle me-2"></i>Já Paguei, Confirmar!
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
