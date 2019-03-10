/*
 * This file is part of the fal_dropbox project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
define(["jquery"], function($) {
	if (!window.opener) {
		alert("ERROR: Sorry, no link to main window... Closing");
		close();
	}

	$("form#getAccessToken").submit(function(event) {
		event.preventDefault();
		let parameters = $.parseJSON($("input#parameters").val());
		let $inputAuthCode = $("input#authCode");
		let $inputAppKey = $("input#appKey");
		let $inputAppSecret = $("input#appSecret");

		$.ajax({
			url: "https://api.dropboxapi.com/oauth2/token",
			dataType: "json",
			method: "POST",
			data: {
				code: $inputAuthCode.val(),
				grant_type: "authorization_code",
				client_id: $inputAppKey.val(),
				client_secret: $inputAppSecret.val()
			},
			success: function(response) {
				if (response.access_token) {
					// set value in parent window
					$("form[name='" + parameters.formName + "'] input[data-formengine-input-name='" + parameters.itemName + "']", window.opener.document).val(response.access_token);
					// Set parent field (_hr) as modified and update original field
					for (var property in parameters.fieldChangeFunc) {
						if (parameters.fieldChangeFunc.hasOwnProperty(property)) {
							eval("window.opener." + parameters.fieldChangeFunc[property]);
						}
					}
				}
				close();
			},
			fail: function() {
				console.log('Ups');
			}
		});
	});
});