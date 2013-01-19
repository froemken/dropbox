jQuery(document).ready(function() {
	jQuery( "#requestToken" ).bind( "click", function() {
		jQuery.ajax({
			url: "https://api.dropbox.com/1/oauth/request_token",
			dataType: "json",
			type: "POST",
			success: function(returnValue, status) {
				alert(returnValue);
				alert(status);
			},
			complete: function(xhr, status, exception) {
				alert(status);
				alert(exception);
			},
			error: function(xhr, status) {
				alert(status);
			}
		});
	});
});