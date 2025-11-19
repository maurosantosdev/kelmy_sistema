<?php
// Guia para testar o webhook com pagamentos reais

echo "=== GUIA PARA TESTAR O WEBHOOK COM PAGAMENTOS REAIS ===\n\n";

echo "1. FAZER UMA RESERVA REAL:\n";
echo "   - Acesse o sistema de reservas\n";
echo "   - Crie uma nova reserva com datas válidas\n";
echo "   - Complete o pagamento via PIX ou outro método do Mercado Pago\n";
echo "   - O sistema criará uma cobrança no Mercado Pago com external_reference contendo os IDs das reservas\n\n";

echo "2. O QUE ACONTECE DURANTE O PAGAMENTO:\n";
echo "   - O sistema cria reservas no banco com status 'pendente'\n";
echo "   - O sistema cria um pagamento no Mercado Pago com external_reference como 'res_abc123,res_def456'\n";
echo "   - O cliente realiza o pagamento no Mercado Pago\n\n";

echo "3. O QUE ACONTECE APÓS O PAGAMENTO:\n";
echo "   - Quando o pagamento é confirmado, o Mercado Pago envia uma notificação para seu webhook\n";
echo "   - O webhook recebe o ID real do pagamento (não mais o falso '123456')\n";
echo "   - O webhook busca os detalhes do pagamento na API do Mercado Pago\n";
echo "   - O webhook obtém o external_reference com os IDs das reservas\n";
echo "   - O webhook atualiza o status das reservas para 'confirmado'\n";
echo "   - O webhook atualiza o campo payment_confirmed_at\n";
echo "   - O webhook atualiza a agenda para 'reservado'\n\n";

echo "4. MONITORAMENTO:\n";
echo "   - Durante o pagamento, monitore os logs com:\n";
echo "     tail -f /var/log/apache2/error.log | grep -i webhook\n\n";

echo "5. VERIFICAR RESULTADOS:\n";
echo "   - Após o pagamento confirmado, verifique os status das reservas:\n";
echo "     SELECT id, status, payment_confirmed_at FROM reservas WHERE status = 'confirmado';\n\n";

echo "6. LEMBRETE:\n";
echo "   - Os testes no painel do Mercado Pago usam IDs falsos (como '123456') e não atualizarão as tabelas\n";
echo "   - Somente pagamentos reais do Mercado Pago acionarão o fluxo completo de atualização\n\n";

echo "Agora você está pronto para testar com pagamentos reais!\n";