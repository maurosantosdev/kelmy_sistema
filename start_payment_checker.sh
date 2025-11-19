#!/bin/bash

# Script para iniciar o serviço de verificação de pagamentos
# Este script pode ser adicionado ao crontab para iniciar automaticamente após reinicialização

cd /var/www/chacararecantodosossegorr.com.br/chacara_kelmy

# Verificar se o processo já está rodando
if pgrep -f "php payment_check_service.php" > /dev/null
then
    echo "$(date): Serviço de verificação de pagamento já está rodando."
else
    echo "$(date): Iniciando serviço de verificação de pagamento..."
    nohup php payment_check_service.php > payment_check_service.log 2>&1 &
    echo "$(date): Serviço de verificação de pagamento iniciado com PID $!"
fi