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
    <title>Seu Perfil - Chácara Recanto do Sossego</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profile-styles.css">
    <style>
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

<div id="perfilPageCliente" class="container-fluid">
    <header class="bg-white text-dark py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0" id="header-title-perfil">Seu Perfil</h1>
                <a href="#" id="logout-link" class="btn btn-light btn-sm">Sair</a>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 30px; padding-bottom: 80px;">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(substr(htmlspecialchars($_SESSION['user_name'] ?? 'C'), 0, 1)); ?></div>
                <div class="profile-info">
                    <h1 class="profile-name" id="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Carregando...'); ?></h1>
                    <p class="profile-email" id="user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'Carregando...'); ?></p>
                </div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <h3 class="stat-value" id="user-phone">Carregando...</h3>
                    <p class="stat-label">Telefone</p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-value" id="user-cpf">Carregando...</h3>
                    <p class="stat-label">CPF</p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-value" id="user-rg">Carregando...</h3>
                    <p class="stat-label">RG</p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-value" id="user-data-nascimento">Carregando...</h3>
                    <p class="stat-label">Nascimento</p>
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-section">
                    <h3>Dados Pessoais</h3>
                    <div class="profile-details">
                        <div class="profile-detail">
                            <p class="profile-detail-label">Nome</p>
                            <p class="profile-detail-value" id="detail-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Carregando...'); ?></p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">E-mail</p>
                            <p class="profile-detail-value" id="detail-user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'Carregando...'); ?></p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">Telefone</p>
                            <p class="profile-detail-value" id="detail-user-phone">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">CPF</p>
                            <p class="profile-detail-value" id="detail-user-cpf">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">RG</p>
                            <p class="profile-detail-value" id="detail-user-rg">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">Data de Nascimento</p>
                            <p class="profile-detail-value" id="detail-user-data-nascimento">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">Estado Civil</p>
                            <p class="profile-detail-value" id="detail-user-estado-civil">Carregando...</p>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Endereço</h3>
                    <div class="profile-details">
                        <div class="profile-detail">
                            <p class="profile-detail-label">Endereço</p>
                            <p class="profile-detail-value" id="detail-user-endereco">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">CEP</p>
                            <p class="profile-detail-value" id="detail-user-cep">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">Cidade</p>
                            <p class="profile-detail-value" id="detail-user-cidade">Carregando...</p>
                        </div>
                        <div class="profile-detail">
                            <p class="profile-detail-label">Data de Cadastro</p>
                            <p class="profile-detail-value" id="detail-user-created-at">Carregando...</p>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button id="editar-perfil" class="profile-btn profile-btn-primary">Editar Perfil</button>
                        <button id="alterar-senha" class="profile-btn profile-btn-secondary">Alterar Senha</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulário de edição de perfil -->
        <div id="editar-perfil-form" style="display:none;">
            <div class="card">
                <div style="max-height: 70vh; overflow-y: auto; padding: 15px;">
                    <h2>Editar Perfil</h2>
                    <form id="perfil-edit-form">
                    <label for="edit-nome">Nome:</label>
                    <input type="text" id="edit-nome" name="nome" required>
                    
                    <label for="edit-email">E-mail:</label>
                    <input type="email" id="edit-email" name="email" required>
                    
                    <label for="edit-telefone">Telefone:</label>
                    <input type="tel" id="edit-telefone" name="telefone" required>
                    
                    <label for="edit-cpf">CPF:</label>
                    <input type="text" id="edit-cpf" name="cpf" maxlength="14" required placeholder="000.000.000-00">
                    
                    <label for="edit-rg">RG:</label>
                    <input type="text" id="edit-rg" name="rg" required>
                    
                    <label for="edit-data-nascimento">Data de Nascimento:</label>
                    <input type="date" id="edit-data-nascimento" name="data_nascimento" required>
                    
                    <label for="edit-estado-civil">Estado Civil:</label>
                    <select id="edit-estado-civil" name="estado_civil">
                        <option value="Solteiro(a)">Solteiro(a)</option>
                        <option value="Casado(a)">Casado(a)</option>
                        <option value="Divorciado(a)">Divorciado(a)</option>
                        <option value="Viúvo(a)">Viúvo(a)</option>
                        <option value="União Estável">União Estável</option>
                        <option value="Separado(a)">Separado(a)</option>
                        <option value="Outro">Outro</option>
                    </select>
                    
                    <label for="edit-rua">Rua:</label>
                    <input type="text" id="edit-rua" name="rua" required>
                    
                    <label for="edit-numero">Número:</label>
                    <input type="text" id="edit-numero" name="numero" required>
                    
                    <label for="edit-bairro">Bairro:</label>
                    <input type="text" id="edit-bairro" name="bairro" required>
                    
                    <label for="edit-cep">CEP:</label>
                    <input type="text" id="edit-cep" name="cep" maxlength="8" required>
                    
                    <label for="edit-cidade">Cidade:</label>
                    <input type="text" id="edit-cidade" name="cidade" required>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" id="cancelar-edicao" class="ui-btn">Cancelar</button>
                        <button type="submit" class="ui-btn ui-btn-b">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Formulário de alteração de senha -->
        <div id="alterar-senha-form" style="display:none;">
            <div class="card">
                <div style="max-height: 70vh; overflow-y: auto; padding: 15px;">
                    <h2>Alterar Senha</h2>
                    <form id="senha-change-form">
                    <label for="senha-atual">Senha Atual:</label>
                    <input type="password" id="senha-atual" name="senha_atual" required>
                    
                    <label for="nova-senha">Nova Senha:</label>
                    <input type="password" id="nova-senha" name="nova_senha" required>
                    
                    <label for="confirmar-senha">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar-senha" name="confirmar_senha" required>
                    
                    <div style="margin-top: 15px;">
                        <button type="button" id="cancelar-senha" class="ui-btn">Cancelar</button>
                        <button type="submit" class="ui-btn ui-btn-b">Alterar Senha</button>
                    </div>
                </form>
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
                <a href="suas_reservas.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center">
                    <i class="fas fa-calendar mb-1"></i>
                    <small>Reservas</small>
                </a>
            </div>
            <div class="col-3 py-2">
                <a href="perfil.php" class="text-white text-decoration-none d-block h-100 d-flex flex-column align-items-center justify-content-center active">
                    <i class="fas fa-user mb-1"></i>
                    <small>Perfil</small>
                </a>
            </div>
        </nav>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Função para carregar o nome do usuário e atualizar o cabeçalho
function loadUserName() {
    $.ajax({
        url: '../php/get_user_info.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.user_name) {
                document.getElementById('header-title-perfil').textContent = 'Olá ' + response.user_name;
            } else {
                document.getElementById('header-title-perfil').textContent = 'Seu Perfil';
            }
        },
        error: function() {
            document.getElementById('header-title-perfil').textContent = 'Seu Perfil';
        }
    });
}

$(document).ready(function() {
    // Carregar o nome do usuário para atualizar o cabeçalho
    loadUserName();

    // Carregar informações do usuário
    $.ajax({
        url: '../php/get_user_info.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#user-name').text(response.user.nome || 'Não informado');
                $('#user-email').text(response.user.email || 'Não informado');

                // Populate the detailed profile information
                $('#detail-user-name').text(response.user.nome || 'Não informado');
                $('#detail-user-email').text(response.user.email || 'Não informado');

                // Formatar telefone
                if(response.user.telefone) {
                    const telefone = response.user.telefone;
                    let formattedTelefone = telefone;

                    // Verifica se o telefone tem 11 dígitos (com 9º dígito)
                    if(telefone.length === 11) {
                        // Formato: (XX) XXXXX-XXXX
                        formattedTelefone = telefone.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                    }
                    // Verifica se o telefone tem 10 dígitos (sem 9º dígito)
                    else if(telefone.length === 10) {
                        // Formato: (XX) XXXX-XXXX
                        formattedTelefone = telefone.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
                    }

                    $('#user-phone').text(formattedTelefone);
                    $('#detail-user-phone').text(formattedTelefone);
                } else {
                    $('#user-phone').text('Não informado');
                    $('#detail-user-phone').text('Não informado');
                }

                // Formatar CPF
                if(response.user.cpf) {
                    const cpf = response.user.cpf;
                    // Adiciona formatação para o CPF XXX.XXX.XXX-XX
                    const formattedCpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    $('#user-cpf').text(formattedCpf);
                    $('#detail-user-cpf').text(formattedCpf);
                } else {
                    $('#user-cpf').text('Não informado');
                    $('#detail-user-cpf').text('Não informado');
                }

                // Exibir RG
                const rgValue = response.user.rg || 'Não informado';
                $('#user-rg').text(rgValue);
                $('#detail-user-rg').text(rgValue);

                // Formatar e exibir data de nascimento
                if(response.user.data_nascimento) {
                    // Converter string de data para o formato correto sem ajuste de fuso horário
                    const [year, month, day] = response.user.data_nascimento.split('-');
                    const date = new Date(year, month - 1, day); // month - 1 porque Janeiro é 0
                    const formattedDate = date.toLocaleDateString('pt-BR');
                    $('#user-data-nascimento').text(formattedDate);
                    $('#detail-user-data-nascimento').text(formattedDate);
                } else {
                    $('#user-data-nascimento').text('Não informado');
                    $('#detail-user-data-nascimento').text('Não informado');
                }

                // Exibir estado civil
                const estadoCivilValue = response.user.estado_civil || 'Não informado';
                $('#user-estado-civil').text(estadoCivilValue);
                $('#detail-user-estado-civil').text(estadoCivilValue);

                // Exibir endereço completo
                const rua = response.user.rua || '';
                const numero = response.user.numero || '';
                const bairro = response.user.bairro || '';

                if(rua || numero || bairro) {
                    const endereco = `${rua}, ${numero} - ${bairro}`;
                    $('#user-endereco').text(endereco);
                    $('#detail-user-endereco').text(endereco);
                } else {
                    $('#user-endereco').text('Não informado');
                    $('#detail-user-endereco').text('Não informado');
                }

                // Exibir CEP
                if(response.user.cep) {
                    const cep = response.user.cep;
                    // Formata CEP no formato XXXXX-XXX
                    const formattedCep = cep.replace(/^(\d{5})(\d{3})$/, '$1-$2');
                    $('#user-cep').text(formattedCep);
                    $('#detail-user-cep').text(formattedCep);
                } else {
                    $('#user-cep').text('Não informado');
                    $('#detail-user-cep').text('Não informado');
                }

                // Exibir cidade
                const cidadeValue = response.user.cidade || 'Não informado';
                $('#user-cidade').text(cidadeValue);
                $('#detail-user-cidade').text(cidadeValue);

                // Formatar e exibir data de cadastro
                if(response.user.created_at) {
                    const date = new Date(response.user.created_at);
                    const formattedDate = date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
                    $('#user-created-at').text(formattedDate);
                    $('#detail-user-created-at').text(formattedDate);
                } else {
                    $('#user-created-at').text('Não informado');
                    $('#detail-user-created-at').text('Não informado');
                }
            }
        },
        error: function() {
            console.error('Erro ao carregar informações do usuário');
            $('#user-phone').text('Erro ao carregar');
            $('#user-cpf').text('Erro ao carregar');
            $('#user-rg').text('Erro ao carregar');
            $('#user-data-nascimento').text('Erro ao carregar');
            $('#user-estado-civil').text('Erro ao carregar');
            $('#user-endereco').text('Erro ao carregar');
            $('#user-cep').text('Erro ao carregar');
            $('#user-cidade').text('Erro ao carregar');
            $('#user-created-at').text('Erro ao carregar');
            // Also update detail elements
            $('#detail-user-phone').text('Erro ao carregar');
            $('#detail-user-cpf').text('Erro ao carregar');
            $('#detail-user-rg').text('Erro ao carregar');
            $('#detail-user-data-nascimento').text('Erro ao carregar');
            $('#detail-user-estado-civil').text('Erro ao carregar');
            $('#detail-user-endereco').text('Erro ao carregar');
            $('#detail-user-cep').text('Erro ao carregar');
            $('#detail-user-cidade').text('Erro ao carregar');
            $('#detail-user-created-at').text('Erro ao carregar');
        }
    });

    // Evento para abrir o formulário de edição
    $('#editar-perfil').on('click', function() {
        // Carregar os dados atuais para o formulário de edição
        $.ajax({
            url: '../php/get_user_info.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Preencher o formulário com os dados atuais
                    $('#edit-nome').val(response.user.nome || '');
                    $('#edit-email').val(response.user.email || '');
                    $('#edit-telefone').val(response.user.telefone || '');
                    // Remover formatação do CPF para o campo de edição
                    const cpfSemFormatacao = response.user.cpf ? response.user.cpf.replace(/\D/g, '') : '';
                    $('#edit-cpf').val(cpfSemFormatacao);
                    $('#edit-rg').val(response.user.rg || '');

                    // Formatar data de nascimento para o formato YYYY-MM-DD
                    if(response.user.data_nascimento) {
                        // Converter string de data para o formato correto sem ajuste de fuso horário
                        const [year, month, day] = response.user.data_nascimento.split('-');
                        const date = new Date(year, month - 1, day); // month - 1 porque Janeiro é 0
                        // Para o formato YYYY-MM-DD, vamos usar a data exata do banco
                        $('#edit-data-nascimento').val(response.user.data_nascimento);
                    }

                    const estadoCivil = response.user.estado_civil || 'Solteiro(a)';
                    $('#edit-estado-civil').val(estadoCivil);

                    // Garantir que o valor foi definido corretamente
                    if (!$('#edit-estado-civil').val()) {
                        // Se não encontrou o valor exato no select, tentar encontrar a opção mais próxima
                        const options = $('#edit-estado-civil option').map(function() { return this.value; }).get();
                        if (options.includes(estadoCivil)) {
                            $('#edit-estado-civil').val(estadoCivil);
                        } else {
                            $('#edit-estado-civil').val('Solteiro(a)'); // Valor padrão
                        }
                    }
                    $('#edit-rua').val(response.user.rua || '');
                    $('#edit-numero').val(response.user.numero || '');
                    $('#edit-bairro').val(response.user.bairro || '');
                    $('#edit-cep').val(response.user.cep || '');
                    $('#edit-cidade').val(response.user.cidade || '');

                    // Exibir o formulário de edição e ocultar as informações atuais
                    $('#editar-perfil-form').show();
                    $('.profile-container').hide();
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao carregar informações para edição.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Função para formatar CPF enquanto o usuário digita
    $('#edit-cpf').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remover tudo que não é dígito
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

        $(this).val(value);
    });

    // Função para formatar CEP enquanto o usuário digita
    $('#edit-cep').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remover tudo que não é dígito
        if (value.length > 8) value = value.slice(0, 8); // Limitar a 8 dígitos

        if (value.length > 5) {
            // Formato: XXXXX-XXX
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        }

        $(this).val(value);
    });

    // Função para formatar telefone enquanto o usuário digita
    $('#edit-telefone').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remover tudo que não é dígito
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

        $(this).val(value);
    });

    // Evento para cancelar a edição
    $(document).on('click', '#cancelar-edicao', function() {
        $('#editar-perfil-form').hide();
        $('.profile-container').show();
    });

    // Evento para submeter o formulário de edição
    $('#perfil-edit-form').on('submit', function(e) {
        e.preventDefault();

        // Desabilitar o botão de submissão para evitar múltiplos envios
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Salvando...');

        // Coletar os dados do formulário e remover formatação onde necessário
        const cpfLimpo = $('#edit-cpf').val().replace(/\D/g, ''); // Remover formatação do CPF
        const cepLimpo = $('#edit-cep').val().replace(/\D/g, ''); // Remover formatação do CEP
        const telefoneLimpo = $('#edit-telefone').val().replace(/\D/g, ''); // Remover formatação do telefone
        const estadoCivil = $('#edit-estado-civil').val() || 'Solteiro(a)'; // Garantir um valor padrão

        const formData = {
            nome: $('#edit-nome').val(),
            email: $('#edit-email').val(),
            telefone: telefoneLimpo,
            cpf: cpfLimpo,
            rg: $('#edit-rg').val(),
            data_nascimento: $('#edit-data-nascimento').val(),
            estado_civil: estadoCivil,
            rua: $('#edit-rua').val(),
            numero: $('#edit-numero').val(),
            bairro: $('#edit-bairro').val(),
            cep: cepLimpo,
            cidade: $('#edit-cidade').val()
        };

        // Enviar os dados para atualização
        $.ajax({
            url: '../php/update_user_info.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Perfil atualizado com sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });

                    // Atualizar os dados na visualização normal
                    $('#user-name').text(formData.nome);
                    $('#user-email').text(formData.email);
                    $('#detail-user-name').text(formData.nome);
                    $('#detail-user-email').text(formData.email);

                    // Formatar e atualizar telefone
                    let formattedTelefone = formData.telefone;
                    if(formData.telefone.length === 11) {
                        formattedTelefone = formData.telefone.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                    } else if(formData.telefone.length === 10) {
                        formattedTelefone = formData.telefone.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
                    }
                    $('#user-phone').text(formattedTelefone);
                    $('#detail-user-phone').text(formattedTelefone);

                    // Formatar e atualizar CPF
                    let formattedCpf = formData.cpf;
                    if(formData.cpf.length === 11) {
                        formattedCpf = formData.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    }
                    $('#user-cpf').text(formattedCpf);
                    $('#detail-user-cpf').text(formattedCpf);

                    // Atualizar outros campos
                    const rgValue = formData.rg || 'Não informado';
                    $('#user-rg').text(rgValue);
                    $('#detail-user-rg').text(rgValue);

                    // Converter string de data para o formato correto sem ajuste de fuso horário
                    const [year, month, day] = formData.data_nascimento.split('-');
                    const date = new Date(year, month - 1, day); // month - 1 porque Janeiro é 0
                    const formattedDate = date.toLocaleDateString('pt-BR');
                    $('#user-data-nascimento').text(formattedDate);
                    $('#detail-user-data-nascimento').text(formattedDate);

                    const estadoCivilValue = formData.estado_civil || 'Não informado';
                    $('#user-estado-civil').text(estadoCivilValue);
                    $('#detail-user-estado-civil').text(estadoCivilValue);

                    // Atualizar endereço com verificação de campos vazios
                    const rua = formData.rua || '';
                    const numero = formData.numero || '';
                    const bairro = formData.bairro || '';
                    if(rua || numero || bairro) {
                        const endereco = `${rua}, ${numero} - ${bairro}`;
                        $('#user-endereco').text(endereco);
                        $('#detail-user-endereco').text(endereco);
                    } else {
                        $('#user-endereco').text('Não informado');
                        $('#detail-user-endereco').text('Não informado');
                    }

                    // Formatar CEP apenas se tiver valor
                    if(formData.cep) {
                        const formattedCep = formData.cep.replace(/^(\d{5})(\d{3})$/, '$1-$2');
                        $('#user-cep').text(formattedCep);
                        $('#detail-user-cep').text(formattedCep);
                    } else {
                        $('#user-cep').text('Não informado');
                        $('#detail-user-cep').text('Não informado');
                    }

                    const cidadeValue = formData.cidade || 'Não informado';
                    $('#user-cidade').text(cidadeValue);
                    $('#detail-user-cidade').text(cidadeValue);

                    // Fechar o formulário de edição
                    $('#editar-perfil-form').hide();
                    $('.profile-container').show();
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao atualizar perfil: ' + response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição JAVASCRIPT:', error);
                console.error('Resposta do servidor:', xhr.responseText);
                Swal.fire({
                    title: 'Erro de Comunicação',
                    text: 'Erro na comunicação com o servidor ao atualizar perfil.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Reabilitar o botão de submissão
                submitBtn.prop('disabled', false).text('Salvar Alterações');
            }
        });
    });

    // Evento para abrir o formulário de alteração de senha
    $('#alterar-senha').on('click', function() {
        // Limpar o formulário
        $('#senha-change-form')[0].reset();

        // Exibir o formulário de alteração de senha e ocultar as informações atuais
        $('#alterar-senha-form').show();
        $('.profile-container').hide();
    });

    // Evento para cancelar a alteração de senha
    $(document).on('click', '#cancelar-senha', function() {
        $('#alterar-senha-form').hide();
        $('.profile-container').show();
    });

    // Evento para submeter o formulário de alteração de senha
    $('#senha-change-form').on('submit', function(e) {
        e.preventDefault();

        // Desabilitar o botão de submissão para evitar múltiplos envios
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Alterando...');

        // Coletar os dados do formulário
        const formData = {
            senha_atual: $('#senha-atual').val(),
            nova_senha: $('#nova-senha').val(),
            confirmar_senha: $('#confirmar-senha').val()
        };

        // Verificar se as senhas novas coincidem
        if (formData.nova_senha !== formData.confirmar_senha) {
            Swal.fire({
                title: 'Erro!',
                text: 'A nova senha e a confirmação de senha não coincidem.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            submitBtn.prop('disabled', false).text('Alterar Senha');
            return;
        }

        // Enviar os dados para alteração de senha
        $.ajax({
            url: '../php/change_password.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Senha alterada com sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });

                    // Fechar o formulário de alteração de senha
                    $('#alterar-senha-form').hide();
                    $('.profile-container').show();
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao alterar senha: ' + response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição JAVASCRIPT:', error);
                console.error('Resposta do servidor:', xhr.responseText);
                Swal.fire({
                    title: 'Erro de Comunicação',
                    text: 'Erro na comunicação com o servidor ao alterar senha.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Reabilitar o botão de submissão
                submitBtn.prop('disabled', false).text('Alterar Senha');
            }
        });
    });

    // Manipulador para o botão de logout
    $('#logout-link').on('click', function(e) {
        e.preventDefault();

        $.ajax({
            url: '../php/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Limpar possíveis dados da sessão no frontend e redirecionar de forma limpa
                    // Forçar limpeza do cache do navegador antes de redirecionar
                    if ('caches' in window) {
                        caches.delete('api-cache').catch(() => {}); // Limpar cache se existir
                    }
                    window.location.replace('login.php');
                } else {
                    alert('Erro ao fazer logout. Por favor, tente novamente.');
                }
            },
            error: function() {
                // Exibir mensagem de erro e redirecionar para login
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao fazer logout. Redirecionando para login...',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(function() {
                    // Mesmo em caso de erro, redirecionar para login
                    window.location.replace('login.php'); // Usar replace para evitar histórico
                });
            }
        });
    });
});
</script>

</body>
</html>