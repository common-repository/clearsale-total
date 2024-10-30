(function( $ ) {
	'use strict';

	jQuery(document).ready(function($) {
		$('body').on('click', '#submit-cs-tot-mbox', function(e) {

			var postid = $("#postid").val();
			console.log("js to call ajax mbox order=" + postid);

			var dados_envio = {
				'cs_total_mbox_nonce': js_global.cs_total_mbox_nonce,
				'mbox': postid,
				'action': 'cs_total_mbox_ajax_action'
			}
			jQuery.ajax({
				url: js_global.xhr_url,
				type: 'POST',
				data: dados_envio,
				dataType: 'JSON',
				success: function(response) {
					if (response == '401'  ) {
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

	});

})( jQuery );
