jQuery(function() {
	if (!window.opener) {
		alert("ERROR: Sorry, no link to main window... Closing");
		close();
	}

	jQuery("form#getAccessToken").submit(function(event) {
		event.preventDefault();
		var parameters = jQuery.parseJSON(jQuery("input#parameters").val());

		jQuery.ajax({
			url: "https://api.dropboxapi.com/oauth2/token",
			dataType: "json",
			method: "POST",
			data: {
				code: jQuery("input#authCode").val(),
				grant_type: "authorization_code",
				client_id: jQuery("input#appKey").val(),
				client_secret: jQuery("input#appSecret").val()
			},
			success: function(response) {
				if (response.access_token) {
					// set value in parent window
					jQuery("form[name='" + parameters.formName + "'] input[name='" + parameters.itemName + "']", window.opener.document).val(response.access_token);
					// Set parent field (_hr) as modified and update original field
					for (var property in parameters.fieldChangeFunc){
						eval("window.opener." + parameters.fieldChangeFunc[property]);
					}
				}
				close();
			}
		});
	});
});