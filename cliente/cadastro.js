// Função para limpar o formulário
function clearForm() {
    document.getElementById('cadastroForm').reset();

    // Limpar também os campos formatados que podem manter valores
    document.getElementById('cpf').value = '';
    document.getElementById('cep').value = '';
    document.getElementById('telefone').value = '';

    console.log('Formulário limpo');
}

// Função para aplicar máscara de CPF
function applyCpfMask(value) {
    let cleanValue = value.replace(/\D/g, '');
    if (cleanValue.length > 11) cleanValue = cleanValue.substring(0, 11);

    if (cleanValue.length > 9) {
        cleanValue = cleanValue.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (cleanValue.length > 6) {
        cleanValue = cleanValue.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (cleanValue.length > 3) {
        cleanValue = cleanValue.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }

    return cleanValue;
}

// Função para aplicar máscara de CEP
function applyCepMask(value) {
    let cleanValue = value.replace(/\D/g, '');
    if (cleanValue.length > 8) cleanValue = cleanValue.substring(0, 8);

    if (cleanValue.length > 5) {
        cleanValue = cleanValue.replace(/(\d{5})(\d{3})$/, '$1-$2');
    }

    return cleanValue;
}

// Função para aplicar máscara de telefone
function applyTelefoneMask(value) {
    let cleanValue = value.replace(/\D/g, '');
    if (cleanValue.length > 11) cleanValue = cleanValue.substring(0, 11);

    if (cleanValue.length > 6) {
        cleanValue = cleanValue.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (cleanValue.length > 2) {
        cleanValue = cleanValue.replace(/(\d{2})(\d{4,5})/, '($1) $2');
    }

    return cleanValue;
}

// Função para formatar CPF ao sair do campo
function formatCpfOnBlur(value) {
    let cleanValue = value.replace(/\D/g, '');
    if (cleanValue.length === 11) {
        cleanValue = cleanValue.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    return cleanValue;
}

// Função para formatar CEP ao sair do campo
function formatCepOnBlur(value) {
    let cleanValue = value.replace(/\D/g, '');
    if (cleanValue.length === 8) {
        cleanValue = cleanValue.replace(/(\d{5})(\d{3})/, '$1-$2');
    }
    return cleanValue;
}

// Função para formatar telefone ao sair do campo
function formatTelefoneOnBlur(value) {
    let cleanValue = value.replace(/\D/g, '');
    if (value.length === 11) {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (value.length === 10) {
        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return value;
}

// Função para inicializar os eventos do formulário
function initializeCadastroEvents() {
    // Limpar o formulário quando a função for chamada
    clearForm();

    // Adicionar eventos para máscaras
    const cpfInput = document.getElementById('cpf');
    const cepInput = document.getElementById('cep');
    const telefoneInput = document.getElementById('telefone');

    // Máscara para CPF
    if (cpfInput) {
        cpfInput.addEventListener('input', function() {
            this.value = applyCpfMask(this.value);
        });

        // Formatar CPF automaticamente ao sair do campo
        cpfInput.addEventListener('blur', function() {
            this.value = formatCpfOnBlur(this.value);
        });
    }

    // Máscara para CEP
    if (cepInput) {
        cepInput.addEventListener('input', function() {
            this.value = applyCepMask(this.value);
        });

        // Formatar CEP automaticamente ao sair do campo
        cepInput.addEventListener('blur', function() {
            this.value = formatCepOnBlur(this.value);
        });
    }

    // Máscara para telefone
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function() {
            this.value = applyTelefoneMask(this.value);
        });

        // Formatar telefone automaticamente ao sair do campo
        telefoneInput.addEventListener('blur', function() {
            this.value = formatTelefoneOnBlur(this.value);
        });
    }

    // Manipulador de submit do formulário
    const form = document.getElementById('cadastroForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('Formulário de submit acionado e manipulado');

            // Obter os valores dos campos
            var formData = {
                nome: document.getElementById('nome').value,
                rg: document.getElementById('rg').value,
                cpf: document.getElementById('cpf').value, // Manter formatação
                estado_civil: document.getElementById('estado_civil').value,
                rua: document.getElementById('rua').value,
                numero: document.getElementById('numero').value,
                bairro: document.getElementById('bairro').value,
                cep: document.getElementById('cep').value, // Manter formatação
                cidade: document.getElementById('cidade').value,
                telefone: document.getElementById('telefone').value, // Manter formatação
                data_nascimento: document.getElementById('data_nascimento').value,
                email: document.getElementById('email').value,
                senha: document.getElementById('senha').value,
                confirmar_senha: document.getElementById('confirmar_senha').value
            };

            console.log('Dados do formulário:', formData);

            // Validação dos campos
            if (!formData.nome || !formData.rg || !formData.estado_civil ||
                !formData.rua || !formData.numero || !formData.bairro || !formData.cidade ||
                !formData.data_nascimento || !formData.email || !formData.senha || !formData.confirmar_senha) {

                console.log('Validação falhou - campos obrigatórios vazios');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Por favor, preencha todos os campos.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Por favor, preencha todos os campos.');
                }
                return false;
            }

            // Verificar se as senhas coincidem
            if (formData.senha !== formData.confirmar_senha) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'As senhas não coincidem.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('As senhas não coincidem.');
                }
                return false;
            }

            console.log('Enviando dados para o servidor...');

            // Log para verificar o que está sendo enviado
            console.log('Dados que serão enviados:', formData);
            const bodyData = Object.keys(formData).map(key => encodeURIComponent(key) + '=' + encodeURIComponent(formData[key])).join('&');
            console.log('Body da requisição:', bodyData);

            // Mostrar loading
            if (typeof Swal !== 'undefined') {
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
            }

            // Enviar via fetch
            fetch('../php/cliente_cadastro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: bodyData
            })
            .then(response => {
                // Check if the response is redirected (which means traditional form submission)
                if (response.redirected) {
                    // If redirected, navigate to the new URL
                    window.location.href = response.url;
                } else if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
                    // If not redirected and response is JSON, parse it
                    return response.json().then(data => {
                        console.log('Resposta do servidor:', data);

                        // Fechar o loading
                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                        }

                        if (data.success) {
                            console.log('Cadastro realizado com sucesso!');
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Sucesso!',
                                    text: data.message || 'Cadastro realizado com sucesso!',
                                    icon: 'success',
                                    timer: 2000, // Auto-close after 2 seconds
                                    showConfirmButton: false
                                }).then(function() {
                                    // Redirecionar para a página retornada pelo servidor após o cadastro
                                    var redirectUrl = data.redirect || '../cliente/suas_reservas.php';

                                    // Forçar um redirecionamento limpo para evitar que dados do formulário fiquem na URL
                                    window.location.replace(redirectUrl);
                                });

                                // Also redirect after the timer automatically
                                setTimeout(function() {
                                    var redirectUrl = data.redirect || '../cliente/suas_reservas.php';
                                    window.location.replace(redirectUrl);
                                }, 2000);
                            } else {
                                alert(data.message || 'Cadastro realizado com sucesso!');
                                var redirectUrl = data.redirect || '../cliente/suas_reservas.php';
                                window.location.replace(redirectUrl);
                            }
                        } else {
                            console.log('Erro no cadastro:', data.message);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: 'Erro!',
                                    text: data.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                alert(data.message);
                            }
                        }
                    }).catch(jsonError => {
                        // Handle case where response is not JSON but also not redirected
                        console.error('Erro ao processar resposta JSON:', jsonError);

                        if (typeof Swal !== 'undefined') {
                            Swal.close();
                            Swal.fire({
                                title: 'Erro!',
                                text: 'Erro ao processar resposta do servidor',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                } else {
                    // If response is not JSON, return a default error object
                    if (typeof Swal !== 'undefined') {
                        Swal.close();
                    }
                    throw new Error('Erro na comunicação com o servidor');
                }
            })
            .catch(error => {
                console.log('Fetch Error:', error);

                // Fechar o loading
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro na comunicação com o servidor: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'Tentar novamente'
                    });
                } else {
                    alert('Erro na comunicação com o servidor: ' + error.message);
                }
            });
        });
    }
}

// Adicionando logs para depuração
console.log("Documento carregado, inicializando eventos...");

// Inicializar eventos quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOMContentLoaded acionado");
    // Limpar o formulário ao carregar o documento
    clearForm();
    initializeCadastroEvents();
});