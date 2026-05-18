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
    <!-- jQuery Mobile CSS - Will be replaced in React/Next.js migration -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="cliente/style.css">
    <style>
        #header-title-index {
            color: #34495e !important;
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
<div id="infoPageCliente" class="container-fluid d-flex flex-column" style="min-height: 100vh;">
    <header class="bg-white text-dark py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0" id="header-title-index"><?php echo $is_logged_in ? 'Olá ' . htmlspecialchars($_SESSION['user_name'] ?? 'usuário') : 'Chácara Recanto do Sossego'; ?></h1>
                <?php if ($is_logged_in): ?>
                    <a href="#" id="logout-link-index" class="btn btn-light btn-sm">Sair</a>
                <?php else: ?>
                    <a href="cliente/login.php" class="btn btn-light btn-sm">Entrar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="flex-grow-1 py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card mb-4">
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
                                                <img src="<?php echo $file_path; ?>" class="d-block w-100" alt="Foto da Chácara Recanto do Sossego">
                                            <?php elseif ($extension == 'mp4'): ?>
                                                <video src="<?php echo $file_path; ?>" class="d-block w-100" playsinline muted loop autoplay></video>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="swiper-slide">
                                         <!-- MUDANÇA: Nome atualizado na imagem padrão -->
                                        <img src="https://via.placeholder.com/600x300/2c3e50/ffffff?text=Chácara+Recanto+do+Sossego" class="d-block w-100" alt="Bem-vindo">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Botões de navegação do carrossel -->
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="card-title">Bem-vindo</h2>
                            <p class="card-text"><?php echo $info_text; ?></p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="card-title">Contato</h2>
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <a href="https://wa.me/5595991244142" target="_blank" class="btn btn-outline-primary d-block">
                                        <i class="fab fa-whatsapp"></i><br>
                                        WhatsApp
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="tel:+5595991244142" class="btn btn-outline-primary d-block">
                                        <i class="fas fa-phone-alt"></i><br>
                                        Ligar
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="#" class="btn btn-outline-primary d-block" onclick="openInstagram(); return false;">
                                        <i class="fab fa-instagram"></i><br>
                                        Instagram
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="fixed-bottom bg-dark text-white py-2">
        <div class="container-fluid px-0">
            <nav class="row g-0 text-center">
                <div class="col-3 py-2">
                    <a href="../index.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center active">
                        <i class="fas fa-home mb-1"></i>
                        <small>Inicio</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="cliente/reserva.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-calendar-alt mb-1"></i>
                        <small>Reserve</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="cliente/suas_reservas.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-calendar mb-1"></i>
                        <small>Reservas</small>
                    </a>
                </div>
                <div class="col-3 py-2">
                    <a href="cliente/perfil.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-user mb-1"></i>
                        <small>Perfil</small>
                    </a>
                </div>
            </nav>
        </div>
    </footer>
</div>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    fetch('php/get_user_info.php')
        .then(response => response.json())
        .then(response => {
            if (response.success && response.user_name) {
                document.getElementById('header-title-index').textContent = 'Olá ' + response.user_name;
            } else {
                document.getElementById('header-title-index').textContent = 'Chácara Recanto do Sossego';
            }
        })
        .catch(() => {
            document.getElementById('header-title-index').textContent = 'Chácara Recanto do Sossego';
        });
}

// Manipulador para o botão de logout na página de informações
document.addEventListener('DOMContentLoaded', function() {
    // Carregar o nome do usuário para atualizar o cabeçalho, se estiver logado
    <?php if ($is_logged_in): ?>
    loadUserName();
    <?php endif; ?>

    const logoutLink = document.getElementById('logout-link-index');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();

            fetch('php/logout.php', {
                method: 'POST',
            })
            .then(response => response.json())
            .then(response => {
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
            })
            .catch(() => {
                // Mesmo em caso de erro, redirecionar para login
                window.location.href = 'cliente/login.php';
            });
        });
    }

    // Initialize Swiper carousel after DOM is loaded
    setTimeout(function() {
        if (typeof Swiper !== 'undefined') {
            const swiper = new Swiper('.swiper-container', {
                // Optional parameters
                direction: 'horizontal',
                loop: true,

                // Navigation arrows
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },

                // And if we need scrollbar
                scrollbar: {
                    el: '.swiper-scrollbar',
                },

                // Enable autoplay
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },

                // Enable zoom
                zoom: true,
            });
        }
    }, 100); // Small delay to ensure Swiper is loaded
});
</script>
</body>
</html>