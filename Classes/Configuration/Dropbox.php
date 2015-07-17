<?php
namespace SFroemken\FalDropbox\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <sfroemken@jweiland.net>, jweiland.net
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
use TYPO3\CMS\Core\SingletonInterface;

/**
 * @package fal_dropbox
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Dropbox implements SingletonInterface {

	/**
	 * key
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * secret
	 *
	 * @var string
	 */
	protected $secret = '';

	/**
	 * authCode
	 *
	 * @var string
	 */
	protected $authCode = '';

	/**
	 * accessToken
	 *
	 * @var string
	 */
	protected $accessToken = '';

	/**
	 * accessType
	 *
	 * @var string
	 */
	protected $accessType = '';

	/**
	 * getter for key
	 *
	 * @return string
	 */
	public function getKey() {
		return trim($this->key);
	}

	/**
	 * setter for key
	 *
	 * @param string $key
	 * @return void
	 */
	public function setKey($key) {
		$this->key = (string)$key;
	}

	/**
	 * getter for secret
	 *
	 * @return string
	 */
	public function getSecret() {
		return trim($this->secret);
	}

	/**
	 * setter for secret
	 *
	 * @param string $secret
	 * @return void
	 */
	public function setSecret($secret) {
		$this->secret = (string)$secret;
	}

	/**
	 * Returns the authCode
	 *
	 * @return string $authCode
	 */
	public function getAuthCode() {
		return trim($this->authCode);
	}

	/**
	 * Sets the authCode
	 *
	 * @param string $authCode
	 * @return void
	 */
	public function setAuthCode($authCode) {
		$this->authCode = (string)$authCode;
	}

	/**
	 * Returns the accessToken
	 *
	 * @return string $accessToken
	 */
	public function getAccessToken() {
		return trim($this->accessToken);
	}

	/**
	 * Sets the accessToken
	 *
	 * @param string $accessToken
	 * @return void
	 */
	public function setAccessToken($accessToken) {
		$this->accessToken = (string)$accessToken;
	}

	/**
	 * getter for access_type
	 *
	 * @return string
	 */
	public function getAccessType() {
		return trim($this->accessType);
	}

	/**
	 * setter for access_type
	 *
	 * @param string $accessType
	 * @return void
	 */
	public function setAccessType($accessType) {
		$this->accessType = (string)$accessType;
	}

	/**
	 * get configuration as array
	 *
	 * @return array
	 */
	public function getArray() {
		$configuration = array();
		$configuration['key'] = $this->getKey();
		$configuration['secret'] = $this->getSecret();
		return $configuration;
	}

	/**
	 * set configuration with values from Flexform
	 *
	 * @param array $configuration
	 */
	public function setConfiguration(array $configuration) {
		if (!empty($configuration)) {
			// call setter method foreach configuration entry
			foreach ($configuration as $key => $value) {
				$methodName = 'set' . ucfirst($key);
				if (method_exists($this, $methodName)) {
					$this->$methodName($value['vDEF']);
				}
			}
		}
	}

}