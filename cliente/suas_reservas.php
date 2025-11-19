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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Suas Reservas - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="../assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        
        /* Forçar estilos Bootstrap em elementos jQuery Mobile */
        .ui-content .btn {
            display: inline-block !important;
            text-align: center !important;
            vertical-align: middle !important;
            touch-action: manipulation !important;
            cursor: pointer !important;
            user-select: none !important;
            background-image: none !important;
            border: 1px solid transparent !important;
        }
        
        .ui-content .btn:focus,
        .ui-content .btn:active:focus {
            outline: thin dotted !important;
            outline: 5px auto -webkit-focus-ring-color !important;
            outline-offset: -2px !important;
        }
        
        .ui-content .btn:hover,
        .ui-content .btn:focus {
            text-decoration: none !important;
        }
        
        .ui-content .btn:active {
            background-image: none !important;
            outline: 0 !important;
            box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125) !important;
        }
        
        .ui-content .btn.disabled,
        .ui-content .btn[disabled],
        fieldset[disabled] .ui-content .btn {
            cursor: not-allowed !important;
            filter: alpha(opacity=65) !important;
            opacity: 0.65 !important;
            box-shadow: none !important;
        }
        
        .ui-content .btn-success,
        .ui-content .btn-primary,
        .ui-content .btn-warning,
        .ui-content .btn-danger {
            color: white !important;
            background-color: #28a745 !important;
        }
        
        .ui-content .btn-primary {
            background-color: #007bff !important;
        }
        
        .ui-content .btn-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        
        .ui-content .btn-danger {
            background-color: #dc3545 !important;
        }
        
        /* Estilos adicionais com alta especificidade para forçar o texto branco */
        button.btn-success,
        a.btn-success,
        input.btn-success,
        button.btn-primary,
        a.btn-primary,
        input.btn-primary {
            color: white !important;
            text-shadow: none !important;
        }
        
        /* Regras para garantir que os estilos sejam aplicados a elementos criados dinamicamente */
        .ui-content .btn-success,
        .ui-content .btn-primary {
            color: white !important;
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
        

    </style>
</head>
<body>

<div data-role="page" id="suasReservasPageCliente">

    <div data-role="header" data-position="fixed">
        <h1 id="header-title">Suas Reservas</h1>
        <a href="#" id="logout-link-reservas" class="ui-btn-right ui-btn ui-corner-all">Sair</a>
    </div>

    <div role="main" class="ui-content">
        <div class="card">
            <h2>Suas Reservas</h2>
            <div id="reservas-list">
                <!-- As reservas serão carregadas aqui via AJAX -->
            </div>
        </div>
    </div>

    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-ajax="false" data-icon="home">Inicio</a></li>
                <li><a href="reserva.php" data-ajax="false" data-icon="grid">Reserve</a></li>
                <li><a href="suas_reservas.php" data-ajax="false" data-icon="calendar" class="ui-btn-active ui-state-persist">Reservas</a></li>
                <li><a href="perfil.php" data-ajax="false" data-icon="user">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/jquery.mobile-1.4.5.min.js"></script>
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
            
            // Exibir mensagem de confirmação se houve mudança recente de status
            if(response.recent_status_change === true) {
                // Exibir notificação de confirmação de pagamento
                Swal.fire({
                    title: '🎉 Ótima notícia!',
                    text: 'Seu pagamento foi confirmado e suas reservas estão marcadas como confirmadas. Aproveite seu momento especial na Chácara Recanto do Sossego!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
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
                
                let html = '<ul data-role="listview">';
                
                // Filtrar reservas confirmadas que ainda não foram assinadas
                let reservasConfirmadasNaoAssinadas = response.reservations.filter(reserva => reserva.status === 'confirmado' && !reserva.contrato_assinado);

                // Filtrar reservas confirmadas com pagamento confirmado para emitir recibos
                let reservasConfirmadasComPagamento = response.reservations.filter(reserva => reserva.status === 'confirmado' && reserva.payment_confirmed_at);

                // Separar as reservas que têm tanto pagamento confirmado quanto contrato não assinado
                let reservasConfirmadasComPagamentoENaoAssinadas = response.reservations.filter(reserva => 
                    reserva.status === 'confirmado' && 
                    reserva.payment_confirmed_at && 
                    !reserva.contrato_assinado
                );

                // Ordenar por data em ordem crescente (mais antiga primeiro) com tratamento de erro
                try {
                    reservasConfirmadasNaoAssinadas.sort((a, b) => {
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
                    console.error('Erro ao ordenar reservas não assinadas:', e);
                }

                // Agrupar todas as reservas confirmadas que ainda não têm contrato assinado (com ou sem pagamento confirmado)
                let reservasNaoAssinadas = response.reservations.filter(reserva =>
                    reserva.status === 'confirmado' && !reserva.contrato_assinado
                );

                // Ordenar por data em ordem crescente (mais antiga primeiro) com tratamento de erro
                try {
                    reservasNaoAssinadas.sort((a, b) => {
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
                    console.error('Erro ao ordenar reservas não assinadas:', e);
                }

                if (reservasNaoAssinadas.length >= 1) {
                    html += '<li class="payment-summary-item">';
                    html += '<h3>Contratos Não Assinados</h3>';
                    html += '<p><strong>Quantidade:</strong> ' + reservasNaoAssinadas.length + ' contrato' + (reservasNaoAssinadas.length > 1 ? 's' : '') + ' não assinado' + (reservasNaoAssinadas.length > 1 ? 's' : '') + '</p>';

                    // Adicionar detalhes das reservas
                    reservasNaoAssinadas.forEach(function(reserva) {
                        html += '<div style="margin: 5px 0; padding: 8px; background-color: #f8f9fa; border-radius: 4px; border-left: 3px solid #007bff;">';
                        html += '<p><strong>Reserva para:</strong> ' + reserva.data_formatada + '</p>';
                        html += '<p><strong>Status:</strong> ' + reserva.status_formatado + '</p>';
                        if(reserva.payment_confirmed_at) {
                            html += '<p><strong>Data de Confirmação:</strong> ' + new Date(reserva.payment_confirmed_at).toLocaleString('pt-BR') + '</p>';
                        }
                        html += '<p><strong>Valor:</strong> ' + reserva.valor_formatado + '</p>';
                        if(reserva.observacoes) {
                            html += '<p><strong>Observações:</strong> ' + escapeHtml(reserva.observacoes) + '</p>';
                        }
                        html += '</div>';
                    });

                    html += '<div class="button-container" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">';
                    // Botão de emissão de recibo para reservas com pagamento confirmado
                    let reservasComPagamento = reservasNaoAssinadas.filter(r => r.payment_confirmed_at);
                    if (reservasComPagamento.length >= 1) {
                        html += '<button class="btn btn-primary btn-lg emitir-todos-recibos-btn" style="color: white; flex: 1; min-width: 150px;" data-reservas-ids=\'' + JSON.stringify(reservasComPagamento.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(reservasComPagamento.map(r => r.data_formatada)) + '\' data-reservas-valores=\'' + JSON.stringify(reservasComPagamento.map(r => r.valor_formatado)) + '\'>' + (reservasComPagamento.length > 1 ? 'Emitir Recibo(s)' : 'Emitir Recibo') + '</button>';
                    }
                    html += '<button class="btn btn-info btn-lg assinar-todos-btn" style="color: white; flex: 1; min-width: 150px;" data-reservas-ids=\'' + JSON.stringify(reservasNaoAssinadas.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(reservasNaoAssinadas.map(r => r.data_formatada)) + '\'>' + (reservasNaoAssinadas.length > 1 ? 'Assinar Contrato(s)' : 'Assinar Contrato') + '</button>';
                    html += '</div>';
                    html += '</li>';
                    html += '<li class="divider"><hr></li>'; // Separador
                }

                // Agrupar todas as reservas confirmadas que já têm contrato assinado
                let reservasAssinadas = response.reservations.filter(reserva =>
                    reserva.status === 'confirmado' && reserva.contrato_assinado
                );

                // Ordenar por data em ordem crescente (mais antiga primeiro) com tratamento de erro
                try {
                    reservasAssinadas.sort((a, b) => {
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
                    console.error('Erro ao ordenar reservas assinadas:', e);
                }

                if (reservasAssinadas.length >= 1) {
                    html += '<li class="payment-summary-item">';
                    html += '<h3>Contratos Assinados</h3>';
                    html += '<p><strong>Quantidade:</strong> ' + reservasAssinadas.length + ' contrato' + (reservasAssinadas.length > 1 ? 's' : '') + ' assinado' + (reservasAssinadas.length > 1 ? 's' : '') + '</p>';

                    // Adicionar detalhes das reservas
                    reservasAssinadas.forEach(function(reserva) {
                        html += '<div style="margin: 5px 0; padding: 8px; background-color: #f8f9fa; border-radius: 4px; border-left: 3px solid #007bff;">';
                        html += '<p><strong>Reserva para:</strong> ' + reserva.data_formatada + '</p>';
                        html += '<p><strong>Status:</strong> ' + reserva.status_formatado + '</p>';
                        if(reserva.payment_confirmed_at) {
                            html += '<p><strong>Data de Confirmação:</strong> ' + new Date(reserva.payment_confirmed_at).toLocaleString('pt-BR') + '</p>';
                        }
                        html += '<p><strong>Valor:</strong> ' + reserva.valor_formatado + '</p>';
                        if(reserva.observacoes) {
                            html += '<p><strong>Observações:</strong> ' + escapeHtml(reserva.observacoes) + '</p>';
                        }
                        html += '</div>';
                    });

                    html += '<div class="button-container" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">';
                    html += '<button class="btn btn-primary btn-lg emitir-todos-recibos-btn" style="color: white; flex: 1; min-width: 150px;" data-reservas-ids=\'' + JSON.stringify(reservasAssinadas.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(reservasAssinadas.map(r => r.data_formatada)) + '\' data-reservas-valores=\'' + JSON.stringify(reservasAssinadas.map(r => r.valor_formatado)) + '\'>' + (reservasAssinadas.length > 1 ? 'Emitir Recibo(s)' : 'Emitir Recibo') + '</button>';
                    html += '<button class="btn btn-warning btn-lg ver-contrato-todos-btn" style="color: white; flex: 1; min-width: 150px;" data-reservas-ids=\'' + JSON.stringify(reservasAssinadas.map(r => r.id)) + '\'>' + (reservasAssinadas.length > 1 ? 'Ver Contrato(s)' : 'Ver Contrato') + '</button>';
                    html += '</div>';
                    html += '</li>';
                    html += '<li class="divider"><hr></li>'; // Separador
                }
                
                // Adicionar botão de pagamento único para todas as reservas pendentes
                if (reservasPendentes.length >= 1) {
                    html += '<li class="payment-summary-item">';
                    html += '<h3>Pagamento' + (reservasPendentes.length > 1 ? ' Total' : '') + '</h3>';
                    html += '<p><strong>Valor Total:</strong> R$ ' + totalPendente.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</p>';
                    html += '<p><strong>Quantidade:</strong> ' + reservasPendentes.length + ' reserva' + (reservasPendentes.length > 1 ? 's' : '') + ' pendente' + (reservasPendentes.length > 1 ? 's' : '') + '</p>';

                    // Adicionar detalhes das reservas
                    reservasPendentes.forEach(function(reserva) {
                        html += '<div style="margin: 5px 0; padding: 8px; background-color: #f8f9fa; border-radius: 4px; border-left: 3px solid #28a745;">';
                        html += '<p><strong>Reserva para:</strong> ' + reserva.data_formatada + '</p>';
                        html += '<p><strong>Status:</strong> ' + reserva.status_formatado + '</p>';
                        html += '<p><strong>Valor:</strong> ' + reserva.valor_formatado + '</p>';
                        if(reserva.observacoes) {
                            html += '<p><strong>Observações:</strong> ' + escapeHtml(reserva.observacoes) + '</p>';
                        }
                        html += '</div>';
                    });

                    html += '<div class="button-container" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">';
                    html += '<button class="btn btn-success btn-lg pagamento-unico-btn" style="color: white; flex: 1; min-width: 150px;" data-reservas-ids=\'' + JSON.stringify(reservasPendentes.map(r => r.id)) + '\' data-reservas-datas=\'' + JSON.stringify(reservasPendentes.map(r => r.data_formatada)) + '\' data-valor-total="' + totalPendente + '">' + (reservasPendentes.length > 1 ? 'Pagar Todas (' + reservasPendentes.length + ')' : 'Realizar Pagamento') + '</button>';
                    html += '</div>';
                    html += '</li>';
                    html += '<li class="divider"><hr></li>'; // Separador
                }

                // Filtrar reservas individuais que não fazem parte dos grupos anteriores
                // (não pendentes, não confirmadas como não assinadas, não confirmadas como assinadas)
                // As reservas individuais incluem aquelas que não estão nas seções agrupadas acima
                let todasReservasAgrupadasIds = new Set();

                // Adicionar IDs das reservas pendentes
                reservasPendentes.forEach(r => todasReservasAgrupadasIds.add(r.id));

                // Adicionar IDs das reservas não assinadas (agora agrupadas)
                reservasNaoAssinadas.forEach(r => todasReservasAgrupadasIds.add(r.id));

                // Adicionar IDs das reservas assinadas (agora agrupadas)
                reservasAssinadas.forEach(r => todasReservasAgrupadasIds.add(r.id));

                let reservasIndividuais = response.reservations.filter(reserva => {
                    return !todasReservasAgrupadasIds.has(reserva.id);
                });

                // Ordenar reservas individuais por data (mais antiga primeiro)
                try {
                    reservasIndividuais.sort((a, b) => {
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
                    console.error('Erro ao ordenar reservas individuais:', e);
                }

                // Exibir reservas individuais com botões lado a lado
                reservasIndividuais.forEach(function(reserva) {
                    html += '<li>';
                    html += '<h3>Reserva para ' + reserva.data_formatada + '</h3>';
                    html += '<p><strong>Status:</strong> <span class="status-badge status-' + reserva.status + '">' + reserva.status_formatado + '</span></p>';
                    if(reserva.payment_confirmed_at) {
                        html += '<p><strong>Data de Confirmação:</strong> ' + new Date(reserva.payment_confirmed_at).toLocaleString('pt-BR') + '</p>';
                    }
                    html += '<p><strong>Valor:</strong> ' + reserva.valor_formatado + '</p>';
                    if(reserva.observacoes) {
                        html += '<p><strong>Observações:</strong> ' + escapeHtml(reserva.observacoes) + '</p>';
                    }

                    html += '<div class="button-container" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">';

                    // Botão de pagamento individual (apenas para reservas pendentes)
                    if(reserva.status === 'pendente') {
                        html += '<button class="btn btn-success btn-lg realizar-pagamento-btn" style="color: white; flex: 1; min-width: 150px;" data-reserva-id="' + reserva.id + '" data-reserva-data="' + reserva.data_formatada + '" data-reserva-valor="' + reserva.valor_formatado + '">Realizar Pagamento</button>';
                    }

                    // Botão de emissão de recibo individual (para reservas confirmadas com pagamento)
                    if(reserva.status === 'confirmado' && reserva.payment_confirmed_at) {
                        html += '<button class="btn btn-primary btn-lg emitir-recibo-btn" style="color: white; flex: 1; min-width: 150px;" data-reserva-id="' + reserva.id + '">Emitir Recibo</button>';
                    }

                    // Botão de assinatura individual (para reservas confirmadas não assinadas)
                    if(reserva.status === 'confirmado' && !reserva.contrato_assinado) {
                        html += '<button class="btn btn-info btn-lg assinar-contrato-btn" style="color: white; flex: 1; min-width: 150px;" data-reserva-id="' + reserva.id + '">Assinar Contrato</button>';
                    }

                    // Botão para ver contratos assinados (para reservas confirmadas com contrato assinado)
                    // Este botão aparecerá ao lado do botão "Emitir Recibo" quando o contrato estiver assinado
                    if(reserva.status === 'confirmado' && reserva.contrato_assinado) {
                        html += '<button class="btn btn-info btn-lg ver-contrato-btn" style="color: white; flex: 1; min-width: 150px;" data-reserva-id="' + reserva.id + '">Ver Contrato Assinado</button>';
                    }

                    // Botão de exclusão (para reservas pendentes ou canceladas)
                    if(reserva.status === 'pendente' || reserva.status === 'cancelado') {
                        html += '<button class="btn btn-danger btn-lg excluir-reserva-btn" style="color: white; flex: 1; min-width: 150px;" data-reserva-id="' + reserva.id + '" data-reserva-data="' + reserva.data_formatada + '">Excluir Reserva</button>';
                    }

                    html += '</div>';
                    html += '</li>';
                });


                html += '</ul>';
                $('#reservas-list').html(html);
                // Atualizar o widget do jQuery Mobile
                $('#reservas-list').trigger('create');
            } else {
                $('#reservas-list').html('<p>Você ainda não possui reservas.</p>');
            }
        },
        error: function() {
            $('#reservas-list').html('<p>Erro ao carregar as reservas. Tente novamente mais tarde.</p>');
        }
    });
}

$(document).on('pageinit', '#suasReservasPageCliente', function() {
    // Carregar reservas do usuário quando a página for inicializada
    loadUserReservations();
});

// Carregar as reservas também quando a página for mostrada (útil para navegação do jQuery Mobile)
$(document).on('pageinit', '#suasReservasPageCliente', function() {
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
});

$(document).on('pageshow', '#suasReservasPageCliente', function() {
    // Carregar reservas do usuário novamente ao mostrar a página
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
    $('#pix-modal-overlay').data('reserva-id', reservaId);

    // Exibir o modal de pagamento PIX
    $('#pix-modal-overlay').fadeIn();
    console.log('Modal exibido, chamando gerarPixParaReserva');

    // Gerar QR Code PIX para esta reserva específica
    gerarPixParaReserva(reservaId, reservaData, reservaValor);
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
    $('#pix-modal-overlay').data('reservas-ids', reservasIds);

    // Exibir o modal de pagamento PIX
    $('#pix-modal-overlay').fadeIn();
    console.log('Modal exibido, chamando gerarPixParaVariasReservas');

    // Gerar QR Code PIX para todas as reservas pendentes
    gerarPixParaVariasReservas(reservasIds, reservasDatas, valorTotal);
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
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly>
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
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly>
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
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly>
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
                if ($('#pix-modal-overlay').is(':visible')) {
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
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly>
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
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly>
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
                            <input type="text" id="pix-key-input" value="${response.pix_key || 'f7d0d678-f14f-4dd9-8186-b26a64006c3f'}" readonly>
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
                if ($('#pix-modal-overlay').is(':visible')) {
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
    $('#pix-modal-overlay').fadeOut();
    $('#payment-status-indicator').hide();
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
    const copyText = document.getElementById('pix-key-input');
    if (copyText) {
        copyText.select();
        copyText.setSelectionRange(0, 99999); // Para dispositivos móveis
        document.execCommand('copy');
        
        // Mostrar mensagem de sucesso
        Swal.fire({
            title: 'Sucesso!',
            text: 'Chave PIX copiada para a área de transferência!',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
});

// Adicionar manipulador para o botão "Excluir Reserva"
$(document).on('click', '.excluir-reserva-btn', function() {
    const reservaId = $(this).data('reserva-id');
    const reservaData = $(this).data('reserva-data');
    
    // Confirmar com o usuário antes de excluir
    if (confirm('Tem certeza que deseja excluir a reserva para o dia ' + reservaData + '? Esta ação não poderá ser desfeita.')) {
        // Fazer a requisição AJAX para excluir a reserva
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

// Adicionar manipulador para o botão "Assinar Contrato Individual"
$(document).on('click', '.assinar-contrato-btn', function() {
    const reservaId = $(this).data('reserva-id');

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
                // Redirecionar para a página de contrato com o ID da reserva específica
                window.open('contrato.php?reserva_id=' + encodeURIComponent(reservaId), '_blank');
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
            text: 'Você está prestes a assinar todos os contratos de uma vez. Deseja continuar?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, assinar todos',
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
            console.error('Erro na requisição AJAX:', error);
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
        text: 'Você está prestes a emitir ' + reservasIds.length + ' recibo' + (reservasIds.length > 1 ? 's' : '') + '. Deseja continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, emitir recibos',
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

    if (reservaId) {
        // Redirecionar para a página de contrato com o ID da reserva específica
        window.open('contrato.php?reserva_id=' + encodeURIComponent(reservaId), '_blank');
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
<div id="pix-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box;">
    <div id="pix-modal-content" class="card" style="background-color: #34495e; color: white; padding: 25px; max-width: 400px; width: 100%; position: relative;">
        <button id="close-pix-modal" class="ui-btn ui-icon-delete ui-btn-icon-notext" style="position: absolute; top: 5px; right: 5px; background-color: transparent !important; border: none !important; box-shadow: none !important;">Fechar</button>
        <div class="pix-details">
            <p><strong>Data:</strong> <span id="pix-data"></span></p>
            <p><strong>Check-in:</strong> 09:00 | <strong>Check-out:</strong> 08:00</p>
            <p><strong>Valor:</strong> <span id="pix-valor"></span></p>
        </div>
        <div id="payment-status-container">
            <p class="pix-instructions">Pague com o QR Code abaixo:</p>
            <div id="pix-qrcode"></div>
            <div style="margin-top: 15px;">
                <p style="font-size: 0.8em; color: #7f8c8d; margin-bottom: 5px;">Ou copie a chave PIX:</p>
                <div class="pix-copy-paste">
                    <input type="text" id="pix-key-input" value="f7d0d678-f14f-4dd9-8186-b26a64006c3f" readonly>
                    <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            <button id="close-pix-modal" class="ui-btn">Fechar</button>
        </div>
    </div>
</div>

</body>
</html>
