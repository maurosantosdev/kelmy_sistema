// Variável global para controlar se o manipulador já foi adicionado
if (typeof loginHandlerInitialized === 'undefined') {
    loginHandlerInitialized = false;
}

// Para compatibilidade com jQuery Mobile
$(document).on('pageinit', '#loginPageCliente', function() {
    // Verificar se já foi inicializado para evitar duplicação
    if (loginHandlerInitialized) {
        return;
    }
    
    loginHandlerInitialized = true;
    
    // Impedir completamente a submissão padrão do formulário
    $('#loginClienteForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Impedir propagação do evento
        return false;
    });
    
    // Manipular o clique no botão de submit específico em vez do submit do formulário
    $(document).off('click', '#loginSubmitBtn').on('click', '#loginSubmitBtn', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Impedir propagação do evento
        
        console.log("Botão de login clicado");
        
        // Verificar se já está processando para evitar múltiplos envios
        if ($('#loginClienteForm').data('processing') === true) {
            console.log("Formulário já está em processamento");
            return false;
        }
        
        // Marcar como em processamento
        $('#loginClienteForm').data('processing', true);
        
        const email = $('#email_login').val();
        const senha = $('#senha_login').val();
        
        console.log("Email:", email, "Senha:", senha ? "***" : "");
        
        if (!email || !senha) {
            // Exibir SweetAlert2 para campos vazios se disponível, senão usar alert padrão
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Atenção',
                    text: 'Por favor, preencha todos os campos.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ffc107'
                });
            } else {
                alert('Por favor, preencha todos os campos.');
            }
            // Permitir nova tentativa se campos estiverem vazios
            $('#loginClienteForm').data('processing', false);
            console.log("Campos vazios, retornando");
            return;
        }
        
        $.ajax({
            url: '../php/cliente_login.php',
            type: 'POST',
            data: {
                email: email,
                senha: senha
            },
            dataType: 'json',
            success: function(response) {
                console.log("Resposta do servidor:", response);
                if (response.success) {
                    // Usar timeout para garantir apenas um alert
                    if (!$('#loginClienteForm').data('alertShown')) {
                        $('#loginClienteForm').data('alertShown', true);
                        // Exibir SweetAlert2 para login bem-sucedido se disponível, senão usar alert padrão
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Login realizado!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#28a745'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Redirecionar para a página de reservas (usando replace para evitar histórico de parâmetros)
                                    console.log("Login bem-sucedido, redirecionando para reserva.php");
                                    // Limpar parâmetros da URL antes de redirecionar
                                    window.location.replace('https://chacararecantodosossegorr.com.br/chacara_kelmy/cliente/reserva.php');
                                }
                            });
                        } else {
                            alert(response.message);
                            window.location.replace('https://chacararecantodosossegorr.com.br/chacara_kelmy/cliente/reserva.php');
                        }
                    }
                } else {
                    console.log("Login falhou:", response.message);
                    // Exibir SweetAlert2 para erro de login se disponível, senão usar alert padrão
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Erro no login',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'Tentar novamente',
                            confirmButtonColor: '#dc3545'
                        });
                    } else {
                        alert(response.message);
                    }
                    // Permitir nova tentativa em caso de falha de login
                    $('#loginClienteForm').data('processing', false);
                    $('#loginClienteForm').data('alertShown', false);
                }
            },
            error: function(xhr, status, error) {
                console.log("Erro na requisição AJAX:", error, status, xhr.responseText);
                // Exibir SweetAlert2 para erro de comunicação se disponível, senão usar alert padrão
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Erro de comunicação',
                        text: 'Ocorreu um erro na comunicação com o servidor. Por favor, tente novamente.',
                        icon: 'error',
                        confirmButtonText: 'Tentar novamente',
                        confirmButtonColor: '#dc3545'
                    });
                } else {
                    alert('Erro na comunicação com o servidor.');
                }
                // Permitir nova tentativa em caso de erro
                $('#loginClienteForm').data('processing', false);
                $('#loginClienteForm').data('alertShown', false);
            }
        });
    });
});

// Função para obter parâmetros da URL
function getUrlParameter(name) {
    var urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Checar se há parâmetros de login na URL e preencher os campos automaticamente
$(document).ready(function() {
    var emailParam = getUrlParameter('email_login');
    var senhaParam = getUrlParameter('senha_login');
    
    if (emailParam) {
        $('#email_login').val(decodeURIComponent(emailParam));
    }
    
    if (senhaParam) {
        $('#senha_login').val(decodeURIComponent(senhaParam));
    }
    
    // Se ambos parâmetros estiverem presentes, fazer login automaticamente
    if (emailParam && senhaParam) {
        // Simular clique no botão de login após um pequeno delay
        setTimeout(function() {
            $('#loginSubmitBtn').click();
        }, 500); // Delay de 500ms para garantir que os campos estejam preenchidos
    }
});

// Reinicializar a flag quando a página for carregada novamente (útil para navegação do jQuery Mobile)
$(document).on('pageshow', '#loginPageCliente', function() {
    loginHandlerInitialized = false;
    $('#loginClienteForm').data('processing', false);
    $('#loginClienteForm').data('alertShown', false);
});