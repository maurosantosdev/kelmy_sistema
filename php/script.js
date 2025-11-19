// Certifique-se de que o SweetAlert2 está carregado antes de usar
$(document).on('pageinit', '#loginPage', function() {

    // Captura o evento de submit do formulário de login
    $('#loginForm').on('submit', function(e) {
        // Previne o comportamento padrão do formulário (recarregar a página)
        e.preventDefault();

        // Desabilita o botão para evitar múltiplos cliques
        $('#submitBtn').prop('disabled', true).text('Autenticando...');

        // Limpa mensagens de erro anteriores
        $('#errorMessage').text('');

        // Pega os valores dos campos
        var usuario = $('#usuario').val();
        var senha = $('#senha').val();

        // Envia os dados para o script PHP via AJAX
        $.ajax({
            url: '../php/admin_login.php',
            type: 'POST',
            data: {
                usuario: usuario,
                senha: senha
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Se o login for bem-sucedido, mostra SweetAlert2 e redireciona
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Redireciona para a página da agenda
                        window.location.href = 'agenda.html';
                    });
                } else {
                    // Se falhar, exibe a mensagem de erro
                    $('#errorMessage').text(response.message);
                }
            },
            error: function() {
                // Em caso de erro na comunicação com o servidor
                $('#errorMessage').text('Erro ao conectar com o servidor. Tente novamente.');
            },
            complete: function() {
                // Reabilita o botão após a requisição terminar
                $('#submitBtn').prop('disabled', false).text('Entrar');
            }
        });
    });
});