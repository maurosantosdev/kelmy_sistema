// Variável global para controlar se o manipulador já foi adicionado
let loginProcessing = false;

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginClienteForm');
    const loginSubmitBtn = document.getElementById('loginSubmitBtn');
    const emailInput = document.getElementById('email_login');
    const senhaInput = document.getElementById('senha_login');

    // Impedir completamente a submissão padrão do formulário
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Impedir propagação do evento

        // Acionar o mesmo processo do clique no botão
        if (!loginProcessing) {
            loginSubmitBtn.click();
        }
        return false;
    });

    // Manipular o clique no botão de submit
    loginSubmitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Impedir propagação do evento

        console.log("Botão de login clicado");

        // Verificar se já está processando para evitar múltiplos envios
        if (loginProcessing) {
            console.log("Formulário já está em processamento");
            return false;
        }

        // Marcar como em processamento
        loginProcessing = true;

        const email = emailInput.value;
        const senha = senhaInput.value;

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
            loginProcessing = false;
            console.log("Campos vazios, retornando");
            return;
        }

        // Fazer a requisição AJAX com fetch
        fetch('../php/cliente_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'  // Indicar que é uma requisição JAVASCRIPT
            },
            body: `email=${encodeURIComponent(email)}&senha=${encodeURIComponent(senha)}`
        })
        .then(response => {
            // Verificar se a resposta é do tipo JSON antes de tentar fazer o parse
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // Se não for JSON, tentar extrair mensagem de erro ou retornar um objeto de erro
                return response.text().then(text => {
                    console.log("Resposta não é JSON:", text);
                    return { success: false, message: 'Erro de comunicação com o servidor. Resposta inesperada.' };
                });
            }
        })
        .then(response => {
            console.log("Resposta do servidor:", response);
            if (response.success) {
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
                            window.location.replace('https://chacararecantodosossegorr.com.br/cliente/reserva.php');
                        }
                    });
                } else {
                    alert(response.message);
                    window.location.replace('https://chacararecantodosossegorr.com.br/cliente/reserva.php');
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
                loginProcessing = false;
            }
        })
        .catch(error => {
            console.log("Erro na requisição:", error);
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
            loginProcessing = false;
        });
    });
});

// Função para obter parâmetros da URL
function getUrlParameter(name) {
    var urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Checar se há parâmetros de login na URL e preencher os campos automaticamente
document.addEventListener('DOMContentLoaded', function() {
    var emailParam = getUrlParameter('email_login');
    var senhaParam = getUrlParameter('senha_login');

    if (emailParam) {
        document.getElementById('email_login').value = decodeURIComponent(emailParam);
    }

    if (senhaParam) {
        document.getElementById('senha_login').value = decodeURIComponent(senhaParam);
    }

    // Se ambos parâmetros estiverem presentes, fazer login automaticamente
    if (emailParam && senhaParam) {
        // Simular clique no botão de login após um pequeno delay
        setTimeout(function() {
            document.getElementById('loginSubmitBtn').click();
        }, 500); // Delay de 500ms para garantir que os campos estejam preenchidos
    }
});