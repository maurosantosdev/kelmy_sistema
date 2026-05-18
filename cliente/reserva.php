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
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        #reservaCalendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            font-size: 14px; /* Define base font size for better scaling */
        }
        #reservaCalendar .day-header {
            text-align: center;
            padding: 3px 0;
            font-weight: 600;
            font-size: 0.5rem;
            background-color: #e9ecef;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
            line-height: 1.2;
        }
        #reservaCalendar .calendar-day {
            aspect-ratio: 1/1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1px 0;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #ffffff;
            position: relative;
            min-height: 20px;
        }
        #reservaCalendar .calendar-day:nth-child(7n) {
            border-right: none;
        }
        #reservaCalendar .calendar-day:last-child,
        #reservaCalendar .calendar-day:nth-last-child(-n+7) {
            border-bottom: none;
        }
        #reservaCalendar .calendar-day:hover:not(.reserved):not(.pending) {
            background-color: #e3f2fd;
            transform: scale(1.03);
            z-index: 1;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
        }
        #reservaCalendar .calendar-day.selected {
            background-color: #3498db;
            color: white;
            box-shadow: inset 0 0 0 2px #2980b9;
            z-index: 2;
        }
        #reservaCalendar .calendar-day.available {
            background-color: #f8fff9;
            cursor: pointer;
        }
        #reservaCalendar .calendar-day.reserved {
            background-color: #ffebee;
            cursor: not-allowed;
            opacity: 0.7;
        }
        #reservaCalendar .calendar-day.pending {
            background-color: #fffde7;
            cursor: not-allowed;
            opacity: 0.7;
        }
        #reservaCalendar .day-number {
            font-size: 0.5rem;
            font-weight: 600;
            margin-bottom: 0;
            line-height: 1.1;
        }
        #reservaCalendar .price {
            font-size: 0.4rem;
            color: #2c3e50 !important;
            font-weight: 500;
            text-align: center;
            line-height: 1.1;
        }
        #reservaCalendar .calendar-day.selected .day-number,
        #reservaCalendar .calendar-day.selected .price {
            color: white !important;
        }

        /* Estilos responsivos para dispositivos móveis */
        @media (max-width: 768px) {
            #reservaCalendar .day-header {
                padding: 3px 0;
                font-size: 0.5rem;
            }

            #reservaCalendar .calendar-day {
                padding: 1px 0;
            }

            #reservaCalendar .day-number {
                font-size: 0.5rem;
            }

            #reservaCalendar .price {
                font-size: 0.4rem;
            }
        }

        @media (max-width: 480px) {
            #reservaCalendar .day-header {
                padding: 3px 0;
                font-size: 0.5rem;
            }

            #reservaCalendar .calendar-day {
                padding: 1px 0;
            }

            #reservaCalendar .day-number {
                font-size: 0.5rem;
            }

            #reservaCalendar .price {
                font-size: 0.4rem;
            }
        }

        @media (max-width: 400px) {
            #reservaCalendar .day-header {
                padding: 2px 0;
                font-size: 0.45rem;
            }

            #reservaCalendar .calendar-day {
                padding: 0px;
            }

            #reservaCalendar .day-number {
                font-size: 0.45rem;
            }

            #reservaCalendar .price {
                font-size: 0.35rem;
            }
        }

        @media (max-width: 350px) {
            #reservaCalendar .day-header {
                padding: 2px 0;
                font-size: 0.45rem;
            }

            #reservaCalendar .calendar-day {
                padding: 0px;
            }

            #reservaCalendar .day-number {
                font-size: 0.45rem;
            }

            #reservaCalendar .price {
                font-size: 0.35rem;
            }
        }

        /* Estilos para o card de Detalhes da Seleção - alinhamento à esquerda e tamanho de texto padronizado */
        #reserva-details {
            text-align: left !important;
            margin-bottom: 120px; /* Aumenta a margem inferior para espaçamento do footer fixo */
        }
        #reserva-details h2 {
            text-align: left !important;
            margin-left: 0 !important;
        }
        #reserva-details p {
            text-align: left !important;
            margin-left: 0 !important;
        }
        #reserva-details label {
            display: block !important;
            text-align: left !important;
            margin-left: 0 !important;
        }
        #selected-dates-container {
            text-align: left !important;
            margin-left: 0 !important;
        }
        #selected-dates-list {
            text-align: left !important;
            margin-left: 0 !important;
            padding-left: 15px !important;
        }
        #selected-total-price-text {
            font-size: 1.1em;
            font-weight: bold;
        }
        /* Espaçamento entre o botão radio e o texto (20px como solicitado) */
        #payment_50, #payment_100 {
            margin-right: 20px !important;
        }

        /* Estilo para o grid do calendário */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }

        /* Estilo para a legenda do calendário */
        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            font-size: 0.85rem;
            padding: 10px 0;
            border-top: 1px solid #dee2e6;
            margin-top: 10px;
        }
        .calendar-legend .dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin-right: 6px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .calendar-legend .available {
            background-color: #d4edda;
        }
        .calendar-legend .reserved {
            background-color: #f8d7da;
        }
        .calendar-legend .pending {
            background-color: #fff3cd;
        }

        /* Estilos específicos para o modal PIX */
        .modal-body .card {
            transition: transform 0.2s;
        }
        .modal-body .card:hover {
            transform: translateY(-3px);
        }
        #pix-qrcode img {
            max-width: 100%;
            height: auto;
        }
        .payment-method .bg-light {
            background-color: #f8f9fa !important;
        }
        .pix-key-input {
            font-family: monospace;
            letter-spacing: 1px;
        }

        /* Estilos responsivos para o título do calendário */
        #reservaCurrentMonthYear {
            font-size: 1.25rem;
        }

        @media (max-width: 768px) {
            #reservaCurrentMonthYear {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            #reservaCurrentMonthYear {
                font-size: 1rem;
            }
        }

        @media (max-width: 400px) {
            #reservaCurrentMonthYear {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 350px) {
            #reservaCurrentMonthYear {
                font-size: 0.8rem;
            }
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
        header .btn-light.btn-sm {
            min-height: 1.5rem;
            min-width: auto;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            header .btn-light.btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            header .btn-light.btn-sm {
                padding: 0.15rem 0.3rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 400px) {
            header .btn-light.btn-sm {
                padding: 0.12rem 0.25rem;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 350px) {
            header .btn-light.btn-sm {
                padding: 0.1rem 0.2rem;
                font-size: 0.65rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            header .btn-light.btn-sm {
                min-height: 1.1rem;
                padding: 0.05rem 0.15rem !important;
                font-size: 0.6rem;
            }
        }

        /* Estilos responsivos para os botões de navegação do calendário */
        #reservaPrevMonth,
        #reservaNextMonth {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 400px) {
            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.15rem 0.3rem;
                font-size: 0.7rem;
                min-width: auto;
            }
        }

        @media (max-width: 350px) {
            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.1rem 0.25rem;
                font-size: 0.65rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.08rem 0.2rem;
                font-size: 0.6rem;
            }
        }

        /* Estilos responsivos para o container do calendário */
        .card .card-body {
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .card .card-body {
                padding: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .card .card-body {
                padding: 0.5rem;
            }
        }

        @media (max-width: 400px) {
            .card .card-body {
                padding: 0.4rem;
            }
        }

        @media (max-width: 350px) {
            .card .card-body {
                padding: 0.25rem;
            }
        }

        @media (max-width: 300px) and (max-height: 660px) {
            .card .card-body {
                padding: 0.15rem;
            }
        }

        /* Estilo específico para a resolução mínima de 300x660 */
        @media (max-width: 300px) {
            #reservaCurrentMonthYear {
                font-size: 0.7rem;
            }

            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.08rem 0.2rem;
                font-size: 0.55rem;
            }

            #reservaCalendar .day-header {
                padding: 2px 0;
                font-size: 0.45rem;
            }

            #reservaCalendar .calendar-day {
                padding: 1px 0px;
            }

            #reservaCalendar .day-number {
                font-size: 0.45rem;
            }

            #reservaCalendar .price {
                font-size: 0.3rem;
            }

            .card .card-body {
                padding: 0.05rem;
            }
        }

        /* Estilo para altura mínima de 660px (ajuste se necessário para layouts verticais) */
        @media (max-height: 660px) {
            #reservaCalendar .calendar-day {
                min-height: 20px;
                padding: 2px 1px;
            }
        }

        /* Estilo específico para dispositivos com resolução semelhante ao Galaxy S8 (360x740) */
        @media (max-width: 360px) and (max-height: 740px) {
            #reservaCalendar .day-header {
                padding: 3px 0;
                font-size: 0.55rem;
            }

            #reservaCalendar .calendar-day {
                padding: 1px 0px;
            }

            #reservaCalendar .day-number {
                font-size: 0.55rem;
            }

            #reservaCalendar .price {
                font-size: 0.4rem;
            }

            #reservaCurrentMonthYear {
                font-size: 0.8rem;
            }

            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.1rem 0.25rem;
                font-size: 0.6rem;
            }
        }

        /* Estilo específico para a dimensão mínima de 300x660 */
        @media (max-width: 300px) and (max-height: 660px) {
            #reservaCalendar .day-header {
                padding: 2px 0;
                font-size: 0.4rem;
                line-height: 1.1;
            }

            #reservaCalendar .calendar-day {
                padding: 0px;
                min-height: 16px;
            }

            #reservaCalendar .day-number {
                font-size: 0.4rem;
                margin-bottom: 0;
                line-height: 1.1;
            }

            #reservaCalendar .price {
                font-size: 0.3rem;
                line-height: 1.1;
            }

            #reservaCurrentMonthYear {
                font-size: 0.7rem;
            }

            #reservaPrevMonth,
            #reservaNextMonth {
                padding: 0.05rem 0.15rem;
                font-size: 0.5rem;
            }

            .card .card-body {
                padding: 0.05rem;
            }
        }
    </style>
</head>
<body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div id="reservaPageCliente" class="container-fluid">

    <header class="bg-white text-dark py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0" id="header-title-reserva"><?php echo isset($_SESSION['user_name']) ? 'Olá ' . htmlspecialchars($_SESSION['user_name']) : 'Reserve sua Data'; ?></h1>
                <?php if (isset($_SESSION['user_name'])): ?>
                    <a href="suas_reservas.php" class="btn btn-light btn-sm">Minhas Reservas</a>
                <?php else: ?>
                    <a href="#" onclick="showLoginModal(); return false;" id="login-link-reserva" class="btn btn-light btn-sm">Entrar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 30px; padding-bottom: 80px;">

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <button id="reservaPrevMonth" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <h2 id="reservaCurrentMonthYear" class="h5 mb-0"></h2>
                <button id="reservaNextMonth" class="btn btn-outline-secondary">
                    Próximo <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="card-body">
                <div id="reservaCalendar" class="calendar-grid"></div>

                <div class="calendar-legend mt-3">
                    <div class="d-flex gap-3">
                        <div><span class="dot available"></span> Disponível</div>
                        <div><span class="dot reserved"></span> Reservado</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4" id="reserva-details" style="display:none;">
            <div class="card-body">
                <h2 class="h5">Detalhes da Seleção</h2>
                <div id="selected-dates-container">
                    <p>Datas selecionadas:</p>
                    <ul id="selected-dates-list" class="list-unstyled"></ul>
                </div>
                <p>Check-in: <strong>09:00</strong> | Check-out: <strong>08:00</strong></p>
                <p>Valor total das diárias: <strong id="selected-total-price-text"></strong></p>
                <p>Forma de pagamento:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="payment_type" value="50" id="payment_50" checked>
                    <label class="form-check-label" for="payment_50">50% (Entrada)</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_type" value="100" id="payment_100">
                    <label class="form-check-label" for="payment_100">100% (Total)</label>
                </div>

                <form id="reservaForm">
                    <input type="hidden" name="selected_dates" id="selected_dates_input">
                    <input type="hidden" name="total_price" id="total_price_input">
                    <input type="hidden" name="payment_percentage" id="payment_percentage_input" value="50">
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações (opcional):</label>
                        <textarea name="observacoes" id="observacoes" class="form-control"></textarea>
                    </div>
                    <button type="button" id="confirmReservaBtn" class="btn btn-primary w-100">Confirmar Reserva</button>
                </form>
            </div>
        </div>
    </main>

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
                    <a href="reserva.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center active">
                        <i class="fas fa-calendar-alt mb-1"></i>
                        <small>Reserve</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="suas_reservas.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
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
</div>

<script>
// Definindo a função global diretamente no HTML para garantir que esteja disponível
function handleReservaClick() {
    // Verificar se o usuário está logado com base no HTML gerado pelo PHP
    // O PHP já gera o header com base no status de login, então podemos verificar isso
    const headerTitle = document.getElementById('header-title-reserva');
    const loginLink = document.getElementById('login-link-reserva');

    // Se o título do header contém o nome do usuário ou se o botão é "Minhas Reservas" em vez de "Entrar", o usuário está logado
    const isUserLoggedIn = headerTitle && headerTitle.textContent.includes('Olá');
    const hasMyBookingsButton = loginLink && loginLink.textContent.trim() === 'Minhas Reservas';

    if (isUserLoggedIn || hasMyBookingsButton) {
        // O usuário está logado, proceder diretamente para a confirmação da reserva
        confirmReserva();
    } else {
        // O usuário não está logado, mostrar o modal de login
        showLoginModal();
    }
}

// Função para mostrar o modal de login
function showLoginModal() {
        // Verificar se o usuário já está logado antes de mostrar o modal
    fetch('../php/cliente_login.php', {
        method: 'POST',
        credentials: 'include', // Incluir credenciais (cookies de sessão) na requisição
        headers: {
            'Content-Type': 'application/x-form-urlencoded',
        },
        body: 'check_session=1'  // Campo especial para verificar a sessão
    })
    .then(function(response) {
        // Verificar se a resposta é JSON antes de tentar fazer o parse
        if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
            return response.json();
        } else {
            // Se não for JSON, retornar um objeto padrão
            return { success: false, message: '' };
        }
    })
    .then(function(response) {
        if (response.success && response.message && response.message.includes('Usuário já está logado')) {
            // Usuário já está logado, mostrar mensagem e continuar na mesma página
            Swal.fire({
                title: 'Informação',
                text: response.message,
                icon: 'info',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#3498db'
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Atualizar a página para refletir o estado de autenticação e continuar na página de reserva
                    window.location.href = 'reserva.php';
                }
            });
        } else {
            // Mostrar o modal de login normalmente
            // Reset form containers
            document.getElementById('login-form-container').style.display = 'block';
            document.getElementById('register-form-container').style.display = 'none';

            // Limpar formulários
            document.getElementById('loginModalForm').reset();
            document.getElementById('registerModalForm').reset();

            // Show Bootstrap modal
            var bootstrapModal = new bootstrap.Modal(document.getElementById('loginModal'));

            // Corrigir o problema de acessibilidade com aria-hidden
            const modalElement = document.getElementById('loginModal');

            // Remover o atributo aria-hidden temporariamente antes de mostrar o modal
            const previousAriaHidden = modalElement.getAttribute('aria-hidden');
            modalElement.removeAttribute('aria-hidden');

            bootstrapModal.show();

            // Garantir que o atributo aria-hidden seja atualizado corretamente
            modalElement.addEventListener('shown.bs.modal', function() {
                // Após o modal ser mostrado, podemos redefinir aria-hidden se necessário
                // Mas para um modal visível, geralmente não deve ter aria-hidden="true"
                modalElement.setAttribute('data-previous-aria-hidden', previousAriaHidden || 'true');
            });

            modalElement.addEventListener('hidden.bs.modal', function() {
                // Quando o modal é fechado, restaurar o estado original de aria-hidden
                modalElement.setAttribute('aria-hidden', 'true');
            });
        }
    })
    .catch(function(error) {
        console.error('Erro na verificação de sessão:', error);
        // Em caso de erro na verificação, mostrar o modal normalmente
        // Reset form containers
        document.getElementById('login-form-container').style.display = 'block';
        document.getElementById('register-form-container').style.display = 'none';

        // Limpar formulários
        document.getElementById('loginModalForm').reset();
        document.getElementById('registerModalForm').reset();

        // Show Bootstrap modal
        var bootstrapModal = new bootstrap.Modal(document.getElementById('loginModal'));

        // Corrigir o problema de acessibilidade com aria-hidden
        const modalElement = document.getElementById('loginModal');

        // Remover o atributo aria-hidden temporariamente antes de mostrar o modal
        const previousAriaHidden = modalElement.getAttribute('aria-hidden');
        modalElement.removeAttribute('aria-hidden');

        bootstrapModal.show();

        // Garantir que o atributo aria-hidden seja atualizado corretamente
        modalElement.addEventListener('shown.bs.modal', function() {
            // Após o modal ser mostrado, podemos redefinir aria-hidden se necessário
            // Mas para um modal visível, geralmente não deve ter aria-hidden="true"
            modalElement.setAttribute('data-previous-aria-hidden', previousAriaHidden || 'true');
        });

        modalElement.addEventListener('hidden.bs.modal', function() {
            // Quando o modal é fechado, restaurar o estado original de aria-hidden
            modalElement.setAttribute('aria-hidden', 'true');
        });
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
        credentials: 'include', // Incluir credenciais (cookies de sessão) na requisição
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dates=' + encodeURIComponent(selectedDates) +
              '&observacoes=' + encodeURIComponent(observacoes) +
              '&total_valor=' + encodeURIComponent(totalValue) +
              '&payment_percentage=' + encodeURIComponent(paymentPercentage)
    })
    .then(function(response) {
        // Verificar se a resposta é ok antes de tentar converter para JSON
        if (!response.ok) {
            return response.text().then(function(errorText) {
                throw new Error('Erro na requisição: ' + response.status + ' ' + response.statusText + ' - ' + errorText);
            });
        }
        // Verificar se a resposta é JSON antes de fazer o parse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(function(responseText) {
                throw new Error('Resposta não é JSON: ' + responseText);
            });
        }
        return response.json();
    })
    .then(function(response) {
        if(response.success) {
            // Exibir o modal de pagamento PIX
            var bootstrapModal = new bootstrap.Modal(document.getElementById('pixModal'));
            bootstrapModal.show();

            // Armazenar os IDs de reserva no elemento do modal para uso posterior
            const pixModalElement = document.getElementById('pixModal');
            pixModalElement.dataset.reservationIds = JSON.stringify(response.reservation_ids || []);

            // Atualizar o modal com o QR Code do Mercado Pago (usando código JavaScript vanilla)
            if (response.qr_code_base64) {
                // Atualizar as datas da reserva
                const pixDataElement = document.getElementById('pix-data');
                if (pixDataElement) {
                    const selectedDatesInput = document.getElementById('selected_dates_input');
                    let selectedDates = selectedDatesInput ? selectedDatesInput.value : 'Carregando...';

                    // Parse the JSON array of dates and format them as dd/mm/yyyy
                    let formattedDates = 'Carregando...';
                    if (selectedDates && selectedDates !== 'Carregando...') {
                        try {
                            const dateArray = JSON.parse(selectedDates);
                            if (Array.isArray(dateArray)) {
                                formattedDates = dateArray.map(function(dateStr) {
                                    // Parse the date string (format: YYYY-MM-DD) and convert to dd/mm/yyyy
                                    // Using UTC to avoid timezone issues that could shift the date
                                    const dateParts = dateStr.split('-');
                                    const year = parseInt(dateParts[0]);
                                    const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
                                    const day = parseInt(dateParts[2]);

                                    const date = new Date(Date.UTC(year, month, day));
                                    const formattedDay = String(date.getUTCDate()).padStart(2, '0');
                                    const formattedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
                                    const formattedYear = date.getUTCFullYear();
                                    return formattedDay + '/' + formattedMonth + '/' + formattedYear;
                                }).join(', ');
                            } else {
                                // If it's not an array, try to format as single date
                                const dateParts = selectedDates.split('-');
                                const year = parseInt(dateParts[0]);
                                const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
                                const day = parseInt(dateParts[2]);

                                const date = new Date(Date.UTC(year, month, day));
                                const formattedDay = String(date.getUTCDate()).padStart(2, '0');
                                const formattedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
                                const formattedYear = date.getUTCFullYear();
                                formattedDates = formattedDay + '/' + formattedMonth + '/' + formattedYear;
                            }
                        } catch (e) {
                            // If parsing fails, use original value
                            formattedDates = selectedDates.replace(/[\[\]"]/g, '').replace(/,/g, ', ');
                        }
                    }
                    pixDataElement.textContent = formattedDates;
                }

                // Atualizar o QR Code
                const qrCodeContainer = document.getElementById('pix-qrcode');
                if (qrCodeContainer) {
                    qrCodeContainer.innerHTML = '<img id="pix-qr-image" src="data:image/png;base64,' + response.qr_code_base64 + '" alt="QR Code PIX" style="width: 180px; height: 180px; padding: 10px; background-color: white; border-radius: 8px;">';
                }

                // Atualizar a chave PIX
                const pixKeyInput = document.getElementById('pix-key-input');
                if (pixKeyInput) {
                    pixKeyInput.value = response.pix_key || response.qr_code || 'Chave PIX não disponível';
                }
            } else if (response.payment_url) {
                // Atualizar as datas da reserva
                const pixDataElement = document.getElementById('pix-data');
                if (pixDataElement) {
                    const selectedDatesInput = document.getElementById('selected_dates_input');
                    let selectedDates = selectedDatesInput ? selectedDatesInput.value : 'Carregando...';

                    // Parse the JSON array of dates and format them as dd/mm/yyyy
                    let formattedDates = 'Carregando...';
                    if (selectedDates && selectedDates !== 'Carregando...') {
                        try {
                            const dateArray = JSON.parse(selectedDates);
                            if (Array.isArray(dateArray)) {
                                formattedDates = dateArray.map(function(dateStr) {
                                    // Parse the date string (format: YYYY-MM-DD) and convert to dd/mm/yyyy
                                    // Using UTC to avoid timezone issues that could shift the date
                                    const dateParts = dateStr.split('-');
                                    const year = parseInt(dateParts[0]);
                                    const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
                                    const day = parseInt(dateParts[2]);

                                    const date = new Date(Date.UTC(year, month, day));
                                    const formattedDay = String(date.getUTCDate()).padStart(2, '0');
                                    const formattedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
                                    const formattedYear = date.getUTCFullYear();
                                    return formattedDay + '/' + formattedMonth + '/' + formattedYear;
                                }).join(', ');
                            } else {
                                // If it's not an array, try to format as single date
                                const dateParts = selectedDates.split('-');
                                const year = parseInt(dateParts[0]);
                                const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
                                const day = parseInt(dateParts[2]);

                                const date = new Date(Date.UTC(year, month, day));
                                const formattedDay = String(date.getUTCDate()).padStart(2, '0');
                                const formattedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
                                const formattedYear = date.getUTCFullYear();
                                formattedDates = formattedDay + '/' + formattedMonth + '/' + formattedYear;
                            }
                        } catch (e) {
                            // If parsing fails, use original value
                            formattedDates = selectedDates.replace(/[\[\]"]/g, '').replace(/,/g, ', ');
                        }
                    }
                    pixDataElement.textContent = formattedDates;
                }

                // Atualizar a chave PIX
                const pixKeyInput = document.getElementById('pix-key-input');
                if (pixKeyInput) {
                    pixKeyInput.value = response.pix_key || response.qr_code || 'Chave PIX não disponível';
                }
            } else {
                // Atualizar as datas da reserva mesmo em caso de erro
                const pixDataElement = document.getElementById('pix-data');
                if (pixDataElement) {
                    const selectedDatesInput = document.getElementById('selected_dates_input');
                    let selectedDates = selectedDatesInput ? selectedDatesInput.value : 'Carregando...';

                    // Parse the JSON array of dates and format them as dd/mm/yyyy
                    let formattedDates = 'Carregando...';
                    if (selectedDates && selectedDates !== 'Carregando...') {
                        try {
                            const dateArray = JSON.parse(selectedDates);
                            if (Array.isArray(dateArray)) {
                                formattedDates = dateArray.map(function(dateStr) {
                                    // Parse the date string (format: YYYY-MM-DD) and convert to dd/mm/yyyy
                                    // Using UTC to avoid timezone issues that could shift the date
                                    const dateParts = dateStr.split('-');
                                    const year = parseInt(dateParts[0]);
                                    const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
                                    const day = parseInt(dateParts[2]);

                                    const date = new Date(Date.UTC(year, month, day));
                                    const formattedDay = String(date.getUTCDate()).padStart(2, '0');
                                    const formattedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
                                    const formattedYear = date.getUTCFullYear();
                                    return formattedDay + '/' + formattedMonth + '/' + formattedYear;
                                }).join(', ');
                            } else {
                                // If it's not an array, try to format as single date
                                const dateParts = selectedDates.split('-');
                                const year = parseInt(dateParts[0]);
                                const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
                                const day = parseInt(dateParts[2]);

                                const date = new Date(Date.UTC(year, month, day));
                                const formattedDay = String(date.getUTCDate()).padStart(2, '0');
                                const formattedMonth = String(date.getUTCMonth() + 1).padStart(2, '0');
                                const formattedYear = date.getUTCFullYear();
                                formattedDates = formattedDay + '/' + formattedMonth + '/' + formattedYear;
                            }
                        } catch (e) {
                            // If parsing fails, use original value
                            formattedDates = selectedDates.replace(/[\[\]"]/g, '').replace(/,/g, ', ');
                        }
                    }
                    pixDataElement.textContent = formattedDates;
                }

                // Atualizar mensagem de erro no QR Code
                const qrCodeContainer = document.getElementById('pix-qrcode');
                if (qrCodeContainer) {
                    qrCodeContainer.innerHTML = '<div class="alert alert-danger text-center"><i class="fas fa-exclamation-triangle me-2"></i>Não foi possível gerar o código PIX. Tente novamente mais tarde ou entre em contato com o suporte.</div>';
                }

                // Atualizar a chave PIX
                const pixKeyInput = document.getElementById('pix-key-input');
                if (pixKeyInput) {
                    pixKeyInput.value = response.pix_key || response.qr_code || 'Chave PIX não disponível';
                }
            }
            // Iniciar verificação automática do status do pagamento após exibir o QR Code
            // Aguardar 3 segundos antes de começar a verificar automaticamente
            setTimeout(function() {
                // Verificar se o modal ainda está aberto antes de iniciar a verificação
                const pixModalElement = document.getElementById('pixModal');
if (pixModalElement.classList.contains('show')) {
                    // Usar os IDs de reserva retornados pelo backend em vez das datas
                    const reservationIds = response.reservation_ids; // IDs de reserva retornados pelo backend

                    // Mostrar mensagem que a verificação automática começou
                    const paymentStatusIndicator = document.getElementById('payment-status-indicator');
                    if (paymentStatusIndicator) {
                        paymentStatusIndicator.innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Verificação automática iniciada...</p>';
                        paymentStatusIndicator.style.display = 'block';
                    }

                    // Atualizar o texto do botão para indicar que não é necessário clicar
                    const confirmPaymentBtn = document.getElementById('confirm-payment-btn');
                    if (confirmPaymentBtn) {
                        confirmPaymentBtn.textContent = 'Aguardando confirmação...';
                        confirmPaymentBtn.disabled = true;
                    }

                    // Resetar contador de tentativas antes de iniciar nova verificação
                    window.verificationAttempts = 0;

                    // Definir um tempo máximo global (1 minuto) para garantir redirecionamento
                    setTimeout(function() {
                        const pixModalElement = document.getElementById('pixModal');
if (pixModalElement.classList.contains('show')) {
                            console.log('Tempo máximo global atingido, forçando redirecionamento...');
                            const paymentStatusIndicator = document.getElementById('payment-status-indicator');
                            if (paymentStatusIndicator) {
                                paymentStatusIndicator.innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Tempo limite atingido. Redirecionando...</p>';
                            }

                            // Fechar o modal antes de redirecionar
                            const pixModalElement = document.getElementById('pixModal');
const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
if (modalInstance) {
    modalInstance.hide();
} else {
    // Se não houver instância, tentar fechar manualmente
    pixModalElement.classList.remove('show');
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
    pixModalElement.style.display = 'none';
}

                            // Redirecionar para suas_reservas.php após atingir o tempo limite
                            window.location.replace('suas_reservas.php');
                        }
                    }, 60000); // 1 minuto = 60000 ms (reduzido para melhor experiência do usuário)

                    // Iniciar a verificação de status do pagamento
                    if (reservationIds && reservationIds.length > 0) {
                        checkPaymentStatus(JSON.stringify(reservationIds)); // Passar os IDs de reserva como string JSON
                    } else {
                        console.error('Nenhum ID de reserva retornado para verificar pagamento');
                        // Fechar o modal e redirecionar em caso de erro
                        const pixModalElement = document.getElementById('pixModal');
const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
if (modalInstance) {
    modalInstance.hide();
} else {
    // Se não houver instância, tentar fechar manualmente
    pixModalElement.classList.remove('show');
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
    pixModalElement.style.display = 'none';
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
    .catch(function(error) {
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

    // Desabilitar o botão de login para evitar múltiplas submissões
    const submitButton = document.querySelector('#loginModalForm button[type="submit"]');
    submitButton.disabled = true;
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Processando...';

    // Usando fetch em vez de jQuery AJAX para melhor tratamento de erros
    fetch('../php/cliente_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(email) + '&senha=' + encodeURIComponent(senha)
    })
    .then(function(response) {
        // Verificar se a resposta é JSON antes de tentar fazer o parse
        if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
            return response.json();
        } else {
            // Se não for JSON, retornar um objeto padrão
            return { success: false, message: 'Erro na comunicação com o servidor' };
        }
    })
    .then(function(response) {
        if (response.success) {
            Swal.fire({
                title: 'Login realizado com sucesso!',
                text: response.message,
                icon: 'success',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#27ae60'
            }).then(function(result) {
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

                    // Fechar o modal de login usando Bootstrap
                    const loginModalElement = document.getElementById('loginModal');
                    const modalInstance = bootstrap.Modal.getInstance(loginModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        // Se não houver instância, tentar fechar manualmente
                        loginModalElement.classList.remove('show');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();
                        loginModalElement.style.display = 'none';
                    }

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
                }).then(function(result) {
                    if (result.isConfirmed) {
                        // Fechar o modal de login usando Bootstrap
                        const loginModalElement = document.getElementById('loginModal');
                        const modalInstance = bootstrap.Modal.getInstance(loginModalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        } else {
                            // Se não houver instância, tentar fechar manualmente
                            loginModalElement.classList.remove('show');
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) backdrop.remove();
                            loginModalElement.style.display = 'none';
                        }

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
    })
    .catch(function(error) {
        console.error('Erro na comunicação com o servidor:', error);
        // Em caso de erro de comunicação, mostrar mensagem genérica
        Swal.fire({
            title: 'Erro de comunicação',
            text: 'Ocorreu um erro na comunicação com o servidor. Por favor, atualize a página e tente novamente.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    })
    .finally(function() {
        // Reabilitar o botão de login após a requisição ser concluída (com sucesso ou erro)
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
}


// Função para registrar no modal
function registerModal() {
    console.log('Botão Cadastrar clicado - Iniciando processo de registro');

    const nome = document.getElementById('nome_registro_modal').value.trim();
    const rg = document.getElementById('rg_registro_modal').value.trim();
    const cpf = document.getElementById('cpf_registro_modal').value.trim();
    const estadoCivil = document.getElementById('estado_civil_registro_modal').value.trim();
    const rua = document.getElementById('rua_registro_modal').value.trim();
    const numero = document.getElementById('numero_registro_modal').value.trim();
    const bairro = document.getElementById('bairro_registro_modal').value.trim();
    const cep = document.getElementById('cep_registro_modal').value.trim();
    const cidade = document.getElementById('cidade_registro_modal').value.trim();
    const telefone = document.getElementById('telefone_registro_modal').value.trim();
    const dataNascimento = document.getElementById('data_nascimento_registro_modal').value.trim();
    const email = document.getElementById('email_registro_modal').value.trim();
    const senha = document.getElementById('senha_registro_modal').value.trim();
    const confirmarSenha = document.getElementById('confirmar_senha_registro_modal').value.trim();

    console.log('Valores coletados:', {nome, rg, cpf, estadoCivil, rua, numero, bairro, cep, cidade, telefone, dataNascimento, email, senha, confirmarSenha});

    if (nome === '' || rg === '' || estadoCivil === '' || rua === '' || numero === '' || bairro === '' || cidade === '' || dataNascimento === '' || email === '' || senha === '' || confirmarSenha === '') {
        console.log('Validação falhou - campos obrigatórios vazios');
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
        console.log('Validação de senhas falhou - senhas não coincidem');
        Swal.fire({
            title: 'Atenção!',
            text: 'As senhas não coincidem.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#34495e'
        });
        return;
    }

    // Validar formato do CPF
    const cpfRegex = /^\d{3}\.\d{3}\.\d{3}-\d{2}$/;
    if (cpf && !cpfRegex.test(cpf)) {
        console.log('Validação de CPF falhou - formato inválido');
        Swal.fire({
            title: 'Atenção!',
            text: 'Formato de CPF inválido. Use o formato XXX.XXX.XXX-XX.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#34495e'
        });
        return;
    }

    console.log('Validação concluída com sucesso - todas as verificações passaram');

    // Desabilitar o botão de cadastro para evitar múltiplas submissões
    const submitButton = document.querySelector('#registerModalForm button[type="submit"]');
    submitButton.disabled = true;
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Processando...';

    // Usando fetch em vez de jQuery AJAX para melhor tratamento de erros
    fetch('../php/cliente_cadastro.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'nome=' + encodeURIComponent(nome) +
              '&rg=' + encodeURIComponent(rg) +
              '&cpf=' + encodeURIComponent(cpf) +
              '&estado_civil=' + encodeURIComponent(estadoCivil) +
              '&rua=' + encodeURIComponent(rua) +
              '&numero=' + encodeURIComponent(numero) +
              '&bairro=' + encodeURIComponent(bairro) +
              '&cep=' + encodeURIComponent(cep) +
              '&cidade=' + encodeURIComponent(cidade) +
              '&telefone=' + encodeURIComponent(telefone) +
              '&data_nascimento=' + encodeURIComponent(dataNascimento) +
              '&email=' + encodeURIComponent(email) +
              '&senha=' + encodeURIComponent(senha) +
              '&confirmar_senha=' + encodeURIComponent(confirmarSenha)
    })
    .then(function(response) {
        // Verificar se a resposta é JSON antes de tentar fazer o parse
        if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
            return response.json();
        } else {
            // Se não for JSON, retornar um objeto padrão
            return { success: false, message: 'Erro na comunicação com o servidor' };
        }
    })
    .then(function(response) {
        if (response.success) {
            Swal.fire({
                title: 'Cadastro realizado com sucesso!',
                text: response.message,
                icon: 'success',
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#27ae60'
            }).then(function(result) {
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

                    // Fechar o modal de cadastro usando Bootstrap
                    const loginModalElement = document.getElementById('loginModal');
                    const modalInstance = bootstrap.Modal.getInstance(loginModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        // Se não houver instância, tentar fechar manualmente
                        loginModalElement.classList.remove('show');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();
                        loginModalElement.style.display = 'none';
                    }

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
    })
    .catch(function(error) {
        console.error('Erro na comunicação com o servidor durante o registro:', error);
        // Em caso de erro de comunicação, mostrar mensagem genérica
        Swal.fire({
            title: 'Erro de comunicação',
            text: 'Ocorreu um erro na comunicação com o servidor. Por favor, atualize a página e tente novamente.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    })
    .finally(function() {
        // Reabilitar o botão de cadastro após a requisição ser concluída (com sucesso ou erro)
        submitButton.disabled = false;
        submitButton.textContent = originalText;
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
                datesArray.forEach(function(dateObj) {
                    // Check if dateObj is a string (old format) or object (new format)
                    const date = typeof dateObj === 'string' ? dateObj : (dateObj.date || dateObj);
                    const dayElement = document.querySelector('.calendar-day[data-date="' + date + '"]');
                    if (dayElement) {
                        dayElement.classList.add('selected');
                    }
                });

                // Restore the dates in the global variable (handle both old and new format)
                if (typeof datesArray[0] === 'object' && datesArray[0].date) {
                    // New format: array of objects
                    window.selectedDates = datesArray;
                } else {
                    // Old format: array of strings - convert to new format
                    window.selectedDates = datesArray.map(function(date) {
                        // Format the date correctly using UTC to avoid timezone issues
                        const dateParts = date.split('-');
                        const year = parseInt(dateParts[0]);
                        const month = parseInt(dateParts[1]) - 1; // Month is zero-indexed
                        const day = parseInt(dateParts[2]);

                        // Criar uma data em UTC e extrair os componentes para formatação manual
                        const dateObj = new Date(Date.UTC(year, month, day));
                        const formattedDay = String(dateObj.getUTCDate()).padStart(2, '0');
                        const formattedMonth = String(dateObj.getUTCMonth() + 1).padStart(2, '0'); // Mês é zero-indexado
                        const formattedYear = dateObj.getUTCFullYear();

                        // Obter o nome do mês por extenso em português
                        const months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
                                       'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
                        const monthName = months[dateObj.getUTCMonth()];

                        const formattedDate = formattedDay + ' de ' + monthName + ' de ' + formattedYear;

                        return {
                            date: date,
                            price: 'R$ 0,00', // Default value, will be updated when calendar loads
                            formatted: formattedDate
                        };
                    });
                }

                // Restaurar os valores nos campos de formulário
                const selectedDatesInput = document.getElementById('selected_dates_input');
                const totalPriceInput = document.getElementById('total_price_input');
                const paymentPercentageInput = document.getElementById('payment_percentage_input');
                const observacoesInput = document.getElementById('observacoes');

                if (selectedDatesInput) selectedDatesInput.value = JSON.stringify(window.selectedDates.map(function(d) { return d.date; }));
                if (totalPriceInput) totalPriceInput.value = totalPrice;
                if (paymentPercentageInput) paymentPercentageInput.value = paymentPercentage;
                if (observacoesInput) observacoesInput.value = observacoes;

                // Atualizar o display das datas selecionadas
                updateSelectedDatesDisplay();

                // Mostrar o painel de detalhes da reserva
                document.getElementById('reserva-details').style.display = 'block';

                // Restaurar a seleção do radio button de pagamento
                if (paymentPercentage === '100') {
                    document.getElementById('payment_100').checked = true;
                } else {
                    document.getElementById('payment_50').checked = true;
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
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Autenticação Necessária</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="text-center text-muted">Para confirmar sua reserva, faça login ou cadastre-se.</p>

                <div id="login-form-container">
                    <form id="loginModalForm">
                        <div class="mb-3">
                            <label for="email_login_modal" class="form-label">E-mail:</label>
                            <input type="email" name="email_login_modal" id="email_login_modal" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="senha_login_modal" class="form-label">Senha:</label>
                            <input type="password" name="senha_login_modal" id="senha_login_modal" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">Entrar</button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="text-muted mb-2">Ou</p>
                        <a href="#" id="switch-to-register-btn" class="btn btn-outline-secondary w-100">Crie sua conta</a>
                    </div>
                </div>

                <div id="register-form-container" style="display: none; max-height: 60vh; overflow-y: auto;">
                    <form id="registerModalForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_registro_modal" class="form-label">Nome Completo:</label>
                                <input type="text" name="nome" class="form-control" id="nome_registro_modal" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="rg_registro_modal" class="form-label">RG:</label>
                                <input type="text" name="rg" class="form-control" id="rg_registro_modal" placeholder="00.000.000-X" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cpf_registro_modal" class="form-label">CPF:</label>
                                <input type="text" name="cpf" class="form-control" id="cpf_registro_modal" placeholder="000.000.000-00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="estado_civil_registro_modal" class="form-label">Estado Civil:</label>
                                <select name="estado_civil" class="form-select" id="estado_civil_registro_modal" required>
                                    <option value="">Selecione...</option>
                                    <option value="solteiro">Solteiro(a)</option>
                                    <option value="casado">Casado(a)</option>
                                    <option value="divorciado">Divorciado(a)</option>
                                    <option value="viuvo">Viúvo(a)</option>
                                    <option value="separado">Separado(a)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="rua_registro_modal" class="form-label">Rua:</label>
                                <input type="text" name="rua" class="form-control" id="rua_registro_modal" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="numero_registro_modal" class="form-label">Número:</label>
                                <input type="text" name="numero" class="form-control" id="numero_registro_modal" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="bairro_registro_modal" class="form-label">Bairro:</label>
                                <input type="text" name="bairro" class="form-control" id="bairro_registro_modal" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cep_registro_modal" class="form-label">CEP:</label>
                                <input type="text" name="cep" class="form-control" id="cep_registro_modal" placeholder="00000-000" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cidade_registro_modal" class="form-label">Cidade:</label>
                                <input type="text" name="cidade" class="form-control" id="cidade_registro_modal" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone_registro_modal" class="form-label">Telefone:</label>
                                <input type="tel" name="telefone" class="form-control" id="telefone_registro_modal" placeholder="(00) 00000-0000" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_nascimento_registro_modal" class="form-label">Data de Nascimento:</label>
                                <input type="date" name="data_nascimento" class="form-control" id="data_nascimento_registro_modal" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email_registro_modal" class="form-label">E-mail:</label>
                                <input type="email" name="email" class="form-control" id="email_registro_modal" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="senha_registro_modal" class="form-label">Senha:</label>
                                <input type="password" name="senha" class="form-control" id="senha_registro_modal" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirmar_senha_registro_modal" class="form-label">Confirmar Senha:</label>
                                <input type="password" name="confirmar_senha" class="form-control" id="confirmar_senha_registro_modal" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="text-muted mb-2">Já tem conta? <a href="#" id="switch-to-login-btn" class="text-decoration-none">Faça login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>

<script>
// Variável global para armazenar a data atual do calendário
let currentDate = new Date();

// Função para carregar dados da agenda (tornada global para ser acessível fora do pageinit)
function loadAgendaData(date) {
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const timestamp = new Date().getTime(); // Adiciona timestamp para evitar cache

    fetch('../php/cliente_agenda.php?action=get_month&month=' + month + '&year=' + year + '&t=' + timestamp)
        .then(function(response) {
            // Verificar se a resposta é JSON antes de tentar fazer o parse
            if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
                return response.json();
            } else {
                // Se não for JSON, retornar um objeto padrão
                return { success: false, prices: {} };
            }
        })
        .then(function(response) {
            if (response && response.success && typeof response.prices === 'object') {
                renderReservaCalendar(date, response.prices);
            } else {
                console.error("Falha ao carregar dados do calendário: ", response.message);
            }
        })
        .catch(function(error) {
            console.error("Erro de comunicação com o servidor: ", error);
        });
}

function renderReservaCalendar(date, agenda) {
    const month = date.getMonth();
    const year = date.getFullYear();
    document.getElementById('reservaCurrentMonthYear').textContent = date.toLocaleString('pt-BR', { month: 'long', year: 'numeric' });
    const firstDayOfMonth = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Armazenar as datas atualmente selecionadas antes de redesenhar o calendário
    const currentlySelectedDates = [];
    const selectedDayElements = document.querySelectorAll('.calendar-day.selected');
    selectedDayElements.forEach(function(element) {
        const date = element.dataset.date;
        if (date) {
            currentlySelectedDates.push(date);
        }
    });

    const calendar = document.getElementById('reservaCalendar');
    calendar.innerHTML = '';
    const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    weekDays.forEach(function(day) {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'day-header';
        dayHeader.textContent = day;
        calendar.appendChild(dayHeader);
    });
    for (let i = 0; i < firstDayOfMonth; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day';
        calendar.appendChild(emptyDay);
    }
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        const dayInfo = agenda[dateStr];

        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.dataset.date = dateStr;

        const dayNumberSpan = document.createElement('span');
        dayNumberSpan.className = 'day-number';
        dayNumberSpan.textContent = day;
        dayElement.appendChild(dayNumberSpan);

        if (dayInfo) {
            if (dayInfo.status === 'ativo') {
                dayElement.classList.add('available');
                const formattedPrice = parseFloat(dayInfo.preco.replace(/\./g, '').replace(',', '.')).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const priceDiv = document.createElement('div');
                priceDiv.className = 'price';
                priceDiv.textContent = formattedPrice;
                dayElement.appendChild(priceDiv);

                dayElement.dataset.price = formattedPrice;
            } else if (dayInfo.status === 'reservado') {
                dayElement.classList.add('reserved');
            } else if (dayInfo.status === 'pendente') {
                dayElement.classList.add('pending', 'reserved'); // Usar a mesma classe visual de reservado mas com estilo adicional
            }
        }

        // Restaurar a seleção se a data estava selecionada anteriormente
        if (currentlySelectedDates.includes(dateStr)) {
            dayElement.classList.add('selected');
        }

        calendar.appendChild(dayElement);
    }
}

// Função para atualizar o display das datas selecionadas (tornada global para ser acessível fora do pageinit)
// Variável global para armazenar as datas selecionadas no contexto do calendário
let selectedDates = [];

function updateSelectedDatesDisplay() {
    const datesList = document.getElementById('selected-dates-list');
    datesList.innerHTML = '';

    let totalPrice = 0;

    selectedDates.forEach(function(item, index) {
        const priceValue = parseFloat(item.price.replace('R$', '').replace(/\./g, '').replace(',', '.').trim());
        totalPrice += priceValue;

        const listItem = document.createElement('li');
        listItem.className = 'selected-date-item';
        listItem.innerHTML = '<span class="date-info">' + item.formatted + ' - ' + item.price + '</span>' +
            '<button class="remove-date-btn" data-index="' + index + '" style="margin-left: 10px; background: #e74c3c; color: white; border: none; border-radius: 4px; padding: 2px 6px; cursor: pointer;">Remover</button>';
        datesList.appendChild(listItem);
    });

    // Atualizar o valor total
    if (selectedDates.length > 0) {
        const formattedTotal = totalPrice.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('selected-total-price-text').textContent = formattedTotal;
        document.getElementById('selected_dates_input').value = JSON.stringify(selectedDates.map(function(d) { return d.date; }));
        document.getElementById('total_price_input').value = totalPrice;
        document.getElementById('reserva-details').style.display = 'block';

        // Atualizar o valor exibido no modal do PIX com base na porcentagem selecionada
        updatePixModalValue();
    } else {
        document.getElementById('reserva-details').style.display = 'none';
    }
}

// Função para atualizar o valor exibido no modal do PIX (tornada global para ser acessível fora do pageinit)
function updatePixModalValue() {
    if (selectedDates.length > 0) {
        const selectedPercentage = document.querySelector('input[name="payment_type"]:checked').value;
        const totalValue = parseFloat(document.getElementById('total_price_input').value);
        const paymentValue = (totalValue * parseInt(selectedPercentage)) / 100;
        const formattedValue = paymentValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        // Atualizar o valor exibido no modal do PIX se o modal estiver aberto
        document.getElementById('pix-valor').textContent = formattedValue;
    }
}

// Script para manipulação do calendário (mantendo a funcionalidade original)
document.addEventListener('DOMContentLoaded', function() {

    document.getElementById('reservaPrevMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        selectedDates = []; // Limpar seleções ao mudar de mês
        updateSelectedDatesDisplay();
        loadAgendaData(currentDate);
    });
    document.getElementById('reservaNextMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        selectedDates = []; // Limpar seleções ao mudar de mês
        updateSelectedDatesDisplay();
        loadAgendaData(currentDate);
    });

    // Manipulador único para ambos os eventos de clique
    document.addEventListener('click', function(e) {
        // Manipular clique nas datas disponíveis do calendário
        if (e.target.closest('#reservaCalendar .available')) {
            const clickedElement = e.target.closest('#reservaCalendar .available');
            const selectedDate = clickedElement.dataset.date;
            const selectedPrice = clickedElement.dataset.price;
            // Usar UTC para evitar problemas de fuso horário
            const dateParts = selectedDate.split('-');
            const year = parseInt(dateParts[0]);
            const month = parseInt(dateParts[1]) - 1; // Mês é zero-indexado
            const day = parseInt(dateParts[2]);

            // Criar uma data em UTC e extrair os componentes para formatação manual
            const dateObj = new Date(Date.UTC(year, month, day));
            const formattedDay = String(dateObj.getUTCDate()).padStart(2, '0');
            const formattedMonth = String(dateObj.getUTCMonth() + 1).padStart(2, '0'); // Mês é zero-indexado
            const formattedYear = dateObj.getUTCFullYear();

            // Obter o nome do mês por extenso em português
            const months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
                           'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
            const monthName = months[dateObj.getUTCMonth()];

            const formattedDate = formattedDay + ' de ' + monthName + ' de ' + formattedYear;

            // Verificar se a data já está selecionada
            const existingIndex = selectedDates.findIndex(function(item) {
                return item.date === selectedDate;
            });

            if (existingIndex >= 0) {
                // Remover a data se já estiver selecionada
                selectedDates.splice(existingIndex, 1);
                clickedElement.classList.remove('selected');
            } else {
                // Adicionar a data se ainda não estiver selecionada
                selectedDates.push({
                    date: selectedDate,
                    price: selectedPrice,
                    formatted: formattedDate
                });
                clickedElement.classList.add('selected');
            }

            updateSelectedDatesDisplay();
        }
        // Manipular clique nos botões de remover data
        else if (e.target.classList.contains('remove-date-btn')) {
            e.stopPropagation();
            const index = parseInt(e.target.dataset.index);
            if (index >= 0 && index < selectedDates.length) {
                const removedDate = selectedDates[index].date;
                selectedDates.splice(index, 1);

                // Remover a classe 'selected' da célula do calendário
                const calendarDay = document.querySelector('.calendar-day[data-date="' + removedDate + '"]');
                if (calendarDay) {
                    calendarDay.classList.remove('selected');
                }

                updateSelectedDatesDisplay();
            }
        }
    });

    // Atualizar o valor exibido no modal do PIX quando a porcentagem for alterada
    document.addEventListener('change', function(e) {
        if (e.target.matches('input[name="payment_type"]')) {
            const selectedPercentage = e.target.value;
            document.getElementById('payment_percentage_input').value = selectedPercentage;
            updatePixModalValue();
        }
    });


    loadAgendaData(currentDate);

    // Recarregar o calendário para garantir que ele apareça corretamente
    setTimeout(function() {
        loadAgendaData(currentDate);
    }, 200); // Pequeno delay adicional para garantir carregamento completo
});

// Função para garantir que o calendário é carregado corretamente em diferentes cenários
function initializeCalendarIfNeeded() {
    loadAgendaData(currentDate); // Usar a variável global currentDate
}

// Função global para verificar o status do pagamento - definida no escopo global para ser acessível de qualquer lugar
function checkPaymentStatus(datesOrIds) {
    window.verificationAttempts++;
    console.log('Tentativa de verificação #' + window.verificationAttempts);

    // Adiciona log para rastrear o processo
    console.log('Iniciando verificação de status do pagamento para:', datesOrIds);

    // Determinar se estamos lidando com datas ou IDs de reserva
    let paramValue = datesOrIds;
    let paramName = 'dates'; // padrão para datas

    // Verificar se o parâmetro parece ser uma string JSON de IDs de reserva
    try {
        const parsed = JSON.parse(datesOrIds);
        if (Array.isArray(parsed) && parsed.length > 0) {
            // É uma string JSON com array de IDs de reserva
            paramName = 'reservas_ids'; // usar o parâmetro correto para IDs de reserva
        }
    } catch (e) {
        // Não é JSON, manter o comportamento padrão para datas
    }

    // Chamar endpoint para verificar status das reservas
    fetch('../php/check_reservation_status.php', {
        method: 'POST',
        credentials: 'include', // Incluir credenciais (cookies de sessão) na requisição
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: paramName + '=' + encodeURIComponent(paramValue)
    })
    .then(function(response) {
        console.log('Resposta recebida do servidor:', response.status);
        // Verificar se a resposta é JSON antes de tentar fazer o parse
        if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
            return response.json();
        } else {
            // Se não for JSON, retornar um objeto padrão
            return { success: false, all_confirmed: false };
        }
    })
    .then(function(data) {
        console.log('Dados recebidos da verificação:', data);

        // Verificar se excedeu o número máximo de tentativas (verificar após obter resposta)
        if (data.success && !data.all_confirmed && window.verificationAttempts >= window.maxVerificationAttempts) {
            console.log('Número máximo de tentativas de verificação atingido, redirecionando para minhas reservas...');
            console.log('Detalhes do redirecionamento por limite de tentativas');
            const paymentStatusIndicator = document.getElementById('payment-status-indicator');
            if (paymentStatusIndicator) {
                paymentStatusIndicator.innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Tempo limite atingido. Redirecionando...</p>';
            }
            // Close the modal before redirecting
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

                // Exibir mensagem de sucesso primeiro, antes de fechar o modal
                Swal.fire({
                    title: 'Sucesso!',
                    text: 'Pagamento confirmado! Sua reserva está confirmada.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#27ae60'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        console.log('Usuário confirmou o alerta de sucesso');
                        // Fechar o modal após o usuário confirmar o alerta
                        const pixModalElement = document.getElementById('pixModal');
                        const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        } else {
                            // Se não houver instância, tentar fechar manualmente
                            pixModalElement.classList.remove('show');
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) backdrop.remove();
                            pixModalElement.style.display = 'none';
                        }

                        // Redirecionar para a página de minhas reservas após mostrar o alerta
                        console.log('Executando redirecionamento de sucesso para suas_reservas.php');
                        window.location.replace('suas_reservas.php?payment_confirmed=1');
                    }
                }).catch(function(error) {
                    console.error('Erro ao exibir o alerta de sucesso:', error);
                    // Fechar o modal mesmo em caso de erro no alerta
                    const pixModalElement = document.getElementById('pixModal');
                    const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        // Se não houver instância, tentar fechar manualmente
                        pixModalElement.classList.remove('show');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();
                        pixModalElement.style.display = 'none';
                    }

                    // Redirecionar mesmo se houver erro no alerta
                    console.log('Redirecionando devido a erro no alerta de sucesso');
                    window.location.replace('suas_reservas.php?payment_confirmed=1');
                });
            } else {
                // Ainda aguardando confirmação
                console.log('Aguardando confirmação de pagamento...', data);

                // Verificar se há informações sobre o status de pagamento para cada reserva
                if (data.reservations_status) {
                    console.log('Status detalhado das reservas:', data.reservations_status);

                    // Verificar se alguma reserva já foi confirmada
                    let hasConfirmedReservation = false;
                    for (const key in data.reservations_status) {
                        const statusInfo = data.reservations_status[key];
                        if (typeof statusInfo === 'object' && statusInfo.status === 'confirmado' && statusInfo.payment_confirmed_at) {
                            hasConfirmedReservation = true;
                            break;
                        } else if (typeof statusInfo === 'string' && statusInfo === 'confirmado') {
                            hasConfirmedReservation = true;
                            break;
                        }
                    }

                    // Verificar também o campo has_recent_confirmation se estiver presente
                    if (data.has_recent_confirmation || hasConfirmedReservation || (data.total_confirmed_count > 0)) {
                        // Existe pelo menos uma reserva confirmada recentemente, atualizar o status
                        const paymentStatusIndicator = document.getElementById('payment-status-indicator');
                        if (paymentStatusIndicator) {
                            paymentStatusIndicator.innerHTML = '<p style="color: #27ae60; font-size: 10px;">Pagamento detectado! Atualizando status...</p>';
                        }

                        // Forçar o redirecionamento para mostrar a confirmação na tela de reservas
                        setTimeout(function() {
                            // Exibir mensagem de sucesso primeiro, antes de fechar o modal
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'Pagamento confirmado! Sua reserva está confirmada.',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#27ae60'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    console.log('Usuário confirmou o alerta de sucesso');
                                    // Fechar o modal após o usuário confirmar o alerta
                                    const pixModalElement = document.getElementById('pixModal');
                                    const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
                                    if (modalInstance) {
                                        modalInstance.hide();
                                    } else {
                                        // Se não houver instância, tentar fechar manualmente
                                        pixModalElement.classList.remove('show');
                                        const backdrop = document.querySelector('.modal-backdrop');
                                        if (backdrop) backdrop.remove();
                                        pixModalElement.style.display = 'none';
                                    }

                                    // Redirecionar para a página de minhas reservas após mostrar o alerta
                                    console.log('Executando redirecionamento de sucesso para suas_reservas.php');
                                    window.location.replace('suas_reservas.php?payment_confirmed=1');
                                }
                            });
                        }, 2000); // Aguardar 2 segundos antes de redirecionar

                    } else {
                        const paymentStatusIndicator = document.getElementById('payment-status-indicator');
                        if (paymentStatusIndicator) {
                            paymentStatusIndicator.innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Aguardando confirmação de pagamento...</p>';
                        }
                    }
                } else {
                    const paymentStatusIndicator = document.getElementById('payment-status-indicator');
                    if (paymentStatusIndicator) {
                        paymentStatusIndicator.innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Aguardando confirmação de pagamento...</p>';
                    }
                }

                // Verificar novamente após 5 segundos (aumentei o intervalo para reduzir chamadas excessivas)
                setTimeout(function() {
                    // Verificar se o modal ainda está aberto antes de continuar verificando
                    const pixModalElement = document.getElementById('pixModal');
if (pixModalElement.classList.contains('show')) {
                        // Verificar se excedeu o número máximo de tentativas
                        if (window.verificationAttempts >= window.maxVerificationAttempts) {
                            console.log('Número máximo de tentativas de verificação atingido (dentro do timeout), redirecionando para minhas reservas...');
                            const paymentStatusIndicator = document.getElementById('payment-status-indicator');
                            if (paymentStatusIndicator) {
                                paymentStatusIndicator.innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Tempo limite atingido. Redirecionando...</p>';
                            }
                            // Close the modal before redirecting
                            const pixModalElement = document.getElementById('pixModal');
const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
if (modalInstance) {
    modalInstance.hide();
} else {
    // Se não houver instância, tentar fechar manualmente
    pixModalElement.classList.remove('show');
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
    pixModalElement.style.display = 'none';
}
                            // Redirecionar imediatamente sem delay para garantir o redirecionamento
                            console.log('Efetuando redirecionamento para suas_reservas.php (timeout limite de tentativas)');
                            window.location.replace('suas_reservas.php');
                        } else {
                            console.log('Continuando verificação, nova tentativa agendada');
                            checkPaymentStatus(datesOrIds);
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
            document.getElementById('payment-status-indicator').innerHTML = '<p style="color: #ecf0f1; font-size: 10px;">Erro na verificação: ' + data.message + '</p>';
            // Continuar verificando mesmo em caso de erro, para lidar com possíveis falhas temporárias
            setTimeout(function() {
                // Verificar se o modal ainda está aberto antes de continuar verificando
                const pixModalElement = document.getElementById('pixModal');
if (pixModalElement.classList.contains('show')) {
                    console.log('Continuando verificação apesar do erro, nova tentativa agendada');
                    checkPaymentStatus(datesOrIds);
                } else {
                    console.log('Modal fechado (após erro), parando verificação automática');
                }
            }, 5000);
        }
    })
    .catch(function(error) {
        console.error('Erro completo ao verificar status do pagamento:', error);
        console.error('Erro de comunicação com o servidor ao verificar pagamento:', error);
        console.log('Detalhes do erro:', error.message, error.stack);
        const confirmPaymentBtn = document.getElementById('confirm-payment-btn');
confirmPaymentBtn.textContent = 'Já paguei, verificar!';
confirmPaymentBtn.disabled = false;

        // Mesmo em caso de erro de comunicação, continuar verificando
        // Verificar se o modal ainda está aberto antes de continuar verificando
        const pixModalElement = document.getElementById('pixModal');
if (pixModalElement.classList.contains('show')) {
            console.log('Continuando verificação apesar do erro de comunicação');
            setTimeout(function() {
                checkPaymentStatus(datesOrIds);
            }, 5000);
        } else {
            console.log('Modal fechado (após erro de comunicação), não continuando verificação');
        }
    });
}

// Eventos para o modal
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('cancel-reservation-btn')?.addEventListener('click', function() {
        const pixModalElement = document.getElementById('pixModal');
        const modalInstance = bootstrap.Modal.getInstance(pixModalElement);
        if (modalInstance) {
            modalInstance.hide();
        } else {
            // Se não houver instância, tentar fechar manualmente
            pixModalElement.classList.remove('show');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
            pixModalElement.style.display = 'none';
        }
        // Redirect to suas_reservas.php when closing the modal after payment attempt
        window.location.replace('suas_reservas.php');
    });

    // Event listener para quando o modal é fechado
    const pixModalElement = document.getElementById('pixModal');
    pixModalElement.addEventListener('hidden.bs.modal', function () {
        // Redirect to suas_reservas.php when closing the modal after payment attempt
        window.location.replace('suas_reservas.php');
    });

    // Variáveis globais para controle de verificação de pagamento (tornadas globais para serem acessíveis fora do document.ready)
    window.verificationAttempts = 0; // Contador de tentativas de verificação
    window.maxVerificationAttempts = 24; // Máximo de 24 tentativas (aproximadamente 2 minutos com intervalos de 5 segundos)

    // Eventos para o modal de login
    document.querySelectorAll('#close-login-modal, .btn-close').forEach(function(element) {
        element.addEventListener('click', function() {
            const loginModalElement = document.getElementById('loginModal');
            const modalInstance = bootstrap.Modal.getInstance(loginModalElement);
            if (modalInstance) {
                modalInstance.hide();
            } else {
                // Se não houver instância, tentar fechar manualmente
                loginModalElement.classList.remove('show');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
                loginModalElement.style.display = 'none';
            }
        });
    });

    // Alternar entre login e registro
    document.getElementById('switch-to-register-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('login-form-container').style.display = 'none';
        document.getElementById('register-form-container').style.display = 'block';

        // Atualizar o foco para o primeiro campo do formulário de registro
        document.getElementById('nome_registro_modal').focus();
    });

    document.getElementById('switch-to-login-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('register-form-container').style.display = 'none';
        document.getElementById('login-form-container').style.display = 'block';

        // Atualizar o foco para o primeiro campo do formulário de login
        document.getElementById('email_login_modal').focus();
    });

    // Submissão do formulário de login no modal
    document.getElementById('loginModalForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        loginModal();
    });

    // Submissão do formulário de registro no modal
    document.getElementById('registerModalForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        registerModal();
    });

    // Manipulador para o botão de cópia da chave PIX
    document.getElementById('copy-pix-key-btn')?.addEventListener('click', function() {
        const pixKeyInput = document.getElementById('pix-key-input');
        if (pixKeyInput) {
            // Try to use modern Clipboard API first (for better iOS support)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(pixKeyInput.value).then(function() {
                    // Mostrar feedback visual
                    const originalIcon = this.querySelector('i')?.className;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(function() {
                        this.innerHTML = '<i class="' + originalIcon + '"></i>';
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
                navigator.clipboard.writeText(inputElement.value).then(function() {
                    successful = true;
                }).catch(function() {
                    // If clipboard API fails, fall back to execCommand
                    successful = document.execCommand('copy');
                });
            } else {
                // Use older execCommand method
                successful = document.execCommand('copy');
            }

            if (successful) {
                // Mostrar feedback visual
                const copyPixKeyBtn = document.getElementById('copy-pix-key-btn');
const originalText = copyPixKeyBtn.innerHTML;
copyPixKeyBtn.innerHTML = '<i class="fas fa-check"></i>';
setTimeout(function() {
    copyPixKeyBtn.innerHTML = originalText;
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
                    didOpen: function() {
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
                didOpen: function() {
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

    // Manipulador para o botão de confirmação de pagamento
    document.removeEventListener('click', handleConfirmPaymentClick);
    document.addEventListener('click', handleConfirmPaymentClick);

    function handleConfirmPaymentClick(e) {
        if (e.target.id === 'confirm-payment-btn') {
            // Verificar se a verificação automática já começou (botão foi escondido)
            if (this.hidden || this.style.display === 'none') {
                // A verificação automática já está em andamento, não fazer nada
                return;
            }

            // Atualizar o texto e desabilitar o botão para evitar múltiplos cliques
            this.textContent = 'Verificando...';
            this.disabled = true;

            // Mostrar indicador de status
            document.getElementById('payment-status-indicator').style.display = 'block';

            // Obter os IDs de reserva armazenados no modal
            const pixModalElement = document.getElementById('pixModal');
            const reservationIds = JSON.parse(pixModalElement.dataset.reservationIds || '[]');

            // Resetar contador de tentativas antes de iniciar nova verificação
            window.verificationAttempts = 0;

            // Iniciar verificação automática do status do pagamento
            if (reservationIds && reservationIds.length > 0) {
                checkPaymentStatus(JSON.stringify(reservationIds)); // Passar os IDs de reserva como string JSON
            } else {
                // Se não tivermos os IDs de reserva, tentar usar as datas selecionadas como fallback
                const selectedDates = document.getElementById('selected_dates_input').value;
                if (selectedDates) {
                    checkPaymentStatus(selectedDates);
                } else {
                    console.error('Nenhuma informação de reserva disponível para verificar pagamento');
                    const confirmPaymentBtn = document.getElementById('confirm-payment-btn');
                    if (confirmPaymentBtn) {
                        confirmPaymentBtn.textContent = 'Já paguei, verificar!';
                        confirmPaymentBtn.disabled = false;
                    }
                }
            }
        }
    }

}); // Fechar o DOMContentLoaded event listener que começa na linha 1768

// Adicionar evento para permitir cópia do QR Code ao clicar nele
document.addEventListener('click', function(e) {
    if (e.target.id === 'pix-qr-image') {
        copyQRCodeImage();
    }
});

// Associar o evento de clique ao botão após o carregamento da página e garantir que o calendário seja carregado
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmReservaBtn')?.addEventListener('click', function() {
        handleReservaClick();
    });

    // Garantir que o calendário é carregado quando a página é mostrada
    setTimeout(function() {
        initializeCalendarIfNeeded();
    }, 50);
});

// Garantir que o calendário também seja inicializado quando a página for ativada
document.addEventListener('DOMContentLoaded', function() {
    // Isso ajuda em casos onde o pageinit não é chamado novamente
    setTimeout(function() {
        initializeCalendarIfNeeded();
    }, 100); // Pequeno delay para garantir que o DOM esteja pronto
});

// Manipulador para o botão de logout
document.addEventListener('DOMContentLoaded', function() {
    // Usar delegate para o botão de logout, já que ele é definido no HTML
    document.addEventListener('click', function(e) {
        if (e.target.id === 'logout-link-reserva') {
            e.preventDefault();

            fetch('../php/logout.php', {
                method: 'POST',
                credentials: 'include', // Incluir credenciais (cookies de sessão) na requisição
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(function(response) {
                // Verificar se a resposta é JSON antes de tentar fazer o parse
                if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
                    return response.json();
                } else {
                    // Se não for JSON, retornar um objeto padrão
                    return { success: false, message: '' };
                }
            })
            .then(function(response) {
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
            })
            .catch(function(error) {
                // Mesmo em caso de erro, redirecionar para a página inicial
                window.location.href = 'reserva.php';
            });
        }
    }); // Fechar o event listener de clique interno

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
}); // Fechar o DOMContentLoaded event listener

// Função adicional para garantir o carregamento do calendário em diferentes cenários
document.addEventListener('DOMContentLoaded', function() {
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

// Document ready function to ensure everything is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Esta função é chamada quando a página é carregada
    // Podemos usar isso para inicializar variáveis se necessário
});

// Função para formatar CPF enquanto o usuário digita
document.addEventListener('input', function(e) {
    if (e.target.id === 'cpf_registro_modal') {
        let value = e.target.value.replace(/\D/g, ''); // Remover tudo que não é dígito
        if (value.length > 11) value = value.slice(0, 11); // Limitar a 11 dígitos

        if (value.length > 9) {
            // Formato: XXX.XXX.XXX-XX
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        } else if (value.length > 6) {
            // Formato: XXX.XXX.XXX
            value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
        } else if (value.length > 3) {
            // Formato: XXX.XXX
            value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
        }

        e.target.value = value;
    }
});

// Função para formatar CEP enquanto o usuário digita
document.addEventListener('input', function(e) {
    if (e.target.id === 'cep_registro_modal') {
        let value = e.target.value.replace(/\D/g, ''); // Remover tudo que não é dígito
        if (value.length > 8) value = value.slice(0, 8); // Limitar a 8 dígitos

        if (value.length > 5) {
            // Formato: XXXXX-XXX
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        }

        e.target.value = value;
    }
});

// Função para formatar telefone enquanto o usuário digita
document.addEventListener('input', function(e) {
    if (e.target.id === 'telefone_registro_modal') {
        let value = e.target.value.replace(/\D/g, ''); // Remover tudo que não é dígito
        if (value.length > 11) value = value.slice(0, 11); // Limitar a 11 dígitos

        if (value.length > 6) {
            // Formato: (XX) XXXXX-XXXX
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length > 2) {
            // Formato: (XX) XXXX
            value = value.replace(/(\d{2})(\d{4,5})/, '($1) $2');
        } else if (value.length > 0) {
            // Formato: (XX
            value = value.replace(/(\d{2})/, '($1)');
        }

        e.target.value = value;
    }
});
</script>

</body>
</html>
