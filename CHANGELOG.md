## [3.1.3] - 2024-08-05

### Added
- Método Click2pay 1.3.0

### Fixed
- No checkout_order_processed salvava session de forma errada


## [3.1.2] - 2024-03-22

### Added
-Método Pagbank por Pagbank V 1.1.0


## [3.1.1] - 2023-12-18

### Fixed
- Botão de reenvio para o HPOS


## [3.1.0] - 2023-12-01

### Added
- Adequação para HPOS


## [3.0.20] - 2023-11-01

### Added
- Adequação para HPOS


## [3.0.19] - 2023-10-06

### Added
- Método PayPal mas sem dados de cartão.


## [3.0.18] - 2023-09-20

### Removed
- Tirado o SOAP para acesso aos Correios.

### Added
- Retry no método Consulta_status


3.0.17 - 03/08/23
- Acionado webhook de novo status após aprovação.
- Melhorias nas mensagens de depuração.
- Só altera para inanalisis se status anterior for NVO.

3.0.16 - 25/07/23
- Adequações para loja do Wordpress

3.0.15 - 11/07/23
- Adequação para loja de plugins do Wordpress, tirado default_timezone do método ajax.

3.0.14 - 04/05/23
- Agora apenas 3 tentativas para reenvio de pedidos.

3.0.13 - 12/04/23
- Pegando request-ID do retorno da ClearSale de outra forma para eliminar erro com PHP 8.

3.0.12 - 14/03/23
- Aviso de in-analisis só com o sucesso da integração.
- Botão reenvia só aparece com pedidos que deram erro ao integrar.
- No hook thankyou verificado quantas vezes foi passado pelo sendOrder, se maior igual que 1 e com dados de cartão integramos o pedido.
- Alterado forma de pegar o nonce_mbox na rotina de admin para montar botão de
reenvio.
- Feito método para pegar status do pedido usado em Cs_total_thankyou.
- Qdo data de aniversário (billing.birthDate) o ano for de 2 digitos não enviar.
- Sanitizado alguns 'echo's em alguns códigos que faltavam.

3.0.11 - 15/02/23
- Usando wp_remote_request no Atualiza_status, Inclui_pedido e Accounts.
- Sanitizado alguns campos restantes pegos por POST.
- No método Cs_total_thankyou pegamos o último status da CS de notas, caso o pedido não tenha sido enviado ainda.

3.0.10 - 28/11/22
- Usando wp_remote_post em Clearsale_Total_Api::Autenticate()
- Dentro do pedido temos uma forma de fazer o reenvio agora. (20/12/22)

3.0.9 - 09/11/22
- Colocado método Pagar-me pela Pagar-me, dados de cartão, boleto e PIX.

3.0.8 - 27/10/22
- Colocado método Pagseguro-Claudio Sanches pegando dados de cartão.

3.0.7 - 20/09/22
- Colocado método Loja5-eRede pegando dados de cartão.
- Tratado fração na quantidade de itens, pegamos parte inteira, se
 quantidade for zero não enviar o amount.
- Sair sem salvar pedido no método Cs_total_thankyou, só envia pedido pago.

3.0.6 - 06/09/22
- Colocado método Openpix, apenas PIX.
- Implementado retry no método Inclui_pedido.

3.0.5 - 16/08/22
- Colocado método Iugu - Gateway de pagamento da iugu para WooCommerce.
 Versão 3.0.0.11 - Boleto, PIX e Cartão com dados
- Corrigido método Cielo loja5, não pegava dados de cartão, testado na versão
 5.0-loja5.com.br. Boleto, Pix, Débito e Crédito com dados de cartão.

3.0.4 - 20/07/22
- Melhorado como pegar número do endereço de "retira na loja", olhando o
 woocommerce_store_address e o woocommerce_store_address2

3.0.3 - 13/07/22
- Alterado método de ajax e javascript para permitir novos métodos de pgtos que
 não salvam dados no postmeta, criada tabela cs_total_dadosextras.
- Integrado os métodos abaixo pegando dados de cartão
- Gerencianet (via ajax) V 1.4.7 crédito|boleto|pix
- Cielo API 3.0 (via postmeta) V 4.0 | Por Loja5.com.br

3.0.2 - 03/06/22
- Colocando installments de cartão no json corretamente.

3.0.1 - 03/05/22
- Alterada a forma de fazer o callback para status, agora é loja.com/wc-api/clearsale_total_status/

2.5.1 - 11/04/22
- Colocado método Asaas - crédito, boleto e Pix, sem pegar dados de cartão.

2.5.0 - 14/03/22
- Agora perguntando qual status vamos colocar quando o pedido for aprovado pela ClearSale.
- Colocado método juno-pix e o wc_pagarme_pix_payment.

2.4.6 - 22/02/22
- Em determinadas situações não pegava número da rua, tanto em shipping quanto em billing. Se tem Brazilian extra fields salvamos o DOB e bairro do billing.

2.4.5 - 19/01/22
- Colocado método Rede de MarcosAlexandre - V2.1.1 e iPag Payment Gateway for WooCommerce. Versão 2.1.4 | Por iPag Pagamentos Digitais. Ambos pegando dados de cartão.

2.4.4 - 16/12/21
- Tirado o JS do mapper, sem uso, não precisava ficar ativo.

2.4.3 - 10/11/21
- Em algumas situações os Correios retorna NULL no bairro quando não encontra o CEP, método Busca_Bairro_ws.

2.4.2 - 10/09/21
- Colocado métodos do Mercado Pago payments V 5.2.1. Pix e boleto.

2.4.1 - 25/08/21
- No caso de internacionalização vamos olhar o país apenas no billing, podendo no shipping não ter conteúdo no campo país.
  Colocado o novo método PIX da Piggly - V 1.3.15
  Adicionado log remoto de instalação e desinstalação, salvando apenas url, versão e datetime destes eventos.
  Adicionada versão do plugin no topo da tela de configurações.

2.4.0 - 23/07/21
- Adicionado novo método do e.Rede, de nome "erede" apenas para crédito.
  Version 1.0 | By e.rede - Tipo_pagamento: tipo=erede

2.3.1 - 19/07/21
- Desligado o required do wordpress para o campo cs_field_doc da classe extrafields.

2.3.0 - 08/07/21
- Verificado o país do cliente (billing e shipping), se não for Brasil, não consistimos o Cpf/Cnpj e não
  integramos o pedido na ClearSale, o nosso campo de documento aparece "Válido no Brasil", se usar o plugin
  woo_extra_fields_bra (Brazilian Market on WooCommerce) e o país não for BR não consistimos o documento digitado.
  Repassado método checkVersion para pegar erro quando não tem SOAP. Os erros não são traduzidos, pois o plugin não é ativado quando dá erro.

2.2.2 - 21/06/21
- Inserido mais um método de pagamento:
  bp_boleto_santander - Boleto Santander - Versão 1.3.1 | Por Rodrigo Max / Agência BluePause

2.2.1 - 16/06/21
- Inserido os novos métodos de pagamentos:
  Pix - loja5 - loja5_woo_pix_estatico - Integração aos Pagamentos Pix Estático - V 1.0
  Boleto - loja5 - Banco do Brasil - Boleto - Integração aos Pagamentos Banco do Brasil Ecommerce. - V 1.0
  Boleto - Juno - Boleto - Juno para WooCommerce - Versão 2.3.3

2.2.0 - 25/03/21
- Tirado da coluna de status e dentro do pedido tb. o "Esperando aprovação do Pagamento".
- Colocado na coluna de status, no lugar das siglas (APA,RPM) o nome curto, a descrição completa só dentro do pedido.
- Quando tem um cancelamento pela Clear, pelos status FRD, RPA, RPP, RPM, SUS e CAN recolocamos estes status mesmos e NÃO o CAN apenas.
- Perdia a session do carrinho ao gravar pedido (apartir da 2.0) agora salvamos a session do carrinho para enviar qdo integra o pedido, assim o FP fica correto.
- Em public/status.php retornava 404 em caso de acesso inválido, mudado para 400 Bad Request.

2.1.1 - 09/03/21
- Pegando e-mail do billing quando compra é por visitante. Não tem o customer e o email vai ser o do cadastro do billing.

2.1.0 - 10/2/21
- Agora contempla método de pagamento Payzen Payment for WooCommerce. Versão 1.0.27 | Por iPag.

2.0.0 - 18/11/20
- Agora enviando pedidos para ClearSale na mudança de status (qdo pedido foi pago) não mais no fechamento do pedido.
- Alterado sintaxe na rotina de validar CPF e CNPJ, para não dar erro de deprecated.
- Quando for reembolso não mandar chargeback para ClearSale.
- WooCommerce pagamento na entrega - V 1.3.2 - Carlos Ramos - dinheiro=woo_payment_on_delivery - 14/12/20.
- Cielo API 3.0 - Loja 5 - Plugin V 3.0 - débito = loja5_woo_cielo_webservice_debito crédito=loja5_woo_cielo_webservice
  boleto=loja5_woo_cielo_webservice_boleto.
- Após aprovação muda status do pedido do Woo para processing!

1.3.2 - 13/11/20
- Voltamos a pegar pedidos pelo hook checkout_order_processed também, junto com thankyou.

1.3.1 - 26/08/20
- Incluído mensagem dos hooks acionados no checkout.
- Testa a existência de tonocheckout na rotina que vai no footer.
- Alterado o tipo de pgto de 14 para 11, quando for transferência e pgto em dinheiro.
- Ao buscar um bairro, usando os Correios, em caso de falha, retornar o bairro com BRANCOS e não nulo.

1.3.0 - 21/08/20
- Mensagem de responsabilidade qdo os pedidos NÃO são cancelados em caso de reprovação.

1.2.1 - 27/07/20
- Consistência de PF e PJ não estava funcionando, colocava sempre PJ.

1.2.0 - 20/07/20
- Inserido opção para lojista cancelar pedido se não foi aprovado pela ClearSale
- No log, quando pegar as chaves, aparece a versão do plugin.
- Para pegar dados do pedido, no fechamento, usamos agora o hook woocommerce_thankyou
- Pegando método do PagSeguro - Claudio Sanches

1.1.6 - 10/07/20
- Pegando o método do e.Rede API - Versão 1.0 - Cartão de Crédito - loja5.

1.1.5 - 11/02/20
- Alterado timeout e exception no soap com os correios, na rotina que pega o bairro dado o CEP.

1.1.4 - 28/01/20
- Pegando o método de pgto Cielo Webservice API 3.0 - Jrossetto.
- No checkout o metodo $order->get_meta('cs_doc') não pegava o # do doc, no caso de falha usado o get_post_meta($order_id, 'cs_doc', true)
 
1.1.3 - 22/10/19
- Pegando o método de pgto PagHiper (apenas boleto), mais informação de log no authenticate.

1.1.2 - 07/10/19
- Pegando o método de pgto boleto e crédito da pagar.me.

1.1.1 - 02/10/19
- Diferenciado o Cielo-Checkout de débito|crédito|boleto.
- Diferenciado o débito e crédito do Cielo-webservices.
- Logando tipo de pagamento, colocando correto o tipo quando for Rede e Cielo Checkout.

1.1.0 - 15/09/2019
- Colocado compatibilidade com woocommerce-extra-checkout-fields-for-brazil.

1.0.0 - 02/07/2019
- Versão inicial. Integração com APIs da ClearSale e integração total com PagSeguro oficial do UOL.
