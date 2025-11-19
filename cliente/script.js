// Aguarda a página de INFORMAÇÕES ser inicializada
$(document).on('pageshow', '#infoPageCliente', function() {
    // Remover swiper existente se já estiver inicializado
    if ($('.swiper-container')[0]) {
        if ($('.swiper-container')[0].swiper) {
            $('.swiper-container')[0].swiper.destroy(true, true);
        }
    }
    
    // Inicializar o Swiper após um pequeno atraso para garantir carregamento completo
    setTimeout(function() {
        var swiper = new Swiper('.swiper-container', {
            loop: true,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            on: {
                init: function() {
                    console.log('Swiper inicializado com navegação');
                }
            }
        });
    }, 100);
});