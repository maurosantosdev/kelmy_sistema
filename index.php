<?php
    @require __DIR__ . '/php/db_connect.php';

    // Iniciar sessão e verificar login
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $info_text = "Bem-vindo! As informações da chácara estarão disponíveis em breve.";
    $media_files = [];

    if (isset($conn) && $conn instanceof mysqli) {
        $result = $conn->query("SELECT texto_info, fotos FROM informacoes WHERE id = 1");
        if ($result && $result->num_rows > 0) {
            $info = $result->fetch_assoc();
            $decoded_media = json_decode($info['fotos'] ?? '[]', true);
            $media_files = is_array($decoded_media) ? $decoded_media : [];
            $info_text = nl2br(htmlspecialchars($info['texto_info'] ?? ''));
        } else {
            $info_text = "Informações não encontradas. Por favor, configure os dados na área administrativa.";
        }
    } else {
        $info_text = "Erro Crítico: Não foi possível conectar ao banco de dados.";
    }
    
    // Verificar se o usuário está logado
    $is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <!-- MUDANÇA: Nome atualizado no título da página -->
    <title>Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="cliente/style.css">
</head>
<body>
<div data-role="page" id="infoPageCliente">
    <div data-role="header" data-position="fixed">
        <!-- MUDANÇA: Nome atualizado no cabeçalho -->
        <h1 id="header-title-index"><?php echo $is_logged_in ? 'Olá ' . htmlspecialchars($_SESSION['user_name'] ?? 'usuário') : 'Chácara Recanto do Sossego'; ?></h1>
        <?php if ($is_logged_in): ?>
            <a href="#" id="logout-link-index" class="ui-btn-right ui-btn ui-corner-all">Sair</a>
        <?php else: ?>
            <a href="cliente/login.php" class="ui-btn-right ui-btn ui-corner-all">Entrar</a>
        <?php endif; ?>
    </div>
    <div role="main" class="ui-content">

        <div class="card">
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <?php if (!empty($media_files)): ?>
                        <?php foreach ($media_files as $file): ?>
                            <div class="swiper-slide">
                                <?php
                                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    $file_path = "uploads/" . htmlspecialchars($file);
                                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])):
                                ?>
                                    <!-- MUDANÇA: Nome atualizado no texto alternativo da imagem -->
                                    <img src="<?php echo $file_path; ?>" alt="Foto da Chácara Recanto do Sossego">
                                <?php elseif ($extension == 'mp4'): ?>
                                    <video src="<?php echo $file_path; ?>" playsinline muted loop autoplay></video>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="swiper-slide">
                             <!-- MUDANÇA: Nome atualizado na imagem padrão -->
                            <img src="https://via.placeholder.com/600x300/2c3e50/ffffff?text=Chácara+Recanto+do+Sossego" alt="Bem-vindo">
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Botões de navegação do carrossel -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>

        <div class="card">
            <h2>Bem-vindo</h2>
            <p><?php echo $info_text; ?></p>
        </div>

        <div class="card">
             <h2>Contato</h2>
            <div class="contact-buttons">
                <a href="https://wa.me/5595991244142" target="_blank" class="contact-btn">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp
                </a>
                <a href="tel:+5595991244142" class="contact-btn">
                    <i class="fas fa-phone-alt"></i>
                    Ligar
                </a>
                <a href="#" class="contact-btn" onclick="openInstagram(); return false;">
                    <i class="fab fa-instagram"></i>
                    Instagram
                </a>
            </div>
        </div>

    </div>
    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-icon="home" class="ui-btn-active ui-state-persist">Inicio</a></li>
                <li><a href="cliente/reserva.php" data-icon="grid" data-ajax="false">Reserve</a></li>
                <li><a href="cliente/suas_reservas.php" data-icon="calendar" data-ajax="false">Reservas</a></li>
                <li><a href="cliente/perfil.php" data-icon="user" data-ajax="false">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/jquery.mobile-1.4.5.min.js"></script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="cliente/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Função para abrir o Instagram no aplicativo
function openInstagram() {
    // URL do Instagram
    const instagramUrl = 'https://www.instagram.com/chacara_r_s';
    // URL do protocolo do Instagram para abrir no aplicativo
    const instagramAppUrl = 'instagram://user?username=chacara_r_s';
    
    // Para iOS
    if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        // Tenta abrir o aplicativo do Instagram
        window.location = instagramAppUrl;
        // Se o aplicativo não abrir, abre no navegador
        setTimeout(() => {
            window.open(instagramUrl, '_blank');
        }, 100);
    } 
    // Para Android
    else if (/Android/.test(navigator.userAgent)) {
        // Tenta abrir o aplicativo do Instagram
        window.location = instagramAppUrl;
        // Se o aplicativo não abrir, abre no navegador
        setTimeout(() => {
            window.open(instagramUrl, '_blank');
        }, 100);
    } 
    // Para outros dispositivos (desktop, etc.)
    else {
        // Abre no navegador
        window.open(instagramUrl, '_blank');
    }
}

// Função para carregar o nome do usuário e atualizar o cabeçalho
function loadUserName() {
    $.ajax({
        url: 'php/get_user_info.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.user_name) {
                document.getElementById('header-title-index').textContent = 'Olá ' + response.user_name;
            } else {
                document.getElementById('header-title-index').textContent = 'Chácara Recanto do Sossego';
            }
        },
        error: function() {
            document.getElementById('header-title-index').textContent = 'Chácara Recanto do Sossego';
        }
    });
}

// Manipulador para o botão de logout na página de informações
$(document).on('pageinit', '#infoPageCliente', function() {
    // Carregar o nome do usuário para atualizar o cabeçalho, se estiver logado
    <?php if ($is_logged_in): ?>
    loadUserName();
    <?php endif; ?>
    
    $('#logout-link-index').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Limpar possíveis dados da sessão no frontend e redirecionar de forma limpa
                    window.location.replace('cliente/login.php');
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
                window.location.href = 'cliente/login.php';
            }
        });
    });
});
</script>
</body>
</html>