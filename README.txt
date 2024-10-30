=== ClearSale Total ===
Contributors: lettitec
Donate link: 
Tags:  fraud, fraud protection, prevent fake orders, e-commerce, woocommerce, sell, store, loja virtual, shop, clearsale, antifraude, análise por inteligência artificial, equipe de detecção de fraude, cartão de crédito
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 3.1.3
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integração do WooCommerce com a ClearSale.
testado: wordpress 6.6
woocommerce 9.1
Requisitos:
php 5.6 ou maior

== Description ==

= Sobre o produto =

Garanta uma solução antifraude completa para o seu e-commerce. Com as soluções antifraude ClearSale, a sua loja tem a máxima proteção na análise de pedidos, combinando as tecnologias mais avançadas de inteligência artificial e machine learning com a ação do nosso time especializado.
As nossas soluções antifraude utilizam-se de diversas variáveis como CPF, CEP, e-mail e device para fazer uma análise do perfil do cliente e decidir se aquela transação é fraudulenta ou legítima.
Essas verificações são feitas de forma rápida para proporcionar uma melhor experiência ao cliente e aumentar a aprovação de pedidos legítimos, identificando qualquer sinal de possível fraude.
A análise é feita através de uma inteligência artificial, atribuindo um score a cada transação.
A ferramenta ClearSale utiliza modelos estatísticos específicos para cada segmento e faz a aprovação automaticamente, se nenhum risco for identificado. Essa decisão automatizada com eficácia é possível, pois somos a empresa que mais conhece o comportamento do consumidor digital brasileiro, com um Data Lake que conhece 97% dos CPFs que transacionam no ambiente digital.
Com a nossa solução, também é possível incluir a possibilidade de uso de segundo fator de autenticação, via SMS ou WhatsApp. Com essa camada extra de segurança, o cliente recebe uma mensagem em seu telefone seguro (hotphone) para confirmar se reconhece ou não a transação que está em andamento, aumento a aprovação com assertividade e baixa fricção.
A solução ClearSale é perfeita para todo e qualquer segmento atuante no e-commerce. Principalmente se você procura aumentar a lucratividade da sua loja, aprovando mais pedidos com uma maior segurança.

[Clique aqui](https://br.clear.sale/cases/desinch%C3%A1) e conheça um case de sucesso de utilização de nosso produto

= Sobre preços =

A precificação de nosso produto depende do seu volume de vendas e como você pretende usar a nossa solução, baseado no perfil da sua indústria e suas necessidades específicas.

= Como eu entro em contato com a ClearSale? =

Para contratar nossos serviços, entre em contato com os especialistas ClearSale, através [deste link.](https://br.clear.sale/contato#conhecer-os-produtos)
Mas se você já for cliente da ClearSale, disponibilizamos um FAQ com [dúvidas frequentes.](https://br.clear.sale/faq) Caso sua dúvida ainda não tenha sido respondida, entre em contato conosco, através dos nossos canais de atendimento.

Além disso, nós da ClearSale atuamos com total transparência e responsabilidade no tratamento dos dados e, por esse motivo, compartilhamos um conteúdo que mostra como estamos inseridos na LGPD.
Para acessar o conteúdo, clique [aqui.](https://br.clear.sale/sobrenos/LGPD)

= Sobre a ClearSale =

A ClearSale (CLSA3) é referência em inteligência de dados com múltiplas soluções para prevenção a riscos em diferentes mercados, como e-commerce, mercado financeiro, vendas diretas, telecomunicações, entre outros. É a empresa que mais conhece o comportamento do consumidor digital brasileiro, o que a faz impulsionar negócios em todo o ecossistema da economia digital.
Por meio do seu time de especialistas, a ClearSale tem o propósito de gerar um efeito de rede de proteção no mercado digital, identificando padrões de ataques, protegendo os mais variados segmentos de negócio e promovendo impacto positivo, para garantir a melhor experiência ao usuário, com mínima fricção, do onboarding ao transacional.


== Installation ==

Certifique-se de que não há instalação de outros módulos da ClearSale em seu sistema;
Baixe o arquivo clearsale-total.zip;
Na área administrativa de seu WordPress acesse o menu Plugins -> Adicionar Novo -> Enviar/Fazer upload do plugin ->
->escolher arquivo, ache o caminho do arquivo clearsale-total.zip e selecione Instalar Agora;
Após a instalação selecione Ativar plugin;
[Clique aqui para tutorial on line](https://api.clearsale.com.br/docs/plugins/wooCommerce/totalTotalGarantidoApplication)

== Configurations ==

Para acessar "CONFIGURAÇÕES" do módulo acesse, na área administrativa de seu WordPress, Configurações -> ClearSale Total.
 
As opções disponíveis estão descritas abaixo.

    Selecione entre ambiente de teste e produção (Defina se está no modo homologação ou produção)

    Digite login e senha fornecidos pela ClearSale
 
    Digite o Fingerprint fornecido pela ClearSale, 
        Você deve ter um número parecido com este: a6s8h29ym6xgm5qor3sk

    Informar a URL que aparece no final da tela de configuração para a ClearSale, com isto a loja recebe as aprovações de compras analisadas.

[Veja a tela 10 do tutorial](https://api.clearsale.com.br/docs/plugins/wooCommerce/totalTotalGarantidoApplication#configuration-section)

== Frequently Asked Questions ==

[Aqui, em FAQ, temos as dúvidas mais frequentes](https://br.clear.sale/total)

== Screenshots ==

== Changelog ==
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


## [3.0.17] - 2023-08-03
### Added
- Acionado webhook de novo status após aprovação.
- Melhorias nas mensagens de depuração.

### Changed
- Só altera para inanalisis se status anterior for NVO.


== Arbitrary section ==

