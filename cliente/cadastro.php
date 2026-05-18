<?php
// Verificar se o usuário está autenticado
session_start();

// Verificar se o usuário está logado e se o usuário ainda existe no banco de dados
if (isset($_SESSION['user_id'])) {
    // Conectar ao banco de dados para verificar se o usuário ainda existe
    require_once '../php/db_connect.php';

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Usuário ainda existe no banco, redirecionar para reserva
        header("Location: https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php");
        exit();
    } else {
        // Usuário não existe mais no banco, limpar a sessão e permitir cadastro
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
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        #cadastroPageCliente header h1 {
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

<div id="cadastroPageCliente" class="container-fluid d-flex flex-column" style="min-height: 100vh;">
    <header class="bg-white text-dark py-3">
        <div class="container">
            <h1 class="text-center mb-0">Cadastro</h1>
        </div>
    </header>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center" style="padding-bottom: 60px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10 col-sm-12">
                    <div class="card shadow">
                        <div class="card-body p-4" style="min-height: calc(100vh - 200px);">
                            <h2 class="card-title text-center mb-4">Crie sua Conta</h2>
                            <form id="cadastroForm" method="post" action="../php/cliente_cadastro.php" onsubmit="return validateAndSubmit(event)">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nome" class="form-label">Nome Completo:</label>
                                        <input type="text" name="nome" class="form-control" id="nome" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="rg" class="form-label">RG:</label>
                                        <input type="text" name="rg" class="form-control" id="rg" placeholder="00.000.000-X" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cpf" class="form-label">CPF:</label>
                                        <input type="text" name="cpf" class="form-control" id="cpf" placeholder="000.000.000-00" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="estado_civil" class="form-label">Estado Civil:</label>
                                        <select name="estado_civil" class="form-select" id="estado_civil" required>
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
                                        <label for="rua" class="form-label">Rua:</label>
                                        <input type="text" name="rua" class="form-control" id="rua" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="numero" class="form-label">Número:</label>
                                        <input type="text" name="numero" class="form-control" id="numero" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="bairro" class="form-label">Bairro:</label>
                                        <input type="text" name="bairro" class="form-control" id="bairro" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cep" class="form-label">CEP:</label>
                                        <input type="text" name="cep" class="form-control" id="cep" placeholder="00000-000" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cidade" class="form-label">Cidade:</label>
                                        <input type="text" name="cidade" class="form-control" id="cidade" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefone" class="form-label">Telefone:</label>
                                        <input type="tel" name="telefone" class="form-control" id="telefone" placeholder="(00) 00000-0000" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="data_nascimento" class="form-label">Data de Nascimento:</label>
                                        <input type="date" name="data_nascimento" class="form-control" id="data_nascimento" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">E-mail:</label>
                                        <input type="email" name="email" class="form-control" id="email" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="senha" class="form-label">Senha:</label>
                                        <input type="password" name="senha" class="form-control" id="senha" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirmar_senha" class="form-label">Confirmar Senha:</label>
                                        <input type="password" name="confirmar_senha" class="form-control" id="confirmar_senha" required>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" style="background-color: #2c3e50; border-color: #2c3e50;" id="submitBtn">Cadastrar</button>
                                </div>
                            </form>
                        </div>

                        <div class="card-footer bg-light text-center py-3">
                            <p class="mb-0">Já tem conta?
                                <a href="login.php" class="btn btn-outline-primary btn-sm ms-2" style="border-color: #2c3e50; color: #2c3e50;">Faça login</a>
                            </p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Atualiza a página uma vez após 2 segundos quando vem de outra página
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se veio de outra página usando o referrer
    var referrer = document.referrer;
    var currentUrl = window.location.href;

    // Se veio de outra página (referrer existe e é diferente da página atual)
    if (referrer && referrer !== '' && !referrer.includes('cadastro.php')) {
        // Verifica se já foi atualizada recentemente para evitar loops
        if (!sessionStorage.getItem('refreshFromOtherPage')) {
            // Marca que já foi atualizada
            sessionStorage.setItem('refreshFromOtherPage', 'true');

            // Atualiza a página após 2 segundos
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        }
    } else {
        // Se veio da mesma página, remove a marcação para permitir futuras atualizações
        sessionStorage.removeItem('refreshFromOtherPage');
    }

    // Limpa parâmetros da URL
    if (window.location.search) {
        history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>
<script>
// Função para validar e submeter o formulário
async function validateAndSubmit(event) {
    event.preventDefault(); // Impede o envio padrão do formulário

    const form = document.getElementById('cadastroForm');
    const submitBtn = document.getElementById('submitBtn');

    // Validação das senhas
    const senha = document.getElementById('senha').value;
    const confirmarSenha = document.getElementById('confirmar_senha').value;

    if (senha !== confirmarSenha) {
        Swal.fire({
            title: 'Erro!',
            text: 'As senhas não coincidem.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#e74c3c'
        });
        return false;
    }

    // Desabilitar o botão para evitar múltiplos envios
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processando...';

    try {
        // Coletar dados do formulário
        const formData = new FormData(form);

        // Exibir mensagem de carregamento
        Swal.fire({
            title: 'Processando...',
            text: 'Aguarde enquanto seu cadastro é realizado.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar dados via fetch
        const response = await fetch('../php/cliente_cadastro.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        // Fechar o loading
        Swal.close();

        if (result.success) {
            Swal.fire({
                title: 'Sucesso!',
                text: result.message || 'Cadastro realizado com sucesso!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Redirecionar após o tempo do timer
                window.location.href = result.redirect || '../cliente/reserva.php';
            });

            // Redirecionar automaticamente após 2 segundos
            setTimeout(() => {
                window.location.href = result.redirect || '../cliente/reserva.php';
            }, 2000);
        } else {
            Swal.fire({
                title: 'Erro!',
                text: result.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
            // Reabilitar o botão
            submitBtn.disabled = false;
            submitBtn.textContent = 'Cadastrar';
        }
    } catch (error) {
        // Fechar o loading em caso de erro
        Swal.close();

        Swal.fire({
            title: 'Erro!',
            text: 'Ocorreu um erro na comunicação com o servidor.',
            icon: 'error',
            confirmButtonText: 'OK'
        });

        // Reabilitar o botão
        submitBtn.disabled = false;
        submitBtn.textContent = 'Cadastrar';
    }

    return false; // Impede o envio padrão do formulário
}

// Função para formatar CPF enquanto o usuário digita
const cpfInput = document.getElementById('cpf');
if (cpfInput) {
    cpfInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Remover tudo que não é dígito
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

        this.value = value;
    });
}

// Função para formatar CEP enquanto o usuário digita
const cepInput = document.getElementById('cep');
if (cepInput) {
    cepInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Remover tudo que não é dígito
        if (value.length > 8) value = value.slice(0, 8); // Limitar a 8 dígitos

        if (value.length > 5) {
            // Formato: XXXXX-XXX
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        }

        this.value = value;
    });
}

// Função para formatar telefone enquanto o usuário digita
const telefoneInput = document.getElementById('telefone');
if (telefoneInput) {
    telefoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Remover tudo que não é dígito
        if (value.length > 11) value = value.slice(0, 11); // Limitar a 11 dígitos

        if (value.length > 6) {
            // Formato: (XX) XXXXX-XXXX
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length > 2) {
            // Formato: (XX) XXXX
            value = value.replace(/(\d{2})(\d{4,5})/, '($1) $2');
        } else if (value.length > 0) {
            // Formato: (XX
            value = value.replace(/(\d{2})/, '($1');
        }

        this.value = value;
    });
}
</script>



</body>
</html>