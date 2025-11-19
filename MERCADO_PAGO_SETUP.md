# Integração Mercado Pago - Chácara Recanto do Sossego

## Configuração no Servidor Linux com HTTPS

### 1. Pré-requisitos
- Servidor com HTTPS configurado (obrigatório para webhooks do Mercado Pago)
- PHP 7.4+ instalado
- Composer instalado

### 2. Instalação do SDK do Mercado Pago
Execute no diretório raiz do projeto:

```bash
composer require mercadopago/dx-php
```

### 3. Configuração do Access Token
1. Acesse sua conta PF no Mercado Pago
2. Vá para Developers > Minha conta > Credenciais
3. Copie o Access Token para produção ou sandbox

4. Atualize o arquivo `php/mp_config.php`:
```php
define('MP_ACCESS_TOKEN', 'SEU_TOKEN_AQUI');
define('MP_SANDBOX', false); // Mude para true para testes
```

### 4. Configuração do Webhook
O arquivo `webhook-mercado-pago.php` deve ser acessível via HTTPS.
Exemplo: `https://seudominio.com.br/webhook-mercado-pago.php`

O webhook agora está configurado para:
- Receber requisições POST com dados JSON do Mercado Pago
- Processar corretamente eventos de pagamento atualizado (`payment.updated`)
- Verificar a assinatura da requisição para validação de autenticidade
- Aceitar IDs de pagamentos reais do Mercado Pago (IDs de teste como '123456' retornarão 404, o que é esperado)
- Responder com status 200 OK e formato JSON para confirmar recebimento
- Atualizar automaticamente o status das reservas no banco de dados quando o pagamento for confirmado
- Atualizar o campo 'payment_confirmed_at' e o status da agenda para 'reservado'

### 5. URLs de Retorno (Opcional)
No painel do Mercado Pago, configure:
- URL de sucesso: `https://seudominio.com.br/cliente/reserva.html`
- URL de pagamento pendente: `https://seudominio.com.br/cliente/reserva.html`
- URL de pagamento rejeitado: `https://seudominio.com.br/cliente/reserva.html`

### 6. Importante para Conta PF
- A conta PF no Mercado Pago tem limites diários e mensais de recebimento
- PIX com conta PF é totalmente funcional
- Taxas podem variar conforme volume de transações
- Certifique-se de que sua conta PF está verificada e ativada para recebimentos

### 7. Testes
1. Para testes, use o modo sandbox:
   - Altere `MP_SANDBOX` para `true` em `mp_config.php`
   - Use um Access Token de sandbox

2. Para produção:
   - Altere `MP_SANDBOX` para `false`
   - Use o Access Token de produção

### 8. Segurança
- Mantenha o Access Token em segredo
- Não cometa com tokens expostos
- Use variáveis de ambiente em produção se possível

### 9. Monitoramento
- Os webhooks são registrados no log do PHP
- Verifique os logs em caso de problemas
- O sistema atualiza automaticamente o status das reservas

### 10. Estrutura de Arquivos
```
/chacara_kelmy/
├── /php/
│   ├── mp_config.php          # Configurações do Mercado Pago
│   ├── mp_init.php            # Inicialização do SDK
│   ├── create_mp_reservation.php # Criação de cobrança
│   ├── check_mp_payment_status.php # Verificação de status
│   └── finalize_reservation.php # Finalização da reserva
├── /cliente/
│   └── script.js              # Atualizado para Mercado Pago
├── webhook-mercado-pago.php   # Recebe notificações do MP
└── ...
```

### 11. Solução de Problemas
- Se receber erro 403 no webhook: verifique se a URL é HTTPS
- Se cobrança não é criada: verifique o Access Token
- Se status não atualiza: verifique se o webhook está acessível publicamente
- Para contas PF: verifique se sua conta está verificada e com recebimento ativado