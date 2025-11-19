# Configuração do Webhook do Mercado Pago

Este documento explica como configurar corretamente o webhook do Mercado Pago para receber notificações de pagamentos, incluindo Pix.

## Passos para Configuração

### 1. Acessar o Painel do Mercado Pago

1. Acesse https://www.mercadopago.com.br/settings/credentials
2. Faça login com sua conta Mercado Pago
3. Vá até a seção de **Webhooks** (geralmente em "Integrações" ou "Credenciais")

### 2. Gerar ou Obter o Webhook Secret

1. Na seção de Webhooks, localize a opção para gerar ou visualizar o **Webhook Secret**
2. Clique em "Editar" ou "Gerar" para criar um novo Webhook Secret
3. Copie o valor fornecido (geralmente uma string longa de caracteres)

### 3. Configurar o Webhook Secret no Sistema

Execute o seguinte comando no terminal, substituindo `SEU_WEBHOOK_SECRET` pelo valor copiado do painel do Mercado Pago:

```bash
php update_mp_webhook_secret.php SEU_WEBHOOK_SECRET
```

### 4. Configurar a URL do Webhook no Painel do Mercado Pago

No painel do Mercado Pago:

1. Na seção de Webhooks, informe a URL:
   ```
   https://chacararecantodosossegorr.com.br/chacara_kelmy/webhook-mercado-pago.php
   ```
2. Certifique-se de que os eventos de pagamento estejam selecionados:
   - payment.created
   - payment.updated
3. Salve as configurações

### 5. Verificar a Configuração

1. Faça um pagamento de teste via Pix
2. Verifique os logs do servidor para confirmar que o webhook está sendo chamado:
   ```bash
   tail -f /var/log/apache2/error.log | grep -i webhook
   ```
3. Verifique se o status da reserva é atualizado após o pagamento

### 6. Solução de Problemas Comuns

#### Webhook não é chamado:

- Verifique se o domínio está acessível publicamente
- Confirme que SSL/TLS está configurado corretamente
- Verifique se a URL no painel do Mercado Pago está exatamente como:
  `https://chacararecantodosossegorr.com.br/chacara_kelmy/webhook-mercado-pago.php`

#### Assinatura inválida:

- Confirme que o Webhook Secret foi configurado corretamente
- Verifique se o mesmo secret está no sistema e no painel do Mercado Pago

#### Reserva não é atualizada:

- Verifique se o campo `external_reference` está sendo preenchido corretamente nos pagamentos
- Confirme que os IDs das reservas estão no formato `res_{id}`

## Teste de Configuração

Para testar rapidamente se o webhook está recebendo requisições, você pode usar ferramentas como:

- Ferramentas de inspeção de rede para verificar chamadas ao webhook
- Verificar os logs do servidor em `/var/log/apache2/error.log`
- Usar serviços como ngrok para testar localmente (em ambiente de desenvolvimento)

## Importante

- O webhook Secret deve ser mantido em segredo e não deve ser exposto em repositórios públicos
- Em ambiente de produção, certifique-se de que o servidor tenha conexão HTTPS válida
- O sistema armazena o Webhook Secret no arquivo `php/mp_webhook_secret.php`

## Eventos Tratados

O webhook está configurado para tratar os seguintes eventos do Mercado Pago:

- `payment.created` - Quando um pagamento é criado
- `payment.updated` - Quando um pagamento tem seu status alterado (aprovado, recusado, etc.)

Estes eventos incluem pagamentos via Pix, cartão de crédito, boleto, etc.