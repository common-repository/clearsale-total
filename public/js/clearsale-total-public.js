var tonocheckout=0;

(function( $ ) {
	'use strict';

// Pagseguro v 2.0.0
//l 259 - template/direct-payment.php
$(document).ready(function(){
	$('.btn-form').on('click', function(e) {
		//e.preventDefault();
		console.log("Ajax:ClearSale:PagSeguro");

		var metodo = 1; // pgseguro oficial
		var card = $('#card_num').val();
		var card_installments = $('#card_installments').val();
		var card_holder = $('#card_holder_name').val();
		//var cvv = $('#card_cod').val();
		var cpf = $('#document-credit-card').val();
		var validate = $('#card_expiration_month').val() + "/" + $('#card_expiration_year').val();

		var order_id = $('#order').attr('data-target');
		//var card_holder_birthdate = $('#card_holder_birthdate').val();

		// se for boleto
		var document_boleto = $('#document-boleto').val();
		// cartao de debito
		var document_debit = $('#document-debit').val();

		var document = 0; // deveria se exclusivo
		if (cpf.length >0) {
			document = cpf;
			if (card.length < 19) {
				console.log("cartao credito < 19=" + card);
				return;	//# cartao invalido
			}
		}
		if (document_boleto.length >0) document = document_boleto;
		if (document_debit.length >0) document = document_debit;
		if (document.length <=0) {
			console.log("sem # de documento=" + document);
			return;
		}

		if (document.length <= 14 && validateCpf(document.toString()) === false) {
			console.log("cpf invalido=" + document);
			return; // cpf invalido
		} else if (document.length > 14 && document.length <= 18 && validateCnpj(document.toString()) === false) { // cnpj
			console.log("cnpj invalido=" + document);
			return;
		}

		console.log("entrou card=" + card + " nome=" + card_holder);
		console.log("documento=" + document);

		var dados_pagseg = {
			metodo: metodo,
			order: order_id,
			card: card,
			card_holder: card_holder,
			cpf: cpf,
			validate: validate,
			installments: card_installments,
			doc_boleto: document_boleto,
			doc_debito: document_debit
		};

		var dados_envio = {
			'clearsale_total_ajax_nonce': js_global.clearsale_total_ajax_nonce,
 			'pagseguro': dados_pagseg,
			'action': 'clearsale_total_push'
      		}

		jQuery.ajax({
			url: js_global.xhr_url,
			type: 'POST',
			data: dados_envio,
			dataType: 'JSON',
			success: function(response) {
				if (response == '401'  ){
					console.log('Requisição inválida')
				}
				else if (response == 402) {
					console.log('Todos os posts já foram mostrados')
				} else {
					console.log('resposta=' + response)
				}
			}
		});

	});

	function validateCpf(str) {
		str = str.replace('.','');
		str = str.replace('.','');
		str = str.replace('.','');
		str = str.replace('-','');
		var strCPF = str;
		var sum;
		var rest;
		sum = 0;

		var equal_digits = 1;

		for (i = 0; i < strCPF.length - 1; i++) {
			if (strCPF.charAt(i) != strCPF.charAt(i + 1)) {
				equal_digits = 0;
				break;
			}
		}

		if (!equal_digits) {
			for (var i = 1; i <= 9; i++) {
				sum = sum + parseInt(strCPF.substring(i-1, i)) * (11 - i);
			}

			rest = sum % 11;

			if ((rest == 0) || (rest == 1)) {
				rest = 0;
			} else {
				rest = 11 - rest;
			};

			if (rest != parseInt(strCPF.substring(9, 10)) ) {
				return false;
			}

			sum = 0;
			for (i = 1; i <= 10; i++) {
				sum = sum + parseInt(strCPF.substring(i-1, i)) * (12 - i);
			}

			rest = sum % 11;

			if ((rest == 0) || (rest == 1)) {
				rest = 0;
			} else {
				rest = 11 - rest;
			};

			if (rest != parseInt(strCPF.substring(10, 11) ) ) {
				return false;
			}
			return true;

		} else {
			return false;
		}
	};

	function validateCnpj(str) {
		str = str.replace('.','');
		str = str.replace('.','');
		str = str.replace('.','');
		str = str.replace('-','');
		str = str.replace('/','');
		var cnpj = str;
		var numbersVal;
		var digits;
		var sum;
		var i;
		var result;
		var pos;
		var size;
		var equal_digits;

		equal_digits = 1;

		if (cnpj.length < 14 && cnpj.length < 15) {
			return false;
		}

		for (i = 0; i < cnpj.length - 1; i++) {
			if (cnpj.charAt(i) != cnpj.charAt(i + 1)) {
				equal_digits = 0;
				break;
			}
		}

		if (!equal_digits) {
			size = cnpj.length - 2
			numbersVal = cnpj.substring(0,size);
			digits = cnpj.substring(size);
			sum = 0;
			pos = size - 7;
			for (i = size; i >= 1; i--)	{
				sum += numbersVal.charAt(size - i) * pos--;
				if (pos < 2) pos = 9;
			}
			result = sum % 11 < 2 ? 0 : 11 - sum % 11;
			if (result != digits.charAt(0))
				return false;
			size = size + 1;
			numbersVal = cnpj.substring(0,size);
			sum = 0;
			pos = size - 7;
			for (i = size; i >= 1; i--)	{
				sum += numbersVal.charAt(size - i) * pos--;
				if (pos < 2)
					pos = 9;
			}
			result = sum % 11 < 2 ? 0 : 11 - sum % 11;
			if (result != digits.charAt(1)) {
				return false;
			}

			return true;
		} else {
			return false;
		}
	};

	//botão de fechar pedido do Woo - pegar dados do place_order
    $('form.woocommerce-checkout').on('click', "#place_order", function(e) {
		console.log("Ajax:ClearSale:place_order entrou...");

		var card = null;
		var card_holder = null;
		var validate = null;
		var card_installments = null;
		var cpf = null;
		var metodo = null;
		var document_boleto = null;
		var document_debit = null;
		var document_pix = null;
		var checked_is = null;

		if (document.querySelector('input[name="payment_method"]:checked')) {
			checked_is = document.querySelector('input[name="payment_method"]:checked').value;
			console.log("checked is:" + checked_is);
			if (checked_is == "gerencianet_oficial") {
				console.log("gn checked");
				metodo = 4;
			}
			if (checked_is == "iugu-credit-card") {
				console.log("iugu checked");
				metodo = 6;
			}
			if (checked_is == "pagseguro") {
				console.log("pagseg-claudio checked");
				metodo = 8;
			}
			if (checked_is == "paypal-brasil-plus-gateway") {
				console.log("paypal-brasil-plus-gateway");
				metodo = 10;
			}
			if (checked_is == "pagbank_credit_card") {
				console.log("pagbank_credit_card");
				metodo = 11;
			}
			if (checked_is == "click2pay-credit-card") {
				console.log("click2pay-credit-card");
				metodo = 12;
			}
		}
		if (metodo == 4) {
			console.log("Ajax:ClearSale:place_order: é gerencianet");
			if (card == null) {
				card = $('#gn_card_number_card').val();
				if (card != null) {
						card_holder = $('#gn_card_name_corporate').val();
						validate = $('#gn_card_expiration_month').val() + "/" + $('#gn_card_expiration_year').val();
						card_installments = $('#gn_card_installments').val();
						cpf = $('#gn_card_cpf_cnpj').val();// CPF nao pede neste metodo
				}
			}
			document_pix = $('#gn_pix_cpf_cnpj').val();
			// gerencianet - boleto - nao pede doc
			if (document.querySelector('input[name="paymentMethodRadio"]:checked')) {
				var ck = document.querySelector('input[name="paymentMethodRadio"]:checked').value;
				console.log("boleto checado =" + ck);
				if (ck == "billet") {
					if ($('#billing_cnpj').val()) {
						document_boleto = $('#billing_cnpj').val();
					} else {
						document_boleto = $('#billing_cpf').val();
					}
				}
			}
		} // end if =4
		if (metodo == 6) {
			console.log("Ajax:ClearSale:place_order: é iugu");
			if (card == null) {
				card = $('#iugu-card-number').val();
				if (card != null) {
						card_holder = $('#iugu-card-holder-name').val();
						validate = $('#iugu-card-expiry').val();
						card_installments = 1; //nao pede
						if ($('#billing_cnpj').val()) {// CPF | CNPJ nao pede neste metodo
							cpf = $('#billing_cnpj').val();
						} else {
							cpf = $('#billing_cpf').val();
						}
				}
			}
		} // end of if =6
		if (metodo == 8) {
			console.log("Ajax:ClearSale:place_order: é pagseg-claudio");
			if (card == null) {
				card = $('#pagseguro-card-number').val();
				if (card != null) {
					card_holder = $('#pagseguro-card-holder-name').val();
					validate = $('#pagseguro-card-expiry').val(); // mm/aaaa
					card_installments = $('#pagseguro-card-installments').val();
					cpf = $('#pagseguro-card-holder-cpf-field').val();
				}
			}
		} // end of if =8
		if (metodo == 10) {
			console.log("Ajax:ClearSale:place_order: é paypal");
			// nada a fazer, tem iframe
		} // end of if =10
		if (metodo == 11) {
			console.log("Ajax:ClearSale:place_order: é Pagbank");
			if (card == null) {
				card = $('#pagbank_credit_card-card-number').val();
				if (card != null) {
					card_holder = $('#pagbank_credit_card-card-holder').val();
					validate = $('#pagbank_credit_card-card-expiry').val(); // mm/yy
					card_installments = $('#pagbank_credit_card-installments').val();
					if (card_installments == null) card_installments = 1;
					if ($('#billing_cnpj').val()) {// CPF | CNPJ nao pede neste metodo
						cpf = $('#billing_cnpj').val();
					} else {
						cpf = $('#billing_cpf').val();
					}
				}
			}
		} // end of if =11
		if (metodo == 12) {
			console.log("Ajax:ClearSale:place_order: é click2pay");
			if (card == null) {
				card = $('#click2pay-credit-card-card-number').val();
				if (card != null) {
					card_holder = $('#click2pay-credit-card-card-holder').val();
					validate = $('#click2pay-credit-card-card-expiry').val(); // mm/yy
					card_installments = $('#click2pay-credit-card-card-installments').val();
					if (card_installments == null) card_installments = 1;
					if ($('#billing_cnpj').val()) {// CPF | CNPJ nao pede neste metodo
						cpf = $('#billing_cnpj').val();
					} else {
						cpf = $('#billing_cpf').val();
					}
				}
			}
		} // end of if =12

		if (metodo == null) {
			console.log("nada recuperado via ajax! saindo...");
			return;
		}
		var order_id = cs_sessionid;

		console.log("metodo=" + metodo + " order#=" + order_id);
		console.log("entrou place_order card=" + card + " nome=" + card_holder);
		console.log("installments=" + card_installments);
		console.log("doc=" + cpf + " docpix=" + document_pix + " doc_bol=" + document_boleto);

		var dados_ajax = {
			metodo: metodo,
			order: order_id,
			card: card,
			card_holder: card_holder,
			cpf: cpf,
			validate: validate,
			installments: card_installments,
			doc_boleto: document_boleto,
			doc_debito: document_debit,
			doc_pix: document_pix
		};

		var dados_envio = {
			'clearsale_total_ajax_nonce': js_global.clearsale_total_ajax_nonce,
 			'pagseguro': dados_ajax,
			'action': 'clearsale_total_push'
      		};

		jQuery.ajax({
			url: js_global.xhr_url,
			type: 'POST',
			data: dados_envio,
			dataType: 'JSON',
			success: function(response) {
				if (response == '401'  ){
					console.log('Requisição inválida!')
				}
				else if (response == 402) {
					console.log('Todos os posts já foram mostrados!')
				} else {
					console.log('Resposta=' + response)
				}
			}
		});



	});

});
//-------


})( jQuery );

