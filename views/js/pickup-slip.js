/*
*
* DISCLAIMER
*
* Do not edit or add to this file.
* You are not authorized to modify, copy or redistribute this file.
* Permissions are reserved by FME Modules.
*
*  @author    FMM Modules
*  @copyright FME Modules 2020
*  @license   Single domain
*/

$(document).ready(function(){
	$('#delivery_form').closest('.row').append('<div id="email-loader"></div>')
})
function sendStoreAlert(id_order, id_store, to) {
	$('#email-loader').addClass('email_loading');
	var request = {
		url: storeSlipController,
		method: 'GET',
		dataType: 'json',
		data: {
			ajax: true,
			to: to,
			id_order: id_order,
			id_store: id_store,
			action: 'sendStoreAlert'
		},
		success: function(response) {
			if (response.hasError) {
				showErrorMessage(response.msg);
				$('#email-loader').removeClass('email_loading');
			} else {
				showSuccessMessage(response.msg);
				$('#email-loader').removeClass('email_loading');
				setTimeout(function(){
					location.reload(true);
				}, 200);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			$('#email-loader').removeClass('email_loading');
			showErrorMessage(textStatus + " : " + errorThrown);
		}
	}
	$.ajax(request);
}

