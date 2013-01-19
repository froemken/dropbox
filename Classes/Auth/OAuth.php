<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan froemken <firma@sfroemken.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 * @author Stefan Froemken <firma@sfroemken.de>
 * @package fal_dropbox
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_FalDropbox_Auth_OAuth {

	/**
	 * @var Tx_FalDropbox_Domain_Model_OAuth
	 */
	protected $oAuth;

	/**
	 * initializes this class
	 *
	 * @return Tx_FalDropbox_Auth_OAuth
	 */
	public function init() {
		$objectManager = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->oAuth = $objectManager->get('Tx_FalDropbox_Domain_Model_OAuth');

		// maybe it's good to move them into extConf
		$this->oAuth->setLinks(array(
			'request_token'=>'https://api.dropbox.com/1/oauth/request_token',
			'authorize'=>'https://www.dropbox.com/1/oauth/authorize',
			'access_token'=>'https://api.dropbox.com/1/oauth/access_token'
		));

		return $this;
	}

	/**
	 * set app keys
	 *
	 * @param string $appKey
	 * @param string $appKeySecret
	 * @return void
	 */
	public function setKeys($appKey, $appKeySecret) {
		$this->oAuth->setOAuthConsumerKey($appKey);
		$this->oAuth->setOAuthConsumerKeySecret($appKeySecret);
	}

	/**
	 * return parameters
	 *
	 * @param boolean return parameters with signature entry or not
	 * @return array The parameters
	 */
	public function getParameters($withSignature = false) {
		$parameters = array(
			'oauth_consumer_key' => $this->oAuth->getOAuthConsumerKey(),
			'oauth_nonce' => $this->oAuth->getOAuthNonce(),
			'oauth_signature_method' => $this->oAuth->getOAuthSignatureMethod(),
			'oauth_timestamp' => $this->oAuth->getOAuthTimestamp(),
			'oauth_version' => $this->oAuth->getOAuthVersion()
		);
		if ($this->oAuth->getOAuthRequestToken()) {
			$parameters['oauth_token'] = $this->oAuth->getOAuthRequestToken();
			$parameters['oauth_verifier'] = '';
		}
		ksort($parameters);
		if ($withSignature) {
			$parameters['oauth_signature'] = $this->getSignature();
		}
		return $parameters;
	}

	/**
	 * get signature
	 *
	 * @return string signature
	 */
	public function getSignature() {
		$key = $this->urlencode($this->oAuth->getOAuthConsumerKeySecret()) . '&';
		if ($this->oAuth->getOAuthRequestTokenSecret()) {
			$key .= $this->urlencode($this->oAuth->getOAuthRequestTokenSecret());
		}
		return base64_encode(hash_hmac(
			'sha1',
			$this->getBaseString(),
			$key,
			true
		));
	}

	/**
	 * get base string
	 *
	 * @return string
	 */
	public function getBaseString() {
		$parts = array(
			'POST',
			$this->urlencode($this->getLink()),
			$this->urlencode($this->getQueryString())
		);
		return implode('&', $parts);
	}

	/**
	 * get query string
	 *
	 * @return string query
	 */
	public function getQueryString() {
		$query = array();
		foreach ($this->getParameters() as $key => $parameter) {
			$query[] = $this->urlencode($key) . '=' . $this->urlencode($parameter);
		}
		return implode('&', $query);
	}

	/**
	 * url encode
	 *
	 * @param string $value Value to url encode
	 * @return string url encoded string
	 */
	public function urlencode($value) {
		$search  = array('+', '%7E');
		$replace = array('%20', '~');

		return str_replace($search, $replace, rawurlencode($value));
	}

	/**
	 * get authorization string
	 * this method is used by getHeaders
	 *
	 * @return string
	 */
	public function getAuthorizationString() {
		$string = array();
		foreach ($this->getParameters(true) as $key => $parameter) {
			$string[] = $key . '="' . $this->urlencode($parameter) . '"';
		}
		return implode(', ', $string);
	}

	/**
	 * get link for current step
	 *
	 * @return string
	 */
	public function getLink() {
		if ($this->oAuth->getOAuthRequestToken()) {
			return $this->oAuth->getLink('access_token');
		} else {
			return $this->oAuth->getLink('request_token');
		}
	}

	/**
	 * get headers for connection
	 *
	 * @param array url informations
	 * @return array
	 */
	public function getHeaders(array $url) {
		return array(
			'POST ' . $url['path'] . ' HTTP/1.0',
			'Host: ' . $url['host'],
			'Authorization: OAuth ' . $this->getAuthorizationString(),
			'Content-Length: 0',
			'Connection: close'
		);
	}

	/**
	 * get request token
	 *
	 * @return array
	 */
	public function getRequestToken() {
		$result = $this->send('request_token');

		list($header, $body) = explode("\n\n", str_replace("\r", '', $result));
		list($status) = explode("\n", $header);

		if ($status != 'HTTP/1.1 200 OK') {
			$error = json_decode($body);
			\TYPO3\CMS\Core\Utility\DebugUtility::debug($error->error, 'Misconfigured Dropbox configuration');
			return array();
		}

		parse_str($body, $data);

		if (empty($data['oauth_token'])) {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug('Failed to get Dropbox request token.');
			return array();
		}

		return $data;
	}

	/**
	 * get access token
	 *
	 * @return array
	 */
	public function getAccessToken() {
		$result = $this->send('access_token');

		list($header, $body) = explode("\n\n", str_replace("\r", '', $result));
		list($status) = explode("\n", $header);

		if ($status != 'HTTP/1.1 200 OK') {
			//$error = json_decode($body);
			//\TYPO3\CMS\Core\Utility\DebugUtility::debug($error->error, 'Error while requesting the access token. Maybe you have to click the link first');
			return false;
		}

		parse_str($body, $data);

		/*if (empty($data['oauth_token'])) {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug('Failed to get Dropbox request token.');
			return array();
		}*/

		return $data;
	}

	/**
	 * get authorize url
	 *
	 * @return string
	 */
	public function getAuthorizeUrl() {
		$link = $this->oAuth->getLink('authorize');
		return $link . '?oauth_token=' . $this->oAuth->getOAuthRequestToken();
	}

	/**
	 * send request
	 *
	 * @param string request
	 * @return string
	 */
	public function send($request) {
		$url = parse_url($this->oAuth->getLink($request));
		$fp = fsockopen('ssl://' . $url['host'], 443, $errno, $errstr, 2);
		if (!$fp) {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug($errno . '-' . $errstr, 'error');
		}
		$push = implode("\r\n", $this->getHeaders($url)) . "\r\n\r\n";
		fputs($fp, $push);

		while (!feof($fp)) {
			$line = fgets($fp, 2048);
			$result .= $line;
			if (!strlen(trim($line))) {
				// Stop at the first empty line (= end of header)
				break;
			}
		}

		$result .= stream_get_contents($fp);
		fclose($fp);

		return $result;
	}

	/**
	 * set request token
	 *
	 * @param string $requestToken
	 * @param string $requestTokenSecret
	 */
	public function setRequestToken($requestToken, $requestTokenSecret) {
		$this->oAuth->setOAuthRequestToken($requestToken);
		$this->oAuth->setOAuthRequestTokenSecret($requestTokenSecret);
	}
}
?>