<?php
// Script para monitorar o webhook durante pagamentos reais

echo "=== MONITORAMENTO DO WEBHOOK PARA PAGAMENTOS REAIS ===\n\n";

echo "Para testar com pagamentos reais, siga estes passos:\n\n";

echo "1. ANTES DE FAZER O PAGAMENTO:\n";
echo "   Execute este comando para monitorar os logs em tempo real:\n";
echo "   tail -f /var/log/apache2/error.log | grep -i -E 'webhook|reserva|payment|status|confirmado'\n\n";

echo "2. DURANTE O PAGAMENTO:\n";
echo "   - Faça uma reserva real no sistema\n";
echo "   - Complete o pagamento via PIX ou outro método\n";
echo "   - Observe os logs para ver:\n";
echo "     * Recebimento da notificação do Mercado Pago\n";
echo "     * Busca dos detalhes do pagamento\n";
echo "     * Extração do external_reference com IDs das reservas\n";
echo "     * Atualização do status das reservas para 'confirmado'\n";
echo "     * Atualização do campo payment_confirmed_at\n";
echo "     * Atualização da agenda para 'reservado'\n\n";

echo "3. DEPOIS DO PAGAMENTO:\n";
echo "   Verifique no banco de dados:\n";
echo "   SELECT id, status, payment_confirmed_at FROM reservas WHERE status = 'confirmado';\n\n";

echo "   E verifique a agenda:\n";
echo "   SELECT * FROM agenda WHERE status = 'reservado';\n\n";

echo "O webhook está pronto para processar pagamentos reais!\n";
echo "Lembre-se: IDs de teste como '123456' não funcionarão, apenas IDs reais de pagamentos.\n";