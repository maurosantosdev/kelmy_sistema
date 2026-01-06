<?php
// Sessão é iniciada but authentication is not required to view the reservation calendar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?> 

<!DOCTYPE html>
<html>
<head>
    <title>Reserve sua Data - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link rel="stylesheet" href="../assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .selected-date-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
        }
        .date-info {
            text-align: left;
        }
        .remove-date-btn {
            margin-left: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 2px 6px;
            cursor: pointer;
            font-size: 0.8em;
        }
        /* Estilos para o calendário - ajustar datas e valores */
        #reservaCalendar .calendar-day {
            padding: 1px !important;
            font-size: 0.55em !important;
        }
        #reservaCalendar .day-number {
            font-size: 8px !important;
            order: 1;
        }
        #reservaCalendar .price {
            font-size: 8px !important;
            color: var(--price-color) !important;
            margin-top: 1px !important;
            font-weight: bold !important;
            order: 2 !important;
            white-space: nowrap !important;
        }
        
        /* Estilos para o card de Detalhes da Seleção - alinhamento à esquerda e tamanho de texto padronizado */
        #reserva-details {
            text-align: left !important;
            font-size: 10px !important;
        }
        #reserva-details h2 {
            text-align: left !important;
            margin-left: 0 !important;
            font-size: 10px !important;
        }
        #reserva-details p {
            text-align: left !important;
            margin-left: 0 !important;
            font-size: 10px !important;
        }
        #reserva-details label {
            display: block !important;
            text-align: left !important;
            margin-left: 0 !important;
            font-size: 10px !important;
        }
        #selected-dates-container {
            text-align: left !important;
            margin-left: 0 !important;
            font-size: 10px !important;
        }
        #selected-dates-list {
            text-align: left !important;
            margin-left: 0 !important;
            padding-left: 15px !important;
            font-size: 10px !important;
        }
        #selected-dates-list li {
            font-size: 10px !important;
        }
        #selected-total-price-text {
            font-size: 10px !important;
        }
        /* Espaçamento entre o botão radio e o texto (20px como solicitado) */
        #payment_50, #payment_100 {
            margin-right: 20px !important;
        }
    </style>
</head>
<body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div data-role="page" id="reservaPageCliente" data-dom-cache="false">

    <div data-role="header" data-position="fixed">
        <?php if (isset($_SESSION['user_name'])): ?>
        <h1>Olá <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        <a href="suas_reservas.php" class="ui-btn-right ui-btn ui-corner-all">Minhas Reservas</a>
        <?php else: ?>
        <h1>Reserve sua Data</h1>
        <a href="#" onclick="showLoginModal(); return false;" id="login-link-reserva" class="ui-btn-right ui-btn ui-corner-all">Entrar</a>
        <?php endif; ?>
    </div>

    <div role="main" class="ui-content">

        <div class="card">
            <div id="reservaCalendarControls">
                <button id="reservaPrevMonth" class="ui-btn ui-icon-arrow-l ui-btn-icon-notext ui-corner-all">Anterior</button>
                <h2 id="reservaCurrentMonthYear"></h2>
                <button id="reservaNextMonth" class="ui-btn ui-icon-arrow-r ui-btn-icon-notext ui-corner-all">Próximo</button>
            </div>
            
            <div id="reservaCalendar"></div>

            <div class="calendar-legend">
                <div><span class="dot available"></span> Disponível</div>
                <div><span class="dot reserved"></span> Reservado</div>
            </div>
        </div>

        <div class="card" id="reserva-details" style="display:none;">
            <h2>Detalhes da Seleção</h2>
            <div id="selected-dates-container">
                <p>Datas selecionadas:</p>
                <ul id="selected-dates-list"></ul>
            </div>
            <p>Check-in: <strong>09:00</strong> | Check-out: <strong>08:00</strong></p>
            <p>Valor total das diárias: <strong id="selected-total-price-text"></strong></p>
            <p>Forma de pagamento:</p>
            <label>
                <input type="radio" name="payment_type" value="50" id="payment_50" checked> 50% (Entrada)
            </label>
            <label>
                <input type="radio" name="payment_type" value="100" id="payment_100"> 100% (Total)
            </label>
            
            <form id="reservaForm">
                <input type="hidden" name="selected_dates" id="selected_dates_input">
                <input type="hidden" name="total_price" id="total_price_input">
                <input type="hidden" name="payment_percentage" id="payment_percentage_input" value="50">
                <label for="observacoes">Observações (opcional):</label>
                <textarea name="observacoes" id="observacoes"></textarea>
                <button type="button" id="confirmReservaBtn" class="ui-btn ui-btn-b">Confirmar Reserva</button>
            </form>
        </div>
    </div>

    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-ajax="false" data-icon="home">Inicio</a></li>
                <li><a href="reserva.php" data-ajax="false" data-icon="grid" class="ui-btn-active ui-state-persist">Reserve</a></li>
                <li><a href="suas_reservas.php" data-ajax="false" data-icon="calendar">Minhas Reservas</a></li>
                <li><a href="perfil.php" data-ajax="false" data-icon="user">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
// Definindo a função global diretamente no HTML para garantir que esteja disponível
function handleReservaClick() {
    // Fazer uma verificação em tempo real para garantir que o status de login esteja atualizado
    $.ajax({
        url: '../php/cliente_login.php',
        type: 'POST',
        data: {
            check_session: 1
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.message && response.message.includes('Usuário já está logado')) {
                // O usuário está logado, proceder diretamente para a confirmação da reserva
                confirmReserva();
            } else {
                // O usuário não está logado, mostrar o modal de login
                showLoginModal();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao verificar sessão:', error, status, xhr.responseText);
            // Em caso de erro, exibir o modal de login como fallback
            showLoginModal();
        }
    });
}

// Função para mostrar o modal de login
function showLoginModal() {
    // Verificar se o usuário já está logado antes de mostrar o modal
    $.ajax({
        url: '../php/cliente_login.php',
        type: 'POST',
        data: {
            check_session: 1  // Campo especial para verificar a sessão
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.message && response.message.includes('Usuário já está logado')) {
                // Usuário já está logado, mostrar mensagem e continuar na mesma página
                Swal.fire({
                    title: 'Informação',
                    text: response.message,
                    icon: 'info',
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#3498db'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Atualizar a página para refletir o estado de autenticação e continuar na página de reserva
                        window.location.href = 'reserva.php';
                    }
                });
            } else {
                // Mostrar o modal de login normalmente
                const loginModal = document.getElementById('login-modal-overlay');
                if (loginModal) {
                    loginModal.style.display = 'flex';

                    // Reset form containers
                    document.getElementById('login-form-container').style.display = 'block';
                    document.getElementById('register-form-container').style.display = 'none';

                    // Limpar formulários
                    document.getElementById('loginModalForm').reset();
                    document.getElementById('registerModalForm').reset();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na verificação de sessão:', error, status, xhr.responseText);
            // Em caso de erro na verificação, mostrar o modal normalmente
            const loginModal = document.getElementById('login-modal-overlay');
            if (loginModal) {
                loginModal.style.display = 'flex';

                // Reset form containers
                document.getElementById('login-form-container').style.display = 'block';
                document.getElementById('register-form-container').style.display = 'none';

                // Limpar formulários
                document.getElementById('loginModalForm').reset();
                document.getElementById('registerModalForm').reset();
            }
        }
    });
}

// Função para confirmar a reserva
function confirmReserva() {
    // Pegando os valores necessários
    const selectedDates = document.getElementById('selected_dates_input').value;
    const totalPrice = document.getElementById('total_price_input').value;
    const paymentPercentage = document.getElementById('payment_percentage_input').value;
    const observacoes = document.getElementById('observacoes').value || '';

    // Verificar se os dados estão sendo obtidos corretamente
    if (!selectedDates || !totalPrice) {
        Swal.fire({
            title: 'Atenção!',
            text: "Por favor, selecione pelo menos uma data disponível antes de confirmar a reserva.",
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#34495e'
        });
        return;
    }

    // Calcular o valor a pagar com base na porcentagem
    const totalValue = parseFloat(totalPrice);
    const paymentValue = (totalValue * parseInt(paymentPercentage)) / 100;

    // Atualizar elementos de exibição
    const pixDataElement = document.getElementById('pix-data');
    if (pixDataElement) pixDataElement.textContent = "Datas selecionadas";
    const pixValorElement = document.getElementById('pix-valor');
    if (pixValorElement) pixValorElement.textContent = paymentValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    // Atualizar o botão de confirmação para mostrar que está processando
    const confirmBtn = document.getElementById('confirm-payment-btn');
    if (confirmBtn) {
        confirmBtn.textContent = 'Gerando cobrança...';
        confirmBtn.disabled = true;
    }

    // Criar cobrança no Mercado Pago usando fetch
    fetch('../php/create_mp_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dates=' + encodeURIComponent(selectedDates) +
              '&observacoes=' + encodeURIComponent(observacoes) +
              '&total_valor=' + encodeURIComponent(totalValue) +
              '&payment_percentage=' + encodeURIComponent(paymentPercentage)
    })
    .then(async response => {
        // Verificar se a resposta é ok antes de tentar converter para JSON
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText} - ${errorText}`);
        }
        // Verificar se a resposta é JSON antes de fazer o parse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            throw new Error(`Resposta não é JSON: ${responseText}`);
        }
        return response.json();
    })
    .then(response => {
        if(response.success) {
            // Exibir o modal de pagamento PIX
            const modal = document.getElementById('pix-modal-overlay');
            if (modal) modal.style.display = 'flex';

            // Atualizar o modal com o QR Code do Mercado Pago (usando código JavaScript vanilla)
            if (response.qr_code_base64) {
                const container = document.getElementById('payment-status-container');
                if (container) {
                    // Get selected dates from the form to display in the modal
                    const selectedDatesInput = document.getElementById('selected_dates_input');
                    let selectedDates = selectedDatesInput ? selectedDatesInput.value : 'Carregando...';

                    // Parse the JSON array of dates and format them as dd/mm/yyyy
                    let formattedDates = 'Carregando...';
                    if (selectedDates && selectedDates !== 'Carregando...') {
                        try {
                            const dateArray = JSON.parse(selectedDates);
                            if (Array.isArray(dateArray)) {
                                formattedDates = dateArray.map(dateStr => {
                                    // Parse the date string (format: YYYY-MM-DD) and convert to dd/mm/yyyy
                                    const date = new Date(dateStr);
                                    const day = String(date.getDate()).padStart(2, '0');
                                    const month = String(date.getMonth() + 1).padStart(2, '0');
                                    const year = date.getFullYear();
                                    return `${day}/${month}/${year}`;
                                }).join(', ');
                            } else {
                                // If it's not an array, try to format as single date
                                const date = new Date(selectedDates);
                                const day = String(date.getDate()).padStart(2, '0');
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const year = date.getFullYear();
                                formattedDates = `${day}/${month}/${year}`;
                            }
                        } catch (e) {
                            // If parsing fails, use original value
                            formattedDates = selectedDates.replace(/[\[\]"]/g, '').replace(/,/g, ', ');
                        }
                    }

                    container.innerHTML = `
                        <p style="color: #ecf0f1; margin: 6px 0; font-size: 10px;"><span style="color: #bdc3c7;">Datas:</span> <span style="color: #ffffff;">` + formattedDates + `</span></p>
                        <p style="color: #ecf0f1; margin: 6px 0; font-size: 10px;"><span style="color: #bdc3c7;">Check-in:</span> <span style="color: #ffffff;">09:00</span> | <span style="color: #bdc3c7;">Check-out:</span> <span style="color: #ffffff;">08:00</span></p>
                        <div id="pix-qrcode-container" style="display:flex; justify-content:center; align-items:center; margin: 15px 0; min-height: 200px;">
                            <img id="pix-qr-image" src="data:image/png;base64,` + response.qr_code_base64 + `" alt="QR Code PIX" style="width: 180px; height: 180px; padding: 10px; background-color: white; border-radius: 8px; display: block;">
                        </div>
                        <div style="margin-top: 15px; border-top: 1px solid #7f8c8d; padding-top: 15px;">
                            <p style="font-size: 10px; margin-bottom: 8px; color: #bdc3c7;">Ou copie a chave PIX:</p>
                            <div class="pix-copy-paste">
                                <input type="text" id="pix-key-input" value="` + (response.pix_key || response.qr_code) + `" readonly style="color: #2c3e50; background-color: #ffffff; padding: 5px; border-radius: 4px; width: 75%; font-size: 10px; overflow: auto; white-space: pre-wrap; text-overflow: clip; user-select: text; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text;" ontouchstart="this.scrollLeft=0" ontouchmove="this.scrollLeft=0" onscroll="this.scrollLeft=0" onclick="this.select();" onfocus="this.select();">
                                <button id="copy-pix-key-btn" style="margin-left: 5px; padding: 5px 8px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 10px;"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <button id="confirm-payment-btn" class="ui-btn" style="background-color: #27ae60; color: white; margin-top: 10px; font-size: 10px; padding: 8px;">Já paguei, verificar!</button>
                        <div id="payment-status-indicator" style="text-align: center; margin-top: 10px; display: none;">
                            <p style="color: #ecf0f1; font-size: 10px;">Verificando status do pagamento...</p>
                        </div>
                    `;
                }
            } else if (response.payment_url) {
                const container = document.getElementById('payment-status-container');
                if (container) {
                    // Get selected dates from the form to display in the modal
                    const selectedDatesInput = document.getElementById('selected_dates_input');
                    let selectedDates = selectedDatesInput ? selectedDatesInput.value : 'Carregando...';

                    // Parse the JSON array of dates and format them as dd/mm/yyyy
                    let formattedDates = 'Carregando...';
                    if (selectedDates && selectedDates !== 'Carregando...') {
                        try {
                            const dateArray = JSON.parse(selectedDates);
                            if (Array.isArray(dateArray)) {
                                formattedDates = dateArray.map(dateStr => {
                                    // Parse the date string (format: YYYY-MM-DD) and convert to dd/mm/yyyy
                                    const date = new Date(dateStr);
                                    const day = String(date.getDate()).padStart(2, '0');
                                    const month = String(date.getMonth() + 1).padStart(2, '0');
                                    const year = date.getFullYear();
                                    return `${day}/${month}/${year}`;
                                }).join(', ');
                            } else {
                                // If it's not an array, try to format as single date
                                const date = new Date(selectedDates);
                                const day = String(date.getDate()).padStart(2, '0');
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const year = date.getFullYear();
                                formattedDates = `${day}/${month}/${year}`;
                            }
                        } catch (e) {
                            // If parsing fails, use original value
                            formattedDates = selectedDates.replace(/[\[\]"]/g, '').replace(/,/g, ', ');
                        }
                    }

                    container.innerHTML = `
                        <p style="color: #ecf0f1; margin: 6px 0; font-size: 10px;"><span style="color: #bdc3c7;">Datas:</span> <span style="color: #ffffff;">` + formattedDates + `</span></p>
                        <p style="color: #ecf0f1; margin: 6px 0; font-size: 10px;"><span style="color: #bdc3c7;">Check-in:</span> <span style="color: #ffffff;">09:00</span> | <span style="color: #bdc3c7;">Check-out:</span> <span style="color: #ffffff;">08:00</span></p>
                        <div style="margin-top: 15px; border-top: 1px solid #7f8c8d; padding-top: 15px;">
                            <p style="font-size: 10px; margin-bottom: 8px; color: #bdc3c7;">Ou copie a chave PIX:</p>
                            <div class="pix-copy-paste">
                                <input type="text" id="pix-key-input" value="` + (response.pix_key || response.qr_code) + `" readonly style="color: #2c3e50; background-color: #ffffff; padding: 5px; border-radius: 4px; width: 75%; font-size: 10px; overflow: auto; white-space: pre-wrap; text-overflow: clip; user-select: text; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text;" ontouchstart="this.scrollLeft=0" ontouchmove="this.scrollLeft=0" onscroll="this.scrollLeft=0" onclick="this.select();" onfocus="this.select();">
                                <button id="copy-pix-key-btn" style="margin-left: 5px; padding: 5px 8px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 10px;"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <button id="confirm-payment-btn" class="ui-btn" style="background-color: #27ae60; color: white; margin-top: 10px; font-size: 10px; padding: 8px;">Já paguei, verificar!</button>
                        <div id="payment-status-indicator" style="text-align: center; margin-top: 10px; display: none;">
                            <p style="color: #ecf0f1; font-size: 10px;">Verificando status do pagamento...</p>
                        </div>
                    `;
                }
            } else {
                const container = document.getElementById('payment-status-container');
                // Get selected dates from the form to display in the modal
                const selectedDatesInput = document.getElementById('selected_dates_input');
                let selectedDates = selectedDatesInput ? selectedDatesInput.value : 'Carregando...';

                // Parse the JSON array of dates and format them as dd/mm/yyyy
                let formattedDates = 'Carregando...';
                if (selectedDates && selectedDates !== 'Carregando...') {
                    try {
                        const dateArray = JSON.parse(selectedDates);
                        if (Array.isArray(dateArray)) {
                            formattedDates = dateArray.map(dateStr => {
                                // Parse the date string (format: YYYY-MM-DD) and convert to dd/mm/yyyy
                                const date = new Date(dateStr);
                                const day = String(date.getDate()).padStart(2, '0');
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const year = date.getFullYear();
                                return `${day}/${month}/${year}`;
                            }).join(', ');
                        } else {
                            // If it's not an array, try to format as single date
                            const date = new Date(selectedDates);
                            const day = String(date.getDate()).padStart(2, '0');
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const year = date.getFullYear();
                            formattedDates = `${day}/${month}/${year}`;
                        }
                    } catch (e) {
                        // If parsing fails, use original value
                        formattedDates = selectedDates.replace(/[\[\]"]/g, '').replace(/,/g, ', ');
                    }
                }

                if (container) {
                    container.innerHTML = `
                        <p style="color: #ecf0f1; margin: 6px 0; font-size: 10px;"><span style="color: #bdc3c7;">Datas:</span> <span style="color: #ffffff;">` + formattedDates + `</span></p>
                        <p style="color: #ecf0f1; margin: 6px 0; font-size: 10px;"><span style="color: #bdc3c7;">Check-in:</span> <span style="color: #ffffff;">09:00</span> | <span style="color: #bdc3c7;">Check-out:</span> <span style="color: #ffffff;">08:00</span></p>
                        <p style="margin-bottom: 12px; color: #ecf0f1; font-size: 10px;">Erro: Não foi possível gerar o código PIX. Tente novamente mais tarde ou entre em contato com o suporte.</p>
                        <div style="margin-top: 15px; border-top: 1px solid #7f8c8d; padding-top: 15px;">
                            <p style="font-size: 10px; margin-bottom: 8px; color: #bdc3c7;">Ou copie a chave PIX:</p>
                            <div class="pix-copy-paste">
                                <input type="text" id="pix-key-input" value="` + (response.pix_key || response.qr_code) + `" readonly style="color: #2c3e50; background-color: #ffffff; padding: 5px; border-radius: 4px; width: 75%; font-size: 10px; overflow: auto; white-space: pre-wrap; text-overflow: clip; user-select: text; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text;" ontouchstart="this.scrollLeft=0" ontouchmove="this.scrollLeft=0" onscroll="this.scrollLeft=0" onclick="this.select();" onfocus="this.select();">
                                <button id="copy-pix-key-btn" style="margin-left: 5px; padding: 5px 8px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 10px;"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <button id="close-pix-modal" class="ui-btn" style="background-color: #e74c3c; color: white; margin-top: 10px; font-size: 10px; padding: 8px;">Fechar</button>
                    `;
                }
            }
            // Iniciar verificação automática do status do pagamento após exibir o QR Code
            // Aguardar 3 segundos antes de começar a verificar automaticamente
            setTimeout(() => {
                // Verificar se o modal ainda está aberto antes de iniciar a verificação
                if ($('#pix-modal-overlay').is(':visible')) {
                    const selectedDates = document.getElementById('selected_dates_input').value;

                    // Mostrar mensagem que a verificação automática começou
                    $('#payment-status-indicator').html('<p style="color: #ecf0f1; font-size: 10px;">Verificação automática iniciada...</p>').show();

                    // Atualizar o texto do botão para indicar que não é necessário clicar
                    $('#confirm-payment-btn').text('Aguardando confirmação...').prop('disabled', true);

                    // Resetar contador de tentativas antes de iniciar nova verificação
                    window.verificationAttempts = 0;

                    // Definir um tempo máximo global (1 minuto) para garantir redirecionamento
                    setTimeout(() => {
                        if ($('#pix-modal-overlay').is(':visible')) {
                            console.log('Tempo máximo global atingido, forçando redirecionamento...');
                            $('#payment-status-indicator').html('<p style="color: #ecf0f1; font-size: 10px;">Tempo limite atingido. Redirecionando...</p>');

                            // Fechar o modal antes de redirecionar
                            $('#pix-modal-overlay').hide();
                            // Como fallback adicional, também tentar fechar com CSS
                            const modalElement = document.getElementById('pix-modal-overlay');
                            if (modalElement) {
                                modalElement.style.display = 'none';
                            }

                            // Redirecionar para suas_reservas.php após atingir o tempo limite
                            window.location.replace('suas_reservas.php');
                        }
                    }, 60000); // 1 minuto = 60000 ms (reduzido para melhor experiência do usuário)

                    // Iniciar a verificação de status do pagamento
                    if (selectedDates) {
                        checkPaymentStatus(selectedDates);
                    } else {
                        console.error('Nenhuma data selecionada para verificar pagamento');
                        // Fechar o modal e redirecionar em caso de erro
                        $('#pix-modal-overlay').hide();
                        const modalElement = document.getElementById('pix-modal-overlay');
                        if (modalElement) {
                            modalElement.style.display = 'none';
                        }
                        window.location.replace('suas_reservas.php');
                    }
                }
            }, 3000); // 3 segundos para iniciar a verificação automática
        } else {
            Swal.fire({
                title: 'Erro!',
                text: "Erro ao criar cobrança: " + response.message,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#e74c3c'
            });
            const confirmBtn = document.getElementById('confirm-payment-btn');
            if (confirmBtn) {
                confirmBtn.textContent = 'Já Paguei, Agendar!';
                confirmBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Erro na criação da cobrança:', error);
        // Verificar se é um erro de parsing JSON e exibir mensagem mais específica
        if (error instanceof SyntaxError) {
            console.error("Erro de comunicação com o servidor: formato de resposta inválido.", error);
            // Em caso de erro de comunicação, simplesmente logamos no console e não exibimos alerta
            // para evitar confusão do usuário caso a requisição tenha sido bem-sucedida apesar do erro
        } else {
            console.error("Erro na comunicação com o servidor: ", error.message);
            // Em caso de erro de comunicação, simplesmente logamos no console e não exibimos alerta
            // para evitar confusão do usuário caso a requisição tenha sido bem-sucedida apesar do erro
        }
        const confirmBtn = document.getElementById('confirm-payment-btn');
        if (confirmBtn) {
            confirmBtn.textContent = 'Já Paguei, Agendar!';
            confirmBtn.disabled = false;
        }
    });
}

// Função para fazer login no modal
function loginModal() {
    const email = document.getElementById('email_login_modal').value;
    const senha = document.getElementById('senha_login_modal').value;
    
    if (!email || !senha) {
        Swal.fire({
            title: 'Atenção!',
            text: 'Por favor, preencha todos os campos.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#34495e'
        });
        return;
    }
    
    // Usando jQuery AJAX em vez de fetch para melhor tratamento de erros
    $.ajax({
        url: '../php/cliente_login.php',
        type: 'POST',
        data: {
            email: email,
            senha: senha
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Login realizado com sucesso!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#27ae60'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Salvar as datas selecionadas no localStorage antes de fechar o modal
                        const selectedDatesInput = document.getElementById('selected_dates_input');
                        const totalPriceInput = document.getElementById('total_price_input');
                        const paymentPercentageInput = document.getElementById('payment_percentage_input');
                        const observacoesInput = document.getElementById('observacoes');

                        if (selectedDatesInput && selectedDatesInput.value) {
                            localStorage.setItem('selectedDates', selectedDatesInput.value);
                            localStorage.setItem('totalPrice', totalPriceInput ? totalPriceInput.value : '');
                            localStorage.setItem('paymentPercentage', paymentPercentageInput ? paymentPercentageInput.value : '50');
                            localStorage.setItem('observacoes', observacoesInput ? observacoesInput.value : '');
                        }

                        // Fechar o modal de login usando jQuery para consistência com o resto do código
                        $('#login-modal-overlay').hide();
                        // Atualizar a página para refletir o estado de autenticação e permanecer na página de reserva
                        window.location.href = 'reserva.php';
                    }
                });
            } else {
                // Verificar se a mensagem de erro é "Usuário já está logado"
                if (response.message && response.message.includes('Usuário já está logado')) {
                    Swal.fire({
                        title: 'Informação',
                        text: response.message,
                        icon: 'info',
                        confirmButtonText: 'Continuar',
                        confirmButtonColor: '#3498db'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Fechar o modal de login usando jQuery
                            $('#login-modal-overlay').hide();
                            // Atualizar a página para refletir o estado de autenticação e permanecer na página de reserva
                            window.location.href = 'reserva.php';
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na comunicação com o servidor:', error, status, xhr.responseText);
            // Verificar se a resposta parece ser HTML em vez de JSON
            if (xhr.responseText && xhr.responseText.trim().startsWith('<!DOCTYPE')) {
                // A resposta parece ser HTML, provavelmente uma página de erro
                Swal.fire({
                    title: 'Erro de comunicação',
                    text: 'Ocorreu um erro na comunicação com o servidor. Por favor, atualize a página e tente novamente.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            } else {
                // Tentar fazer parse da resposta como JSON
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        Swal.fire({
                            title: 'Erro!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#e74c3c'
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro de comunicação',
                            text: 'Não foi possível processar a resposta do servidor.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } catch(e) {
                    // Se não for JSON válido, mostrar mensagem genérica
                    Swal.fire({
                        title: 'Erro de comunicação',
                        text: 'Ocorreu um erro na comunicação com o servidor.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            }
        }
    });
}


// Função para registrar no modal
function registerModal() {
    const nome = document.getElementById('nome_registro_modal').value;
    const email = document.getElementById('email_registro_modal').value;
    const telefone = document.getElementById('telefone_registro_modal').value;
    const senha = document.getElementById('senha_registro_modal').value;
    const confirmarSenha = document.getElementById('confirmar_senha_registro_modal').value;
    
    if (!nome || !email || !telefone || !senha || !confirmarSenha) {
        Swal.fire({
            title: 'Atenção!',
            text: 'Por favor, preencha todos os campos.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#34495e'
        });
        return;
    }
    
    if (senha !== confirmarSenha) {
        Swal.fire({
            title: 'Atenção!',
            text: 'As senhas não coincidem.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#34495e'
        });
        return;
    }
    
    // Usando jQuery AJAX em vez de fetch para melhor tratamento de erros
    $.ajax({
        url: '../php/cliente_cadastro.php',
        type: 'POST',
        data: {
            nome: nome,
            email: email,
            telefone: telefone,
            senha: senha
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Cadastro realizado com sucesso!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#27ae60'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Salvar as datas selecionadas no localStorage antes de fechar o modal
                        const selectedDatesInput = document.getElementById('selected_dates_input');
                        const totalPriceInput = document.getElementById('total_price_input');
                        const paymentPercentageInput = document.getElementById('payment_percentage_input');
                        const observacoesInput = document.getElementById('observacoes');

                        if (selectedDatesInput && selectedDatesInput.value) {
                            localStorage.setItem('selectedDates', selectedDatesInput.value);
                            localStorage.setItem('totalPrice', totalPriceInput ? totalPriceInput.value : '');
                            localStorage.setItem('paymentPercentage', paymentPercentageInput ? paymentPercentageInput.value : '50');
                            localStorage.setItem('observacoes', observacoesInput ? observacoesInput.value : '');
                        }

                        // Fechar o modal de cadastro usando jQuery para consistência com o resto do código
                        $('#login-modal-overlay').hide();
                        // Atualizar a página para refletir o estado de autenticação e permanecer na página de reserva
                        window.location.href = 'reserva.php';
                    }
                });
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: response.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#e74c3c'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na comunicação com o servidor durante o registro:', error, status, xhr.responseText);
            // Verificar se a resposta parece ser HTML em vez de JSON
            if (xhr.responseText && xhr.responseText.trim().startsWith('<!DOCTYPE')) {
                // A resposta parece ser HTML, provavelmente uma página de erro
                Swal.fire({
                    title: 'Erro de comunicação',
                    text: 'Ocorreu um erro na comunicação com o servidor. Por favor, atualize a página e tente novamente.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            } else {
                // Tentar fazer parse da resposta como JSON
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        Swal.fire({
                            title: 'Erro!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#e74c3c'
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro de comunicação',
                            text: 'Não foi possível processar a resposta do servidor.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } catch(e) {
                    // Se não for JSON válido, mostrar mensagem genérica
                    Swal.fire({
                        title: 'Erro de comunicação',
                        text: 'Ocorreu um erro na comunicação com o servidor.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                }
            }
        }
    });
}

// Função para verificar se o usuário está logado ao carregar a página
function checkLoginStatusOnLoad() {
    // Verificar se o usuário já está logado antes de mostrar o modal
    $.ajax({
        url: '../php/cliente_login.php',
        type: 'POST',
        data: {
            check_session: 1
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.message && response.message.includes('Usuário já está logado')) {
                // Atualizar o header para mostrar o nome do usuário e o botão apropriado
                // Esta lógica já está sendo tratada no PHP, mas adicionamos esta verificação adicional para atualizar o frontend
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao verificar status de login:', error, status, xhr.responseText);
        }
    });
}

// Função para restaurar as datas selecionadas do localStorage
function restoreSelectedDates() {
    const selectedDates = localStorage.getItem('selectedDates');
    const totalPrice = localStorage.getItem('totalPrice');
    const paymentPercentage = localStorage.getItem('paymentPercentage') || '50';
    const observacoes = localStorage.getItem('observacoes') || '';

    if (selectedDates) {
        try {
            const datesArray = JSON.parse(selectedDates);
            if (Array.isArray(datesArray) && datesArray.length > 0) {
                // Processar as datas para selecioná-las no calendário
                datesArray.forEach(date => {
                    const dayElement = $(`.calendar-day[data-date="${date}"]`);
                    if (dayElement.length > 0) {
                        dayElement.addClass('selected');
                    }
                });

                // Restaurar os valores nos campos de formulário
                const selectedDatesInput = document.getElementById('selected_dates_input');
                const totalPriceInput = document.getElementById('total_price_input');
                const paymentPercentageInput = document.getElementById('payment_percentage_input');
                const observacoesInput = document.getElementById('observacoes');

                if (selectedDatesInput) selectedDatesInput.value = selectedDates;
                if (totalPriceInput) totalPriceInput.value = totalPrice;
                if (paymentPercentageInput) paymentPercentageInput.value = paymentPercentage;
                if (observacoesInput) observacoesInput.value = observacoes;

                // Atualizar o display das datas selecionadas
                updateSelectedDatesDisplay();

                // Mostrar o painel de detalhes da reserva
                $('#reserva-details').show();

                // Restaurar a seleção do radio button de pagamento
                if (paymentPercentage === '100') {
                    $('#payment_100').prop('checked', true);
                } else {
                    $('#payment_50').prop('checked', true);
                }

                // Limpar o localStorage após restaurar
                localStorage.removeItem('selectedDates');
                localStorage.removeItem('totalPrice');
                localStorage.removeItem('paymentPercentage');
                localStorage.removeItem('observacoes');
            }
        } catch (e) {
            console.error('Erro ao restaurar datas selecionadas:', e);
        }
    }
}

// Chamar a função ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatusOnLoad();
    // Verificar se há datas selecionadas para restaurar após login
    setTimeout(restoreSelectedDates, 500); // Pequeno delay para garantir que os elementos estejam carregados
});
</script>

<!-- Modal de Login/Registro -->
<div id="login-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box;">
    <div id="login-modal-content" class="card" style="background-color: #ffffff; color: #2c3e50; padding: 20px; max-width: 90%; width: 90%; max-width: 400px; position: relative;">
        <button id="close-login-modal" class="ui-btn ui-icon-delete ui-btn-icon-notext ui-btn-a" style="position: absolute; top: 8px; right: 8px; border-radius: 50% !important; width: 30px !important; height: 30px !important; min-height: 30px !important; padding: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; z-index: 1001;">Fechar</button>
        <h2 style="text-align: center; margin-top: 0; margin-bottom: 15px; color: #2c3e50; font-size: 1.0em;">Autenticação Necessária</h2>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">Para confirmar sua reserva, faça login ou cadastre-se.</p>
        
        <style>
        #loginModalForm, #registerModalForm {
            width: 100%;
        }
        #loginModalForm label, #registerModalForm label {
            display: block;
            margin-bottom: 5px;
            font-weight: normal;
        }
        #loginModalForm input, #registerModalForm input {
            width: 100% !important;
            margin-bottom: 12px !important;
            box-sizing: border-box !important;
            border-radius: 20px !important;
            height: 45px !important;
            padding: 10px !important;
        }
        #loginModalForm button, #registerModalForm button {
            width: 100% !important;
            margin: 15px 0 !important;
            border-radius: 20px !important;
        }
        #switch-to-register-btn, #switch-to-login-btn {
            border-radius: 20px !important;
        }
        </style>
        
        <div id="login-form-container">
            <form id="loginModalForm">
                <label for="email_login_modal">E-mail:</label>
                <input type="email" name="email_login_modal" id="email_login_modal" required>
                
                <label for="senha_login_modal">Senha:</label>
                <input type="password" name="senha_login_modal" id="senha_login_modal" required>
                
                <button type="submit" class="ui-btn ui-btn-b" id="loginSubmitBtn">Entrar</button>
            </form>

            <div style="text-align: center; margin-top: 15px;">
                <p style="color: #7f8c8d; margin: 12px 0; font-size: 0.9em;">Ou</p>
                <a href="#" id="switch-to-register-btn" class="ui-btn ui-btn-a">Crie sua conta</a>
            </div>
        </div>
        
        <div id="register-form-container" style="display: none;">
            <form id="registerModalForm">
                <label for="nome_registro_modal">Nome:</label>
                <input type="text" name="nome_registro_modal" id="nome_registro_modal" required>
                
                <label for="email_registro_modal">E-mail:</label>
                <input type="email" name="email_registro_modal" id="email_registro_modal" required>
                
                <label for="telefone_registro_modal">Telefone:</label>
                <input type="tel" name="telefone_registro_modal" id="telefone_registro_modal" required>
                
                <label for="senha_registro_modal">Senha:</label>
                <input type="password" name="senha_registro_modal" id="senha_registro_modal" required>
                
                <label for="confirmar_senha_registro_modal">Confirmar Senha:</label>
                <input type="password" name="confirmar_senha_registro_modal" id="confirmar_senha_registro_modal" required>
                
                <button type="submit" class="ui-btn ui-btn-b">Cadastrar</button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <p style="color: #7f8c8d; margin: 12px 0; font-size: 0.9em;">Já tem conta? <a href="#" id="switch-to-login-btn" class="ui-link">Faça login</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Pagamento PIX -->
<div id="pix-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box;">
    <div id="pix-modal-content" class="card" style="background-color: #2c3e50; color: white; padding: 20px; max-width: 350px; width: 100%; position: relative; text-align: left; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.3);">
        <button id="close-pix-modal" class="ui-btn ui-icon-delete ui-btn-icon-notext" style="position: absolute; top: 5px; right: 5px; background-color: transparent !important; border: none !important; box-shadow: none !important; color: #ecf0f1 !important;">Fechar</button>
        <div class="pix-details" style="margin-bottom: 15px;">
            <p style="margin: 6px 0; color: #ecf0f1; font-size: 12px;"><span style="color: #bdc3c7;">Realize seu pagamento:</span></p>
            <p style="margin: 6px 0; color: #ecf0f1; font-size: 10px;"><span style="color: #bdc3c7;">Check-in:</span> <span style="color: #ffffff;">09:00</span> | <span style="color: #bdc3c7;">Check-out:</span> <span style="color: #ffffff;">08:00</span></p>
            <p style="margin: 6px 0; color: #ecf0f1; font-size: 10px;"><span style="color: #bdc3c7;">Valor:</span> <span id="pix-valor" style="color: #ffffff;"></span></p>
        </div>
        <div id="payment-status-container">
            <p class="pix-instructions">Pague com o QR Code abaixo:</p>
            <div id="pix-qrcode"></div>
            <div style="margin-top: 15px; border-top: 1px solid #7f8c8d; padding-top: 15px;">
                <p style="font-size: 10px; margin-bottom: 8px; color: #bdc3c7;">Ou copie a chave PIX:</p>
                <div class="pix-copy-paste">
                    <input type="text" id="pix-key-input" value="Aguardando geração do código..." readonly style="overflow: auto; white-space: pre-wrap; text-overflow: clip; user-select: text; -webkit-user-select: text; -moz-user-select: text; -ms-user-select: text;" ontouchstart="this.scrollLeft=0" ontouchmove="this.scrollLeft=0" onscroll="this.scrollLeft=0" onclick="this.select();" onfocus="this.select();">
                    <button id="copy-pix-key-btn"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            <button id="cancel-reservation-btn" class="ui-btn">Desistir</button>
            <button id="confirm-payment-btn" class="ui-btn">Já Paguei, Agendar!</button>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/jquery.mobile-1.4.5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>

<script>
// Variável global para armazenar a data atual do calendário
let currentDate = new Date();

// Função para carregar dados da agenda (tornada global para ser acessível fora do pageinit)
function loadAgendaData(date) {
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const timestamp = new Date().getTime(); // Adiciona timestamp para evitar cache
    $.ajax({
        url: `../php/cliente_agenda.php?action=get_month&month=${month}&year=${year}&t=${timestamp}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.success && typeof response.prices === 'object') {
                renderReservaCalendar(date, response.prices);
            } else {
                console.error("Falha ao carregar dados do calendário: ", response.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Erro de comunicação com o servidor: ", textStatus, errorThrown);
        }
    });
}

function renderReservaCalendar(date, agenda) {
    const month = date.getMonth();
    const year = date.getFullYear();
    $('#reservaCurrentMonthYear').text(date.toLocaleString('pt-BR', { month: 'long', year: 'numeric' }));
    const firstDayOfMonth = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const calendar = $('#reservaCalendar').empty();
    const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    weekDays.forEach(day => calendar.append(`<div class="day-header">${day}</div>`));
    for (let i = 0; i < firstDayOfMonth; i++) {
        calendar.append('<div class="calendar-day"></div>');
    }
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayInfo = agenda[dateStr];
        let dayElement = $(`<div class="calendar-day" data-date="${dateStr}"><span class="day-number">${day}</span></div>`);
        if (dayInfo) {
            if (dayInfo.status === 'ativo') {
                dayElement.addClass('available');
                const formattedPrice = parseFloat(dayInfo.preco.replace(/\./g, '').replace(',', '.')).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                dayElement.append(`<div class="price">${formattedPrice}</div>`);
                dayElement.data('price', formattedPrice);
            } else if (dayInfo.status === 'reservado') {
                dayElement.addClass('reserved');
            } else if (dayInfo.status === 'pendente') {
                dayElement.addClass('pending').addClass('reserved'); // Usar a mesma classe visual de reservado mas com estilo adicional
            }
        }
        calendar.append(dayElement);
    }
}

// Função para atualizar o display das datas selecionadas (tornada global para ser acessível fora do pageinit)
// Variável global para armazenar as datas selecionadas no contexto do calendário
let selectedDates = [];

function updateSelectedDatesDisplay() {
    const datesList = $('#selected-dates-list');
    datesList.empty();

    let totalPrice = 0;

    selectedDates.forEach((item, index) => {
        const priceValue = parseFloat(item.price.replace('R$', '').replace(/\./g, '').replace(',', '.').trim());
        totalPrice += priceValue;

        const listItem = $(`<li class="selected-date-item"><span class="date-info">${item.formatted} - ${item.price}</span>
            <button class="remove-date-btn" data-index="${index}" style="margin-left: 10px; background: #e74c3c; color: white; border: none; border-radius: 4px; padding: 2px 6px; cursor: pointer;">Remover</button>
            </li>`);
        datesList.append(listItem);
    });

    // Atualizar o valor total
    if (selectedDates.length > 0) {
        const formattedTotal = totalPrice.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        $('#selected-total-price-text').text(formattedTotal);
        $('#selected_dates_input').val(JSON.stringify(selectedDates.map(d => d.date)));
        $('#total_price_input').val(totalPrice);
        $('#reserva-details').slideDown();

        // Atualizar o valor exibido no modal do PIX com base na porcentagem selecionada
        updatePixModalValue();
    } else {
        $('#reserva-details').hide();
    }
}

// Função para atualizar o valor exibido no modal do PIX (tornada global para ser acessível fora do pageinit)
function updatePixModalValue() {
    if (selectedDates.length > 0) {
        const selectedPercentage = $('input[name="payment_type"]:checked').val();
        const totalValue = parseFloat($('#total_price_input').val());
        const paymentValue = (totalValue * parseInt(selectedPercentage)) / 100;
        const formattedValue = paymentValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        // Atualizar o valor exibido no modal do PIX se o modal estiver aberto
        $('#pix-valor').text(formattedValue);
    }
}

// Script para manipulação do calendário (mantendo a funcionalidade original)
$(document).on('pageinit', '#reservaPageCliente', function() {
    
    $('#reservaPrevMonth').on('click', function() { 
        currentDate.setMonth(currentDate.getMonth() - 1); 
        selectedDates = []; // Limpar seleções ao mudar de mês
        updateSelectedDatesDisplay();
        loadAgendaData(currentDate); 
    });
    $('#reservaNextMonth').on('click', function() { 
        currentDate.setMonth(currentDate.getMonth() + 1); 
        selectedDates = []; // Limpar seleções ao mudar de mês
        updateSelectedDatesDisplay();
        loadAgendaData(currentDate); 
    });

    $('#reservaCalendar').on('click', '.available', function() {
        const selectedDate = $(this).data('date');
        const selectedPrice = $(this).data('price');
        const dateObj = new Date(selectedDate + 'T00:00:00');
        const formattedDate = dateObj.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
        
        // Verificar se a data já está selecionada
        const existingIndex = selectedDates.findIndex(item => item.date === selectedDate);
        
        if (existingIndex >= 0) {
            // Remover a data se já estiver selecionada
            selectedDates.splice(existingIndex, 1);
            $(this).removeClass('selected');
        } else {
            // Adicionar a data se ainda não estiver selecionada
            selectedDates.push({date: selectedDate, price: selectedPrice, formatted: formattedDate});
            $(this).addClass('selected');
        }
        
        updateSelectedDatesDisplay();
    });
    
    
    // Remover data individualmente
    $(document).on('click', '.remove-date-btn', function(e) {
        e.stopPropagation();
        const index = parseInt($(this).data('index'));
        if (index >= 0 && index < selectedDates.length) {
            const removedDate = selectedDates[index].date;
            selectedDates.splice(index, 1);
            
            // Remover a classe 'selected' da célula do calendário
            $(`.calendar-day[data-date="${removedDate}"]`).removeClass('selected');
            
            updateSelectedDatesDisplay();
        }
    });
    
    // Atualizar o valor exibido no modal do PIX quando a porcentagem for alterada
    $(document).on('change', 'input[name="payment_type"]', function() {
        const selectedPercentage = $(this).val();
        $('#payment_percentage_input').val(selectedPercentage);
        updatePixModalValue();
    });
    

    loadAgendaData(currentDate);
    
    // Recarregar o calendário para garantir que ele apareça corretamente
    setTimeout(function() {
        loadAgendaData(currentDate);
    }, 200); // Pequeno delay adicional para garantir carregamento completo
});

// Adicionar uma verificação adicional para garantir que o calendário apareça
$(document).on('pageshow', '#reservaPageCliente', function() {
    // Força a renderização do calendário novamente ao mostrar a página
    setTimeout(function() {
        if (typeof currentDate !== 'undefined') {
            loadAgendaData(currentDate);
        }
    }, 300);
});

// Função para garantir que o calendário é carregado corretamente em diferentes cenários do jQuery Mobile
function initializeCalendarIfNeeded() {
    loadAgendaData(currentDate); // Usar a variável global currentDate
}

// Função global para verificar o status do pagamento - definida no escopo global para ser acessível de qualquer lugar
function checkPaymentStatus(dates) {
    window.verificationAttempts++;
    console.log(`Tentativa de verificação #${window.verificationAttempts}`);

    // Adiciona log para rastrear o processo
    console.log('Iniciando verificação de status do pagamento para datas:', dates);

    // Chamar endpoint para verificar status das reservas
    fetch('../php/check_reservation_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dates=' + encodeURIComponent(dates)
    })
    .then(response => {
        console.log('Resposta recebida do servidor:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos da verificação:', data);

        // Verificar se excedeu o número máximo de tentativas (verificar após obter resposta)
        if (data.success && !data.all_confirmed && window.verificationAttempts >= window.maxVerificationAttempts) {
            console.log('Número máximo de tentativas de verificação atingido, redirecionando para minhas reservas...');
            console.log('Detalhes do redirecionamento por limite de tentativas');
            $('#payment-status-indicator').html('<p style="color: #ecf0f1; font-size: 10px;">Tempo limite atingido. Redirecionando...</p>');
            // Close the modal before redirecting
            $('#pix-modal-overlay').hide();
            // Como fallback adicional, também tentar fechar com CSS
            const modalElement = document.getElementById('pix-modal-overlay');
            if (modalElement) {
                modalElement.style.display = 'none';
                console.log('Modal fechado via CSS fallback');
            }
            // Redirecionar imediatamente sem delay para garantir o redirecionamento
            console.log('Efetuando redirecionamento para suas_reservas.php (limite de tentativas)');
            window.location.replace('suas_reservas.php');
            return;
        }
        if (data.success) {
            if (data.all_confirmed) {
                // Todas as reservas foram confirmadas
                console.log('Pagamento confirmado detectado, fechando modal...');
                console.log('Data from server:', data);

                // Adiciona log antes de exibir o alerta de sucesso
                console.log('Exibindo alerta de sucesso ao usuário');

                // Fechar o modal imediatamente após confirmação de pagamento
                $('#pix-modal-overlay').hide(); // jQuery Mobile hide - mais confiável que fadeOut
                console.log('Modal fechado via jQuery após sucesso');

                // Como fallback adicional, também tentar fechar com CSS
                const modalElement = document.getElementById('pix-modal-overlay');
                if (modalElement) {
                    modalElement.style.display = 'none';
                    console.log('Modal fechado via CSS fallback após sucesso');
                }

                // Exibir mensagem de sucesso
                Swal.fire({
                    title: 'Sucesso!',
                    text: 'Pagamento confirmado! Sua reserva está confirmada.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#27ae60'
                }).then((result) => {
                    console.log('Usuário confirmou o alerta de sucesso');
                    // Redirecionar para a página de minhas reservas após mostrar o alerta
                    console.log('Executando redirecionamento de sucesso para suas_reservas.php');
                    window.location.replace('suas_reservas.php');
                }).catch((error) => {
                    console.error('Erro ao exibir o alerta de sucesso:', error);
                    // Redirecionar mesmo se houver erro no alerta
                    console.log('Redirecionando devido a erro no alerta de sucesso');
                    window.location.replace('suas_reservas.php');
                });
            } else {
                // Ainda aguardando confirmação
                console.log('Aguardando confirmação de pagamento...', data);
                $('#payment-status-indicator').html('<p style="color: #ecf0f1; font-size: 10px;">Aguardando confirmação de pagamento...</p>');

                // Verificar novamente após 5 segundos (aumentei o intervalo para reduzir chamadas excessivas)
                setTimeout(() => {
                    // Verificar se o modal ainda está aberto antes de continuar verificando
                    if ($('#pix-modal-overlay').is(':visible')) {
                        // Verificar se excedeu o número máximo de tentativas
                        if (window.verificationAttempts >= window.maxVerificationAttempts) {
                            console.log('Número máximo de tentativas de verificação atingido (dentro do timeout), redirecionando para minhas reservas...');
                            $('#payment-status-indicator').html('<p style="color: #ecf0f1; font-size: 10px;">Tempo limite atingido. Redirecionando...</p>');
                            // Close the modal before redirecting
                            $('#pix-modal-overlay').hide();
                            // Como fallback adicional, também tentar fechar com CSS
                            const modalElement = document.getElementById('pix-modal-overlay');
                            if (modalElement) {
                                modalElement.style.display = 'none';
                                console.log('Modal fechado via CSS fallback por limite de tentativas no timeout');
                            }
                            // Redirecionar imediatamente sem delay para garantir o redirecionamento
                            console.log('Efetuando redirecionamento para suas_reservas.php (timeout limite de tentativas)');
                            window.location.replace('suas_reservas.php');
                        } else {
                            console.log('Continuando verificação, nova tentativa agendada');
                            checkPaymentStatus(dates);
                        }
                    } else {
                        console.log('Modal fechado (dentro do timeout), parando verificação automática');
                    }
                }, 5000);
            }
        } else {
            // Resetar contador em caso de erro e tentar novamente
            window.verificationAttempts = Math.max(0, window.verificationAttempts - 1); // Evitar que erro conte como tentativa
            // Erro na verificação
            console.log('Erro na verificação do status do pagamento:', data.message);
            $('#payment-status-indicator').html('<p style="color: #ecf0f1; font-size: 10px;">Erro na verificação: ' + data.message + '</p>');
            // Continuar verificando mesmo em caso de erro, para lidar com possíveis falhas temporárias
            setTimeout(() => {
                // Verificar se o modal ainda está aberto antes de continuar verificando
                if ($('#pix-modal-overlay').is(':visible')) {
                    console.log('Continuando verificação apesar do erro, nova tentativa agendada');
                    checkPaymentStatus(dates);
                } else {
                    console.log('Modal fechado (após erro), parando verificação automática');
                }
            }, 5000);
        }
    })
    .catch(error => {
        console.error('Erro completo ao verificar status do pagamento:', error);
        console.error('Erro de comunicação com o servidor ao verificar pagamento:', error);
        console.log('Detalhes do erro:', error.message, error.stack);
        $('#confirm-payment-btn').text('Já paguei, verificar!').prop('disabled', false);

        // Mesmo em caso de erro de comunicação, continuar verificando
        // Verificar se o modal ainda está aberto antes de continuar verificando
        if ($('#pix-modal-overlay').is(':visible')) {
            console.log('Continuando verificação apesar do erro de comunicação');
            setTimeout(() => {
                checkPaymentStatus(dates);
            }, 5000);
        } else {
            console.log('Modal fechado (após erro de comunicação), não continuando verificação');
        }
    });
}

// Eventos para o modal
$(document).ready(function() {
    $('#close-pix-modal, #cancel-reservation-btn').on('click', function() {
        $('#pix-modal-overlay').hide();
        // Como fallback adicional, também tentar fechar com CSS
        const modalElement = document.getElementById('pix-modal-overlay');
        if (modalElement) {
            modalElement.style.display = 'none';
        }
        // Redirect to suas_reservas.php when closing the modal after payment attempt
        window.location.replace('suas_reservas.php');
    });

    // Redirect when clicking outside the modal content
    $(document).on('click', '#pix-modal-overlay', function(e) {
        // Only redirect if the click was directly on the overlay, not on the modal content
        if (e.target.id === 'pix-modal-overlay') {
            $('#pix-modal-overlay').hide();
            // Como fallback adicional, também tentar fechar com CSS
            const modalElement = document.getElementById('pix-modal-overlay');
            if (modalElement) {
                modalElement.style.display = 'none';
            }
            // Redirect to suas_reservas.php when closing the modal after payment attempt
            window.location.replace('suas_reservas.php');
        }
    });
    
    // Variáveis globais para controle de verificação de pagamento (tornadas globais para serem acessíveis fora do document.ready)
    window.verificationAttempts = 0; // Contador de tentativas de verificação
    window.maxVerificationAttempts = 24; // Máximo de 24 tentativas (aproximadamente 2 minutos com intervalos de 5 segundos)

    // Eventos para o modal de login
    $('#close-login-modal').on('click', function() {
        $('#login-modal-overlay').hide();
    });
    
    // Alternar entre login e registro
    $('#switch-to-register-btn').on('click', function(e) {
        e.preventDefault();
        $('#login-form-container').hide();
        $('#register-form-container').show();
    });
    
    $('#switch-to-login-btn').on('click', function(e) {
        e.preventDefault();
        $('#register-form-container').hide();
        $('#login-form-container').show();
    });
    
    // Submissão do formulário de login no modal
    $('#loginModalForm').on('submit', function(e) {
        e.preventDefault();
        loginModal();
    });
    
    // Submissão do formulário de registro no modal
    $('#registerModalForm').on('submit', function(e) {
        e.preventDefault();
        registerModal();
    });
    
    // Manipulador para o botão de cópia da chave PIX
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
    
    
    // Manipulador para o botão de confirmação de pagamento
    $(document).off('click', '#confirm-payment-btn').on('click', '#confirm-payment-btn', function() {
        // Verificar se a verificação automática já começou (botão foi escondido)
        if ($(this).is(':hidden')) {
            // A verificação automática já está em andamento, não fazer nada
            return;
        }

        // Atualizar o texto e desabilitar o botão para evitar múltiplos cliques
        $(this).text('Verificando...').prop('disabled', true);

        // Mostrar indicador de status
        $('#payment-status-indicator').show();

        // Obter as datas selecionadas
        const selectedDates = $('#selected_dates_input').val();

        // Resetar contador de tentativas antes de iniciar nova verificação
        window.verificationAttempts = 0;

        // Iniciar verificação automática do status do pagamento
        checkPaymentStatus(selectedDates);
    });
    
    // Função para copiar imagem do QR Code
    function copyQRCodeImage() {
        const qrCodeImg = document.getElementById('pix-qr-image');
        if (qrCodeImg) {
            // Criar um canvas temporário para converter a imagem para blob
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = qrCodeImg.width;
            canvas.height = qrCodeImg.height;
            ctx.drawImage(qrCodeImg, 0, 0);
            
            canvas.toBlob(function(blob) {
                const item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function() {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Imagem do QR Code copiada para a área de transferência!',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#27ae60'
                    });
                }).catch(function(err) {
                    console.error('Erro ao copiar imagem:', err);
                    // Fallback: exibir mensagem de instruções
                    Swal.fire({
                        title: 'Instruções',
                        html: 'Para copiar o QR Code, pressione e segure a imagem no celular ou clique com o botão direito e selecione "Copiar imagem" no computador.',
                        icon: 'info',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#34495e'
                    });
                });
            });
        }
    }
    
    // Adicionar evento para permitir cópia do QR Code ao clicar nele
    $(document).on('click', '#pix-qr-image', function() {
        copyQRCodeImage();
    });
});

// Associar o evento de clique ao botão após o carregamento da página e garantir que o calendário seja carregado
$(document).on('pageshow', '#reservaPageCliente', function() {
    $('#confirmReservaBtn').off('click').on('click', function() {
        handleReservaClick();
    });
    
    // Garantir que o calendário é carregado quando a página é mostrada
    setTimeout(function() {
        initializeCalendarIfNeeded();
    }, 50);
});

// Garantir que o calendário também seja inicializado quando a página for ativada
$(document).on('pagebeforeshow', '#reservaPageCliente', function() {
    // Isso ajuda em casos onde o pageinit não é chamado novamente
    setTimeout(function() {
        initializeCalendarIfNeeded();
    }, 100); // Pequeno delay para garantir que o DOM esteja pronto
});

// Manipulador para o botão de logout
$(document).on('pageinit', '#reservaPageCliente', function() {
    // Usar delegate para o botão de logout, já que ele é definido no HTML
    $(document).on('click', '#logout-link-reserva', function(e) {
        e.preventDefault();

        $.ajax({
            url: '../php/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Limpar possíveis dados da sessão no frontend e redirecionar de forma limpa
                    window.location.replace('reserva.php');
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao fazer logout. Por favor, tente novamente.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            },
            error: function() {
                // Mesmo em caso de erro, redirecionar para a página inicial
                window.location.href = 'reserva.php';
            }
        });
    });

    // Certificar-se de que o calendário é carregado quando a página é inicializada
    setTimeout(function() {
        if (typeof currentDate !== 'undefined') {
            loadAgendaData(currentDate);
        } else {
            // Se currentDate não estiver definido, criar e usar uma nova data
            const localCurrentDate = new Date();
            loadAgendaData(localCurrentDate);
        }
    }, 100); // Pequeno delay para garantir que o DOM esteja pronto
});

// Função adicional para garantir o carregamento do calendário em diferentes cenários
$(document).on('pageshow', '#reservaPageCliente', function() {
    // Garantir que o calendário seja carregado ao mostrar a página
    setTimeout(function() {
        if (typeof currentDate !== 'undefined') {
            loadAgendaData(currentDate);
        } else {
            const localCurrentDate = new Date();
            loadAgendaData(localCurrentDate);
        }
    }, 150);
});

// Se o pagebeforeshow não for suficiente, vamos adicionar uma verificação adicional
$(document).on('pageremove', '#reservaPageCliente', function() {
    // Esta função é chamada quando uma página é removida do DOM
    // Podemos usar isso para limpar variáveis se necessário
});
</script>

</body>
</html>
