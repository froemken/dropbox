TYPO3.jQuery(function() {
	if (!window.opener) {
		alert("ERROR: Sorry, no link to main window... Closing");
		close();
	}

	TYPO3.jQuery("form#getAccessToken").submit(function(event) {
		event.preventDefault();
		var parameters = TYPO3.jQuery.parseJSON(TYPO3.jQuery("input#parameters").val());

		TYPO3.jQuery.ajax({
			url: "https://api.dropboxapi.com/oauth2/token",
			dataType: "json",
			method: "POST",
			data: {
				code: TYPO3.jQuery("input#authCode").val(),
				grant_type: "authorization_code",
				client_id: TYPO3.jQuery("input#appKey").val(),
				client_secret: TYPO3.jQuery("input#appSecret").val()
			},
			success: function(response) {
				if (response.access_token) {
					// set value in parent window
					TYPO3.jQuery("form[name='" + parameters.formName + "'] input[data-formengine-input-name='" + parameters.itemName + "']", window.opener.document).val(response.access_token);
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