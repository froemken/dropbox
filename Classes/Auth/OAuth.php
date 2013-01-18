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
	 */
	public function init() {
		$objectManager = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->oAuth = $objectManager->get('Tx_FalDropbox_Domain_Model_OAuth');

		// maybe it's good to move them into extConf
		$this->oAuth->setLinks(array(
			'request_token'=>'https://api.dropbox.com/1/oauth/request_token',
			'authorize'=>'https://www.linkedin.com/uas/oauth/authorize',
			'access_token'=>'https://api.linkedin.com/uas/oauth/accessToken'
		));
	}

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
			$this->urlencode($this->oAuth->getLink('request_token')),
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
	 * get headers for connection
	 *
	 * @return array
	 */
	public function getHeaders() {
		return array(
			'POST /1/oauth/request_token HTTP/1.0',
			'Host: api.dropbox.com',
			'Authorization: OAuth ' . $this->getAuthorizationString(),
			'Content-Type: text/xml;charset=UTF-8',
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
		$result = $this->send();

		list($header, $body) = explode("\n\n", str_replace("\r", '', $result));
		list($status) = explode("\n", $header);

		if ($status != 'HTTP/1.1 200 OK') {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug('Error getting OAuth token and secret.', 'error');
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
	 * send request
	 *
	 * @return string
	 */
	public function send() {
		$fp = fsockopen('ssl://api.dropbox.com', 443, $errno, $errstr, 2);
		if (!$fp) {
			\TYPO3\CMS\Core\Utility\DebugUtility::debug($errno . '-' . $errstr, 'error');
		}
		$push = implode("\r\n", $this->getHeaders()) . "\r\n\r\n";
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
}
?>