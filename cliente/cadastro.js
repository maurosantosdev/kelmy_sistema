// Para compatibilidade com jQuery Mobile
$(document).on('pageinit', '#cadastroPageCliente', function() {
    // Máscara para CPF
    $(document).on('input', '#cpf', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        
        if (value.length > 9) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else if (value.length > 6) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
        } else if (value.length > 3) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
        }
        
        $(this).val(value);
    });
    
    // Máscara para CEP
    $(document).on('input', '#cep', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 8) value = value.substring(0, 8);
        
        if (value.length > 5) {
            value = value.replace(/(\d{5})(\d{3})$/, '$1-$2');
        }
        
        $(this).val(value);
    });
    
    // Máscara para telefone
    $(document).on('input', '#telefone', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        
        if (value.length > 6) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d{4})$/, '$1-$2');
        } else if (value.length > 2) {
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
        }
        
        $(this).val(value);
    });
    
    // Formatar campos automaticamente ao sair do campo
    $(document).on('blur', '#cpf', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length === 11) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            $(this).val(value);
        }
    });
    
    $(document).on('blur', '#cep', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length === 8) {
            value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            $(this).val(value);
        }
    });
    
    $(document).on('blur', '#telefone', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length === 11) {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            $(this).val(value);
        } else if (value.length === 10) {
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            $(this).val(value);
        }
    });
    
    // Manipulador de submit para o formulário
    $(document).on('submit', '#cadastroForm', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Formulário submetido');
        
        // Obter os valores dos campos
        var formData = {
            nome: $('#nome').val(),
            rg: $('#rg').val(),
            cpf: $('#cpf').val().replace(/[^\d]/g, ''), // Remover formatação
            estado_civil: $('#estado_civil').val(),
            rua: $('#rua').val(),
            numero: $('#numero').val(),
            bairro: $('#bairro').val(),
            cep: $('#cep').val().replace(/[^\d]/g, ''), // Remover formatação
            cidade: $('#cidade').val(),
            telefone: $('#telefone').val().replace(/[^\d]/g, ''), // Remover formatação
            data_nascimento: $('#data_nascimento').val(),
            email: $('#email').val(),
            senha: $('#senha').val()
        };
        
        console.log('Dados do formulário:', formData);
        
        // Validação dos campos
        if (!formData.nome || !formData.rg || !formData.cpf || !formData.estado_civil ||
            !formData.rua || !formData.numero || !formData.bairro || !formData.cep ||
            !formData.cidade || !formData.telefone || !formData.data_nascimento ||
            !formData.email || !formData.senha) {

            console.log('Validação falhou - campos obrigatórios vazios');
            Swal.fire({
                title: 'Erro!',
                text: 'Por favor, preencha todos os campos.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        console.log('Enviando dados para o servidor...');
        
        // Enviar via AJAX
        $.ajax({
            url: '../php/cliente_cadastro.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Resposta do servidor:', response);
                if (response.success) {
                    console.log('Cadastro realizado com sucesso!');
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message || 'Cadastro realizado com sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Redirecionar para a página especificada no response ou usar a padrão
                        var redirectUrl = response.redirect || 'https://chacararecantodosossegorr.com.br/repo_limpo/cliente/reserva.php';
                        window.location.href = redirectUrl;
                    });
                } else {
                    console.log('Erro no cadastro:', response.message);
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.responseText, status, error);
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro na comunicação com o servidor: ' + error,
                    icon: 'error',
                    confirmButtonText: 'Tentar novamente'
                });
            }
        });
        
        return false;
    });
});