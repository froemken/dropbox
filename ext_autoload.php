<?php
$extpath = t3lib_extMgm::extPath('fal_dropbox');
return array(
	'http_oauth' => $extpath . 'Classes/OAuth/OAuth.php',
	'http_oauth_consumer' => $extpath . 'Classes/OAuth/OAuth/Consumer.php',
	'http_oauth_consumer_request' => $extpath . 'Classes/OAuth/OAuth/Consumer/Request.php',
	'http_oauth_consumer_response' => $extpath . 'Classes/OAuth/OAuth/Consumer/Response.php',
	'http_oauth_consumer_exception_invalidresponse' => $extpath . 'Classes/OAuth/OAuth/Consumer/Exception/InvalidResponse.php',
	'http_oauth_exception' => $extpath . 'Classes/OAuth/OAuth/Exception.php',
	'http_oauth_exception_notimplemented' => $extpath . 'Classes/OAuth/OAuth/Exception/NotImplemented.php',
	'http_oauth_message' => $extpath . 'Classes/OAuth/OAuth/Message.php',
	'http_oauth_signature' => $extpath . 'Classes/OAuth/OAuth/Signature.php',
	'http_oauth_provider_exception' => $extpath . 'Classes/OAuth/OAuth/Provider/Exception.php',
	'http_oauth_provider_exception_invalidrequest' => $extpath . 'Classes/OAuth/OAuth/Provider/Exception/InvalidRequest.php',
	'http_oauth_provider_request' => $extpath . 'Classes/OAuth/OAuth/Provider/Request.php',
	'http_oauth_provider_response' => $extpath . 'Classes/OAuth/OAuth/Provider/Response.php',

	'dropbox_api' => $extpath . 'Classes/Dropbox/API.php',
	'dropbox_autoload' => $extpath . 'Classes/Dropbox/autoload.php',
	'dropbox_exception' => $extpath . 'Classes/Dropbox/Exception.php',
	'dropbox_exception_forbidden' => $extpath . 'Classes/Dropbox/Exception/Forbidden.php',
	'dropbox_exception_notfound' => $extpath . 'Classes/Dropbox/Exception/NotFound.php',
	'dropbox_exception_overquota' => $extpath . 'Classes/Dropbox/Exception/OverQuota.php',
	'dropbox_exception_requesttoken' => $extpath . 'Classes/Dropbox/Exception/RequestToken.php',
	'dropbox_oauth' => $extpath . 'Classes/Dropbox/OAuth.php',
	'dropbox_oauth_curl' => $extpath . 'Classes/Dropbox/OAuth/Curl.php',
	'dropbox_oauth_pear' => $extpath . 'Classes/Dropbox/OAuth/PEAR.php',
	'dropbox_oauth_php' => $extpath . 'Classes/Dropbox/OAuth/PHP.php',
	'dropbox_oauth_wordpress' => $extpath . 'Classes/Dropbox/OAuth/Wordpress.php',
	'dropbox_oauth_zend' => $extpath . 'Classes/Dropbox/OAuth/Zend.php',
	'dropbox_oauth_consumer_dropbox' => $extpath . 'Classes/Dropbox/OAuth/Consumer/Dropbox.php',
);
?>