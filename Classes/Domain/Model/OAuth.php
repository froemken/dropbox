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
class Tx_FalDropbox_Domain_Model_OAuth {

	/**
	 * oAuthConsumerKey (APP-Key)
	 *
	 * @var string
	 */
	protected $oAuthConsumerKey = '';

	/**
	 * oAuthConsumerKeySecret (APP-Key Secret)
	 *
	 * @var string
	 */
	protected $oAuthConsumerKeySecret = '';

	/**
	 * oAuthRequestToken
	 *
	 * @var string
	 */
	protected $oAuthRequestToken = '';

	/**
	 * oAuthRequestTokenSecret
	 *
	 * @var string
	 */
	protected $oAuthRequestTokenSecret = '';

	/**
	 * oAuthNonce (A unique string)
	 *
	 * @link http://oauth.net/core/1.0a/#nonce
	 * @var string
	 */
	protected $oAuthNonce = '';

	/**
	 * oAuthSignatureMethod (Normaly HMAC-SHA1)
	 *
	 * @var string
	 */
	protected $oAuthSignatureMethod = 'HMAC-SHA1';

	/**
	 * oAuthTimestamp (current)
	 *
	 * @link http://oauth.net/core/1.0a/#nonce
	 * @var string
	 */
	protected $oAuthTimestamp = '';

	/**
	 * oAuthVersion (Normaly 1.0)
	 *
	 * @var string
	 */
	protected $oAuthVersion = '1.0';

	/**
	 * request links
	 *
	 * @var array
	 */
	protected $links = array();





	/**
	 * setter for oAuthConsumerKey
	 *
	 * @param string $oAuthConsumerKey
	 */
	public function setOAuthConsumerKey($oAuthConsumerKey) {
		$this->oAuthConsumerKey = $oAuthConsumerKey;
	}

	/**
	 * getter for oAuthConsumerKey
	 *
	 * @return string
	 */
	public function getOAuthConsumerKey() {
		return $this->oAuthConsumerKey;
	}

	/**
	 * setter for oAuthRequestToken
	 *
	 * @param string $oAuthRequestToken
	 */
	public function setOAuthRequestToken($oAuthRequestToken) {
		$this->oAuthRequestToken = $oAuthRequestToken;
	}

	/**
	 * getter for oAuthRequestToken
	 *
	 * @return string
	 */
	public function getOAuthRequestToken() {
		return $this->oAuthRequestToken;
	}

	/**
	 * setter for oAuthRequestTokenSecret
	 *
	 * @param string $oAuthRequestTokenSecret
	 */
	public function setOAuthRequestTokenSecret($oAuthRequestTokenSecret) {
		$this->oAuthRequestTokenSecret = $oAuthRequestTokenSecret;
	}

	/**
	 * getter for oAuthRequestTokenSecret
	 *
	 * @return string
	 */
	public function getOAuthRequestTokenSecret() {
		return $this->oAuthRequestTokenSecret;
	}

	/**
	 * setter for oAuthConsumerKeySecret
	 *
	 * @param string $oAuthConsumerKeySecret
	 */
	public function setOAuthConsumerKeySecret($oAuthConsumerKeySecret) {
		$this->oAuthConsumerKeySecret = $oAuthConsumerKeySecret;
	}

	/**
	 * getter for oAuthConsumerKeySecret
	 *
	 * @return string
	 */
	public function getOAuthConsumerKeySecret() {
		return $this->oAuthConsumerKeySecret;
	}

	/**
	 * setter for oAuthNonce
	 *
	 * @param string $oAuthNonce
	 */
	public function setOAuthNonce($oAuthNonce) {
		$this->oAuthNonce = $oAuthNonce;
	}

	/**
	 * getter for oAuthNonce
	 *
	 * @return string
	 */
	public function getOAuthNonce() {
		if (empty($this->oAuthNonce)) {
			$this->setOAuthNonce(md5($this->getOAuthTimestamp() . rand(1,999)));
		}
		return $this->oAuthNonce;
	}

	/**
	 * setter for oAuthSignatureMethod
	 *
	 * @param string $oAuthSignatureMethod
	 */
	public function setOAuthSignatureMethod($oAuthSignatureMethod) {
		$this->oAuthSignatureMethod = $oAuthSignatureMethod;
	}

	/**
	 * getter for oAuthSignatureMethod
	 *
	 * @return string
	 */
	public function getOAuthSignatureMethod() {
		if (empty($this->oAuthSignatureMethod)) {
			$this->setOAuthSignatureMethod('HMAC-SHA1');
		}
		return $this->oAuthSignatureMethod;
	}

	/**
	 * setter for oAuthTimestamp
	 *
	 * @param string $oAuthTimestamp
	 */
	public function setOAuthTimestamp($oAuthTimestamp) {
		$this->oAuthTimestamp = $oAuthTimestamp;
	}

	/**
	 * getter for oAuthTimestamp
	 *
	 * @return string
	 */
	public function getOAuthTimestamp() {
		if (empty($this->oAuthTimestamp)) {
			$this->setOAuthTimestamp(time());
		}
		return $this->oAuthTimestamp;
	}

	/**
	 * setter for oAuthVersion
	 *
	 * @param string $oAuthVersion
	 */
	public function setOAuthVersion($oAuthVersion) {
		$this->oAuthVersion = $oAuthVersion;
	}

	/**
	 * getter for oAuthVersion
	 *
	 * @return string
	 */
	public function getOAuthVersion() {
		if (empty($this->oAuthVersion)) {
			$this->setOAuthVersion('1.0');
		}
		return $this->oAuthVersion;
	}

	/**
	 * setter for links
	 *
	 * @param string $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * getter for links
	 *
	 * @return array
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * getter for one single link
	 *
	 * @return string
	 */
	public function getLink($link) {
		return $this->links[$link];
	}
}
?>