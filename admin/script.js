/*
=================================================================
|    SCRIPT PARA A ÁREA ADMINISTRATIVA - CHÁCARA BAHAMAS        |
|                  VERSÃO COMPLETA E FINAL                      |
=================================================================
*/

// --- 1. LÓGICA DA PÁGINA DE LOGIN ---
$(document).on('pageinit', '#loginPage', function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        var submitBtn = $('#submitBtn').prop('disabled', true).text('Autenticando...');
        $('#errorMessage').text('');
        $.ajax({
            url: '/chacara_kelmy/php/admin_login.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = '/chacara_kelmy/admin/agenda.html';
                } else {
                    $('#errorMessage').text(response.message);
                }
            },
            error: function() { $('#errorMessage').text('Erro ao conectar com o servidor.'); },
            complete: function() { submitBtn.prop('disabled', false).text('Entrar'); }
        });
    });
});

// --- 2. LÓGICA DA PÁGINA DA AGENDA (COM CORREÇÃO DE ESTILO) ---
$(document).on('pageinit', '#agendaPage', function() {
    let agendaCurrentDate = new Date();

    function agendaRenderCalendar(date, prices) {
        const month = date.getMonth(), year = date.getFullYear();
        $('#currentMonthYear').text(date.toLocaleString('pt-BR', { month: 'long', year: 'numeric' }));
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const calendarContainer = $('#calendar').empty();
        const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        
        weekDays.forEach(day => calendarContainer.append(`<div class="day-header">${day}</div>`));
        for (let i = 0; i < firstDay; i++) {
            calendarContainer.append('<div class="day empty"></div>');
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const dayStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            let dayElement = $(`<div class="day" data-date="${dayStr}">${day}</div>`);
            
            const priceInfo = prices[dayStr];
            if (priceInfo && priceInfo.preco) {
                dayElement.addClass('has-price');
                const priceHtml = '<div class="price">R$ ' + priceInfo.preco + '</div>';
                dayElement.append(priceHtml);
            }
            calendarContainer.append(dayElement);
        }

        // ***** LINHA DA CORREÇÃO DEFINITIVA *****
        // Força o jQuery Mobile a estilizar o novo conteúdo do calendário
        calendarContainer.trigger('create');
    }

    function agendaLoadMonthPrices(date) {
        const month = date.getMonth() + 1, year = date.getFullYear();
        $.ajax({
            url: `/chacara_kelmy/php/agenda_manager.php?action=get_month&month=${month}&year=${year}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.success && typeof response.prices === 'object') {
                    agendaRenderCalendar(date, response.prices);
                } else if (response && response.message === 'Acesso não autorizado.') {
                    alert('Sua sessão expirou. Por favor, faça o login novamente.');
                    window.location.href = '/chacara_kelmy/admin/';
                }
            },
            error: function() { alert('Erro de comunicação ao carregar dados do calendário.'); }
        });
    }

    $('#prevMonth').on('click', function() { agendaCurrentDate.setMonth(agendaCurrentDate.getMonth() - 1); agendaLoadMonthPrices(agendaCurrentDate); });
    $('#nextMonth').on('click', function() { agendaCurrentDate.setMonth(agendaCurrentDate.getMonth() + 1); agendaLoadMonthPrices(agendaCurrentDate); });

    $('#priceForm').on('submit', function(e) {
        e.preventDefault();
        var formMsg = $('#formMessage').text('Salvando...').css('color', 'white');
        $.ajax({
            url: '/chacara_kelmy/php/agenda_manager.php?action=save_period',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                formMsg.text(response.message).css('color', response.success ? '#4CAF50' : '#F44336');
                if (response.success) agendaLoadMonthPrices(agendaCurrentDate);
            },
            error: function() { formMsg.text('Erro de comunicação.').css('color', '#F44336'); }
        });
    });

    $('#calendar').on('click', '.day.has-price', function() {
        const dateToDelete = $(this).data('date');
        const formattedDate = new Date(dateToDelete + 'T00:00:00').toLocaleDateString('pt-BR');
        if (confirm(`Tem certeza que deseja remover o preço do dia ${formattedDate}?`)) {
            $.ajax({
                url: '/chacara_kelmy/php/agenda_manager.php?action=delete_date',
                type: 'POST',
                data: { date: dateToDelete },
                dataType: 'json',
                success: function(response) {
                    if (response.success) agendaLoadMonthPrices(agendaCurrentDate);
                    else alert('Erro: ' + response.message);
                },
                error: function() { alert('Erro de comunicação ao tentar deletar.'); }
            });
        }
    });

    agendaLoadMonthPrices(agendaCurrentDate);
});


// --- 3. LÓGICA DA PÁGINA DE INFORMAÇÕES (PERMANECE IGUAL E FUNCIONAL) ---
$(document).on('pageinit', '#infoPage', function() {

    function displayServerMediaPreview(filename) {
        const container = $('#image-preview-container');
        let mediaElement;
        const extension = filename.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            mediaElement = `<img src="/chacara_kelmy/uploads/${filename}" alt="Mídia Salva">`;
        } else if (extension === 'mp4') {
            mediaElement = `<video src="/chacara_kelmy/uploads/${filename}" muted loop playsinline controls></video>`;
        } else {
            return;
        }
        const mediaCard = `<div class="image-card" data-filename="${filename}">${mediaElement}<button class="delete-btn ui-btn ui-icon-delete ui-btn-icon-notext" data-filename="${filename}">Excluir</button></div>`;
        container.append(mediaCard);
    }

    function initializeSortable() {
        const container = $('#image-preview-container');
        if (container.hasClass('ui-sortable')) {
            container.sortable('destroy');
        }
        container.sortable({
            placeholder: "ui-sortable-placeholder",
            helper: "clone"
        });
    }

    function loadInfoData() {
        $('#image-preview-container').empty();
        $.ajax({
            url: '/chacara_kelmy/php/informacoes_manager.php?action=get_info',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    $('#info_texto').val(response.data.texto_info);
                    if (response.data.fotos && Array.isArray(response.data.fotos)) {
                        response.data.fotos.forEach(filename => displayServerMediaPreview(filename));
                    }
                    initializeSortable();
                } else {
                    console.error("Falha ao carregar dados: ", response ? response.message : "Resposta vazia do servidor.");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na chamada AJAX para get_info: ", textStatus, errorThrown);
            }
        });
    }

    $('#fotos').on('change', function(event) {
        const localPreviewContainer = $('#local-preview-container');
        localPreviewContainer.empty();
        const files = event.target.files;
        if (!files || files.length === 0) return;
        for (const file of files) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                let mediaElement;
                if (file.type.startsWith('image/')) {
                    mediaElement = `<img src="${dataUrl}" alt="Pré-visualização">`;
                } else if (file.type.startsWith('video/')) {
                    mediaElement = `<video src="${dataUrl}" muted autoplay loop playsinline></video>`;
                } else {
                    return;
                }
                const mediaCard = `<div class="image-card local-preview">${mediaElement}<div class="preview-overlay">Pré-visualização</div></div>`;
                localPreviewContainer.append(mediaCard);
            };
            reader.readAsDataURL(file);
        }
    });

    $('#infoForm').on('submit', function(e) {
        e.preventDefault();
        $('#saveInfoBtn').prop('disabled', true).text('Salvando...');
        
        var formData = new FormData(this);
        const newOrder = $('#image-preview-container').sortable('toArray', {
            attribute: 'data-filename'
        });
        formData.append('order', JSON.stringify(newOrder));

        $.ajax({
            url: '/chacara_kelmy/php/informacoes_manager.php?action=save_info',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#formMessage').text(response.message).css('color', response.success ? '#4CAF50' : '#F44336');
                if (response.success) {
                    $('#fotos').val('');
                    $('#local-preview-container').empty();
                    loadInfoData();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 $('#formMessage').text("Erro de comunicação crítico. Verifique se o servidor está online.").css('color', '#F44336');
                 console.error("Erro AJAX ao salvar:", textStatus, errorThrown, jqXHR.responseText);
            },
            complete: function() {
                $('#saveInfoBtn').prop('disabled', false).text('Atualizar Informações');
            }
        });
    });

    $('#image-preview-container').on('click', '.delete-btn', function() {
        const filename = $(this).data('filename');
        const card = $(this).closest('.image-card');
        if (confirm(`Tem certeza que deseja excluir esta mídia?`)) {
            $.ajax({
                url: '/chacara_kelmy/php/informacoes_manager.php?action=delete_image',
                type: 'POST',
                data: { filename: filename },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        card.fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Erro ao remover mídia.');
                    }
                }
            });
        }
    });

    loadInfoData();
});

// --- 4. LÓGICA DA PÁGINA DE CONTRATO ---
$(document).on('pageshow', '#contratoPage', function() {
    console.log('Página de contrato carregada');



    function loadContractData() {
        console.log('Carregando dados do contrato');
        $.ajax({
            url: '/chacara_kelmy/php/contrato_manager.php?action=get_contract',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Dados do contrato carregados:', response);
                if (response.success && response.data) {
                    renderContractItems(response.data);
                } else {
                    console.error("Falha ao carregar dados do contrato: ", response ? response.message : "Resposta vazia do servidor.");
                    // Mesmo em caso de erro, inicializar com uma lista vazia
                    renderContractItems([]);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na chamada AJAX para get_contract: ", textStatus, errorThrown);
                console.error("Erro detalhado:", jqXHR.responseText);
                // Mesmo em caso de erro, inicializar com uma lista vazia
                renderContractItems([]);
            }
        });
    }

    function renderContractItems(items) {
        const container = $('#contractItemsContainer');
        container.empty();

        // Adicionar uma seção para exibir os itens existentes como lista
        if (items.length === 0) {
            container.append(`<div style="text-align: center; padding: 20px; font-style: italic; color: #000000; background: #f5f5f5; border: 1px solid #cccccc; border-radius: 8px; margin-bottom: 15px;">Nenhum item de contrato cadastrado. Clique em "Adicionar Novo Item" para começar.</div>`);
        } else {
            // Criar uma lista clara com todos os itens existentes
            const listContainer = $(`<div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #cccccc; border-radius: 8px;"><h4 style="margin-top: 0; color: #000000 !important; font-weight: 400 !important;">Itens Cadastrados:</h4></div>`);
            const listHtml = $(`<ul style="list-style-type: none; padding: 0; margin: 0;"></ul>`);
            
            items.forEach(function(item) {
                const itemHtml = `
                    <li style="background: #ffffff; margin: 8px 0; padding: 12px; border: 1px solid #cccccc; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 5px 0; color: #000000 !important; font-weight: 400 !important;">${item.descricao}</h3>
                                <p style="margin: 0; color: #000000 !important;">Quantidade: ${item.qtd} | Valor Unitário: R$ ${parseFloat(item.valor_unitario).toFixed(2)} | Total: R$ ${parseFloat(item.valor_total).toFixed(2)}</p>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button class="edit-item-btn ui-btn ui-btn-inline ui-corner-all" data-id="${item.id}">Editar</button>
                                <button class="delete-item-btn ui-btn ui-btn-inline ui-corner-all" data-id="${item.id}">Excluir</button>
                            </div>
                        </div>
                    </li>
                `;
                listHtml.append(itemHtml);
            });
            
            listContainer.append(listHtml);
            container.append(listContainer);
        }

        // Não adiciona um novo item automaticamente, o usuário adiciona quando quiser
    }

    function addExistingContractItem(item) {
        const container = $('#contractItemsContainer');
        const rowId = 'contract-row-' + item.id;

        const descricao = item.descricao || '';
        const qtd = item.qtd || 1;
        const valor_unitario = parseFloat(item.valor_unitario).toFixed(2) || '0.00';
        const valor_total = parseFloat(item.valor_total).toFixed(2) || '0.00';

        const rowHtml = `
            <div class="contract-row" id="${rowId}" data-id="${item.id}">
                <div class="contract-item" style="background: linear-gradient(to bottom, #45515f 0%, #3d4a5a 100%); border: 1px solid #2b384a; padding: 15px; margin: 8px 0; border-radius: 8px; box-shadow: 0 1px 0 rgba(255,255,255,0.1);">
                    <div class="item-fields">
                        <div class="item-info">
                            <h4 style="color: #ffffff !important; text-shadow: 0 1px 1px rgba(0,0,0,0.3) !important; font-weight: 400 !important; margin: 0 0 8px 0;">${descricao}</h4>
                            <p style="color: #ffffff !important; opacity: 0.8; margin: 0 0 10px 0;">Qtde: ${qtd}, Valor Unitário: R$ ${valor_unitario}, Total: R$ ${valor_total}</p>
                        </div>
                        <div class="item-actions" style="margin-top: 10px; display: flex; gap: 10px;">
                            <button type="button" class="update-item-btn ui-btn ui-btn-inline ui-corner-all" style="font-weight: 500 !important; border-radius: 5px !important; border: 1px solid #004a8f !important; color: #ffffff !important; text-shadow: 0 -1px 0 rgba(0,0,0,0.4) !important; background: linear-gradient(to bottom, #00aaff 0%, #0077cc 100%) !important; box-shadow: 0 1px 2px rgba(0,0,0,0.3) !important; position: relative;">Atualizar</button>
                            <button type="button" class="delete-item-btn ui-btn ui-btn-inline ui-corner-all" style="font-weight: 500 !important; border-radius: 5px !important; border: 1px solid #004a8f !important; color: #ffffff !important; text-shadow: 0 -1px 0 rgba(0,0,0,0.4) !important; background: linear-gradient(to bottom, #00aaff 0%, #0077cc 100%) !important; box-shadow: 0 1px 2px rgba(0,0,0,0.3) !important; position: relative;">Excluir</button>
                        </div>
                    </div>
                </div>
                <hr style="margin: 15px 0; border: 0; border-top: 1px solid #2b384a; background: #2b384a;">
            </div>
        `;

        container.append(rowHtml);

        // Adicionar evento para atualizar item
        const newRow = $('#' + rowId);
        newRow.find('.update-item-btn').on('click', function() {
            updateContractItem(newRow);
        });

        // Adicionar evento para excluir item
        newRow.find('.delete-item-btn').on('click', function() {
            deleteContractItem(newRow);
        });
    }





    function updateContractItem(row) {
        const id = row.find('.item-id').val();
        const descricao = row.find('.descricao-input').val();
        const qtd = row.find('.qtd-input').val();
        const valor_unitario = row.find('.valor-unitario-input').val();
        const valor_total = row.find('.valor-total-input').val();

        if (!descricao || qtd === '' || valor_unitario === '') {
            Swal.fire({
                title: 'Erro!',
                text: 'Preencha todos os campos obrigatórios.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Enviar atualização para o servidor
        $.ajax({
            url: '/chacara_kelmy/php/contrato_manager.php?action=update_item',
            type: 'POST',
            data: {
                id: id,
                descricao: descricao,
                qtd: parseInt(qtd),
                valor_unitario: parseFloat(valor_unitario),
                valor_total: parseFloat(valor_total)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Fechar qualquer modal de edição que possa estar aberto
                    if ($('#addItemModal').hasClass('ui-popup-active')) {
                        $('#addItemModal').popup('close');
                    }
                    
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        loadContractData(); // Recarregar os dados para refletir a alteração
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                // Fechar qualquer modal de edição que possa estar aberto
                if ($('#addItemModal').hasClass('ui-popup-active')) {
                    $('#addItemModal').popup('close');
                }
                
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro de comunicação com o servidor.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    function deleteContractItem(row) {
        const id = row.data('id');

        if (id === 0) {
            // Item novo, apenas remove do DOM
            row.remove();
            return;
        }

        Swal.fire({
            title: 'Tem certeza?',
            text: 'Você deseja excluir este item do contrato?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/chacara_kelmy/php/contrato_manager.php?action=delete_contract_item',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Fechar qualquer modal de edição que possa estar aberto
                            if ($('#addItemModal').hasClass('ui-popup-active')) {
                                $('#addItemModal').popup('close');
                            }
                            
                            row.remove();
                            Swal.fire({
                                title: 'Excluído!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function() {
                                loadContractData(); // Recarregar os dados para refletir a alteração
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        // Fechar qualquer modal de edição que possa estar aberto
                        if ($('#addItemModal').hasClass('ui-popup-active')) {
                            $('#addItemModal').popup('close');
                        }
                        
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro de comunicação com o servidor.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }

    function saveNewContractItem(row) {
        const descricao = row.find('.descricao-input').val();
        const qtd = row.find('.qtd-input').val();
        const valor_unitario = row.find('.valor-unitario-input').val();
        const valor_total = row.find('.valor-total-input').val();

        if (!descricao || qtd === '' || valor_unitario === '') {
            Swal.fire({
                title: 'Erro!',
                text: 'Preencha todos os campos obrigatórios.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Enviar novo item para o servidor
        $.ajax({
            url: '/chacara_kelmy/php/contrato_manager.php?action=save_new_item',
            type: 'POST',
            data: {
                descricao: descricao,
                qtd: parseInt(qtd),
                valor_unitario: parseFloat(valor_unitario),
                valor_total: parseFloat(valor_total)
            },
            dataType: 'json',
            success: function(response) {
                // Fechar qualquer modal de edição que possa estar aberto
                if ($('#addItemModal').hasClass('ui-popup-active')) {
                    $('#addItemModal').popup('close');
                }
                
                if (response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        loadContractData(); // Recarregar os dados para refletir a alteração
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                // Fechar qualquer modal de edição que possa estar aberto
                if ($('#addItemModal').hasClass('ui-popup-active')) {
                    $('#addItemModal').popup('close');
                }
                
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro de comunicação com o servidor.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }









    // Inicializar o popup quando a página for mostrada
    if ($('#addItemModal').length) {
        $('#addItemModal').popup();
    }
    
    $('#addContractRow').on('click', function() {
        // Abrir o modal para adicionar novo item
        $('#addItemModal').popup('open');
    });
    
    // Função para formatar valor como moeda brasileira
    function formatarMoedaBR(valor) {
        // Remove tudo que não é número
        let v = valor.toString().replace(/\D/g, '');
        
        // Garante que tenha pelo menos 3 dígitos
        v = v.length < 3 ? '0' + v : v;
        
        // Divide em reais e centavos
        let cents = v.slice(-2);
        let reals = v.slice(0, -2);
        
        // Adiciona separador de milhar aos reais
        reals = reals.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        
        // Retorna no formato brasileiro
        return reals + ',' + cents;
    }

    // Função para converter valor formatado para número
    function valorParaNumero(valor) {
        // Remove separador de milhar e converte vírgula para ponto
        const cleanValue = valor.toString().replace(/\./g, '').replace(',', '.');
        return parseFloat(cleanValue) || 0;
    }
    
    // Manipular formatação do campo de valor unitário no modal de adição
    $('#novo_valor_unitario').on('blur', function() {
        let valor = $(this).val();
        if (valor && !isNaN(valor.replace(/[^\d,]/g, '').replace(',', '.'))) {
            // Remove tudo que não é dígito
            valor = valor.replace(/[^\d]/g, '');
            // Garante pelo menos 3 dígitos (adiciona zeros se necessário)
            while (valor.length < 3) {
                valor = '0' + valor;
            }
            // Aplica a formatação de moeda
            $(this).val(formatarMoedaBR(valor));
        } else {
            $(this).val('0,00');
        }
    });
    
    // Manipular formatação do campo de valor unitário no modal de adição (ao digitar)
    $('#novo_valor_unitario').on('input', function() {
        let valor = $(this).val().replace(/[^\d]/g, '');
        // Formata imediatamente com vírgula para os centavos
        if (valor.length >= 3) {
            $(this).val(formatarMoedaBR(valor));
        } else {
            // Mostra o valor digitado sem formatação até ter 3 dígitos
            $(this).val(valor);
        }
    });
    
    // Manipular o formulário do modal de adição
    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        
        const descricao = $('#nova_descricao').val();
        const qtd = $('#nova_qtd').val();
        const valor_unitario = $('#novo_valor_unitario').val();
        
        if (!descricao || qtd === '' || valor_unitario === '') {
            Swal.fire({
                title: 'Erro!',
                text: 'Preencha todos os campos obrigatórios.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Calcular valor total
        const valor_total = (parseFloat(qtd) * parseFloat(valor_unitario)).toFixed(2);
        
        // Enviar novo item para o servidor
        $.ajax({
            url: '/chacara_kelmy/php/contrato_manager.php?action=save_new_item',
            type: 'POST',
            data: {
                descricao: descricao,
                qtd: parseInt(qtd),
                valor_unitario: parseFloat(valor_unitario),
                valor_total: parseFloat(valor_total)
            },
            dataType: 'json',
            success: function(response) {
                // Fechar o modal antes de mostrar o alerta
                $('#addItemModal').popup('close');
                
                if (response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Limpar o formulário após fechar o alerta
                        $('#addItemForm')[0].reset();
                        $('#nova_qtd').val('1');
                        $('#novo_valor_unitario').val('0,00');
                        
                        // Recarregar os dados para refletir a alteração
                        loadContractData();
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Reabrir o modal se houver erro e o usuário quiser tentar novamente
                        // (opcional, dependendo da experiência desejada)
                    });
                }
            },
            error: function() {
                // Fechar o modal antes de mostrar o alerta de erro
                $('#addItemModal').popup('close');
                
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro de comunicação com o servidor.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
    
    // Inicializar o popup de edição quando a página for mostrada
    if ($('#editItemModal').length) {
        $('#editItemModal').popup();
    }
    
    // Manipular o botão de cancelar do modal de adição
    $('#cancelAddItem').on('click', function() {
        $('#addItemModal').popup('close');
        $('#addItemForm')[0].reset();
        $('#nova_qtd').val('1');
        $('#novo_valor_unitario').val('0,00');
    });
    
    // Manipular o botão de cancelar do modal de edição
    $('#cancelEditItem').on('click', function() {
        $('#editItemModal').popup('close');
        $('#editItemForm')[0].reset();
        $('#edit_valor_unitario').val('0,00');
    });
    
    // Manipular formatação do campo de valor unitário no modal de edição
    $('#edit_valor_unitario').on('blur', function() {
        let valor = $(this).val();
        if (valor && !isNaN(valor.replace(/[^\d,]/g, '').replace(',', '.'))) {
            // Remove tudo que não é dígito
            valor = valor.replace(/[^\d]/g, '');
            // Garante pelo menos 3 dígitos (adiciona zeros se necessário)
            while (valor.length < 3) {
                valor = '0' + valor;
            }
            // Aplica a formatação de moeda
            $(this).val(formatarMoedaBR(valor));
        } else {
            $(this).val('0,00');
        }
    });
    
    // Manipular formatação do campo de valor unitário no modal de edição (ao digitar)
    $('#edit_valor_unitario').on('input', function() {
        let valor = $(this).val().replace(/[^\d]/g, '');
        // Formata imediatamente com vírgula para os centavos
        if (valor.length >= 3) {
            $(this).val(formatarMoedaBR(valor));
        } else {
            // Mostra o valor digitado sem formatação até ter 3 dígitos
            $(this).val(valor);
        }
    });
    
    // Manipular o formulário do modal de edição
    $('#editItemForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#edit_item_id').val();
        const descricao = $('#edit_descricao').val();
        const qtd = $('#edit_qtd').val();
        const valor_unitario = valorParaNumero($('#edit_valor_unitario').val());
        
        if (!id || !descricao || qtd === '' || isNaN(valor_unitario) || valor_unitario < 0) {
            Swal.fire({
                title: 'Erro!',
                text: 'Preencha todos os campos obrigatórios corretamente.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Calcular valor total
        const valor_total = (parseFloat(qtd) * valor_unitario).toFixed(2);
        
        // Enviar atualização para o servidor
        $.ajax({
            url: '/chacara_kelmy/php/contrato_manager.php?action=update_item',
            type: 'POST',
            data: {
                id: parseInt(id),
                descricao: descricao,
                qtd: parseInt(qtd),
                valor_unitario: valor_unitario,
                valor_total: parseFloat(valor_total)
            },
            dataType: 'json',
            success: function(response) {
                // Fechar o modal antes de mostrar o alerta
                $('#editItemModal').popup('close');
                
                if (response.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Limpar o formulário após fechar o alerta
                        $('#editItemForm')[0].reset();
                        $('#edit_valor_unitario').val('0,00');
                        
                        // Recarregar os dados para refletir a alteração
                        loadContractData();
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                // Fechar o modal antes de mostrar o alerta de erro
                $('#editItemModal').popup('close');
                
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro de comunicação com o servidor.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Função para abrir o modal de edição com os dados do item
    function editContractItem(id) {
        // Carregar os dados do item para edição
        $.ajax({
            url: '/chacara_kelmy/php/contrato_manager.php?action=get_contract',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const item = response.data.find(i => i.id == id);
                    if (item) {
                        // Preencher o formulário com os dados do item
                        $('#edit_item_id').val(item.id);
                        $('#edit_descricao').val(item.descricao);
                        $('#edit_qtd').val(item.qtd);
                        // Converter o valor para centavos e aplicar formatação
                        const valorEmCentavos = Math.round(parseFloat(item.valor_unitario) * 100).toString();
                        $('#edit_valor_unitario').val(formatarMoedaBR(valorEmCentavos));
                        
                        // Abrir o modal de edição
                        $('#editItemModal').popup('open');
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Item não encontrado.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Erro ao carregar dados do item para edição.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    // Manipular clique nos botões de editar da lista visual
    $(document).on('click', '.edit-item-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        // Chamar a função para abrir o modal de edição
        editContractItem(id);
    });

    // Manipular clique nos botões de excluir da lista visual
    $(document).on('click', '.delete-item-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        // Confirmar e excluir o item
        confirmDeleteContractItem(id);
    });

    // Carregar os dados iniciais
    loadContractData();
});


// --- 5. LÓGICA GLOBAL (Logout) ---
$(document).on('click', '#logoutBtn', function(e) {
    e.preventDefault();
    if (confirm('Tem certeza que deseja sair?')) {
        $.ajax({
            url: '/chacara_kelmy/php/agenda_manager.php?action=logout',
            type: 'GET',
            success: function() {
                window.location.href = '/chacara_kelmy/admin/';
            }
        });
    }
});

// --- 6. LÓGICA ADICIONAL PARA PÁGINA DE GRÁFICOS (se existir) ---
// Funções auxiliares para formatação de moeda (já definidas acima)