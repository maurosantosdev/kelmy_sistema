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
    
    <link rel="stylesheet" href="../assets/css/jquery.mobile-1.4.5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div data-role="page" id="perfilPageCliente">

    <div data-role="header" data-position="fixed">
        <h1 id="header-title-perfil">Seu Perfil</h1>
        <a href="#" id="logout-link" class="ui-btn-right ui-btn ui-corner-all">Sair</a>
    </div>

    <div role="main" class="ui-content">
        <div class="card">
            <h2>Informações do Perfil</h2>
            <p><strong>Nome:</strong> <span id="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Carregando...'); ?></span></p>
            <p><strong>E-mail:</strong> <span id="user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'Carregando...'); ?></span></p>
            <p><strong>Telefone:</strong> <span id="user-phone">Carregando...</span></p>
            <p><strong>CPF:</strong> <span id="user-cpf">Carregando...</span></p>
            <p><strong>RG:</strong> <span id="user-rg">Carregando...</span></p>
            <p><strong>Data de Nascimento:</strong> <span id="user-data-nascimento">Carregando...</span></p>
            <p><strong>Estado Civil:</strong> <span id="user-estado-civil">Carregando...</span></p>
            <p><strong>Endereço:</strong> <span id="user-endereco">Carregando...</span></p>
            <p><strong>CEP:</strong> <span id="user-cep">Carregando...</span></p>
            <p><strong>Cidade:</strong> <span id="user-cidade">Carregando...</span></p>
            <p><strong>Data de Cadastro:</strong> <span id="user-created-at">Carregando...</span></p>
        </div>
        
        <button id="editar-perfil" class="ui-btn">Editar Perfil</button>
        <button id="alterar-senha" class="ui-btn">Alterar Senha</button>
        
        <!-- Formulário de edição de perfil -->
        <div id="editar-perfil-form" style="display:none;">
            <div class="card">
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

    <div data-role="footer" data-position="fixed">
        <div data-role="navbar">
            <ul>
                <li><a href="../index.php" data-ajax="false" data-icon="home">Inicio</a></li>
                <li><a href="reserva.php" data-ajax="false" data-icon="grid">Reserve</a></li>
                <li><a href="suas_reservas.php" data-ajax="false" data-icon="calendar">Reservas</a></li>
                <li><a href="perfil.php" data-ajax="false" data-icon="user" class="ui-btn-active ui-state-persist">Perfil</a></li>
            </ul>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/jquery.mobile-1.4.5.min.js"></script>
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

$(document).on('pageinit', '#perfilPageCliente', function() {
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
                } else {
                    $('#user-phone').text('Não informado');
                }
                
                // Formatar CPF
                if(response.user.cpf) {
                    const cpf = response.user.cpf;
                    // Adiciona formatação para o CPF XXX.XXX.XXX-XX
                    const formattedCpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    $('#user-cpf').text(formattedCpf);
                } else {
                    $('#user-cpf').text('Não informado');
                }
                
                // Exibir RG
                $('#user-rg').text(response.user.rg || 'Não informado');
                
                // Formatar e exibir data de nascimento
                if(response.user.data_nascimento) {
                    // Converter string de data para o formato correto sem ajuste de fuso horário
                    const [year, month, day] = response.user.data_nascimento.split('-');
                    const date = new Date(year, month - 1, day); // month - 1 porque Janeiro é 0
                    $('#user-data-nascimento').text(date.toLocaleDateString('pt-BR'));
                } else {
                    $('#user-data-nascimento').text('Não informado');
                }
                
                // Exibir estado civil
                $('#user-estado-civil').text(response.user.estado_civil || 'Não informado');
                
                // Exibir endereço completo
                const rua = response.user.rua || '';
                const numero = response.user.numero || '';
                const bairro = response.user.bairro || '';
                
                if(rua || numero || bairro) {
                    $('#user-endereco').text(`${rua}, ${numero} - ${bairro}`);
                } else {
                    $('#user-endereco').text('Não informado');
                }
                
                // Exibir CEP
                if(response.user.cep) {
                    const cep = response.user.cep;
                    // Formata CEP no formato XXXXX-XXX
                    const formattedCep = cep.replace(/^(\d{5})(\d{3})$/, '$1-$2');
                    $('#user-cep').text(formattedCep);
                } else {
                    $('#user-cep').text('Não informado');
                }
                
                // Exibir cidade
                $('#user-cidade').text(response.user.cidade || 'Não informado');
                
                // Formatar e exibir data de cadastro
                if(response.user.created_at) {
                    const date = new Date(response.user.created_at);
                    $('#user-created-at').text(date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR'));
                } else {
                    $('#user-created-at').text('Não informado');
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
                    $('.card').first().hide();
                    $('#alterar-senha').hide();
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
        $('.card').first().show();
        $('#alterar-senha').show();
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
                    
                    // Formatar e atualizar telefone
                    let formattedTelefone = formData.telefone;
                    if(formData.telefone.length === 11) {
                        formattedTelefone = formData.telefone.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                    } else if(formData.telefone.length === 10) {
                        formattedTelefone = formData.telefone.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
                    }
                    $('#user-phone').text(formattedTelefone);
                    
                    // Formatar e atualizar CPF
                    let formattedCpf = formData.cpf;
                    if(formData.cpf.length === 11) {
                        formattedCpf = formData.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    }
                    $('#user-cpf').text(formattedCpf);
                    
                    // Atualizar outros campos
                    $('#user-rg').text(formData.rg || 'Não informado');
                    // Converter string de data para o formato correto sem ajuste de fuso horário
                    const [year, month, day] = formData.data_nascimento.split('-');
                    const date = new Date(year, month - 1, day); // month - 1 porque Janeiro é 0
                    $('#user-data-nascimento').text(date.toLocaleDateString('pt-BR'));
                    $('#user-estado-civil').text(formData.estado_civil);
                    
                    // Atualizar endereço com verificação de campos vazios
                    const rua = formData.rua || '';
                    const numero = formData.numero || '';
                    const bairro = formData.bairro || '';
                    if(rua || numero || bairro) {
                        $('#user-endereco').text(`${rua}, ${numero} - ${bairro}`);
                    } else {
                        $('#user-endereco').text('Não informado');
                    }
                    
                    // Formatar CEP apenas se tiver valor
                    if(formData.cep) {
                        const formattedCep = formData.cep.replace(/^(\d{5})(\d{3})$/, '$1-$2');
                        $('#user-cep').text(formattedCep);
                    } else {
                        $('#user-cep').text('Não informado');
                    }
                    
                    $('#user-cidade').text(formData.cidade || 'Não informado');
                    
                    // Fechar o formulário de edição
                    $('#editar-perfil-form').hide();
                    $('.card').first().show();
                    $('#alterar-senha').show();
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
                console.error('Erro na requisição AJAX:', error);
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
        $('.card').first().hide();
        $('#editar-perfil').hide();
    });
    
    // Evento para cancelar a alteração de senha
    $(document).on('click', '#cancelar-senha', function() {
        $('#alterar-senha-form').hide();
        $('.card').first().show();
        $('#editar-perfil').show();
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
                    $('.card').first().show();
                    $('#editar-perfil').show();
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
                console.error('Erro na requisição AJAX:', error);
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
                    window.location.href = 'login.php';
                });
            }
        });
    });
});
</script>

</body>
</html>