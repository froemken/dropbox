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
 *
 * @package sfstefan
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class tx_faldropbox_tca {

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 */
	protected $registry;

	/**
	 * @var Dropbox_OAuth_PEAR
	 */
	protected $oauth;





	/**
	 * initializes this object
	 *
	 * @param array $config
	 */
	public function initialization(array $config) {
		$this->registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$this->oauth = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Dropbox_OAuth_PEAR', $config['appKey'], $config['appSecret']);
	}

	/**
	 * show informations about dropbox authentication
	 *
	 * @param array $PA
	 * @param array $fObj
	 * @return string
	 */
	public function dropboxLink($PA, $fObj) {
		$config = $this->getConfiguration($PA);
		if(empty($config['appKey'])) return '<div>You have to set App key first and save the record.</div>';
		if(empty($config['appSecret'])) return '<div>You have to set App key first and save the record.</div>';
		if(empty($config['accessType'])) return '<div>You have to save this record first.</div>';
		$this->initialization($config);

		$settings = $this->registry->get('fal_dropbox', 'config');
		if(empty($settings)) {
			// create a new request which the user have to apply
			$settings['requestToken'] = $this->oauth->getRequestToken();

			$settings['appKey'] = $config['appKey'];
			$settings['appSecret'] = $config['appSecret'];
			$settings['accessType'] = $config['accessType'];
			$this->registry->set('fal_dropbox', 'config', $settings);

			return '<div>Link: <a href="' . $this->oauth->getAuthorizeUrl() . '" target="_blank">Connect App with your Dropbox account</a></div>';
		} elseif(empty($settings['requestToken'])) {
			// create a new request which the user have to apply
			$settings['requestToken'] = $this->oauth->getRequestToken();
			$this->registry->set('fal_dropbox', 'config', $settings);

			return '<div>Link: <a href="' . $this->oauth->getAuthorizeUrl() . '" target="_blank">Connect App with your Dropbox account</a></div>';
		} elseif(empty($settings['accessToken'])) {
			// ok now we can try to access the real access token
			$this->oauth->setToken($settings['requestToken']);
			try {
				$settings['accessToken'] = $this->oauth->getAccessToken();
				$this->registry->set('fal_dropbox', 'config', $settings);
			} catch(Exception $e) {
				// create a new request which the user have to apply
				$settings['requestToken'] = $this->oauth->getRequestToken();
				$this->registry->set('fal_dropbox', 'config', $settings);
				return '<div>Link: <a href="' . $this->oauth->getAuthorizeUrl() . '" target="_blank">Connect App with your Dropbox account</a></div>';
			}
		} else {
			$this->oauth->setToken($settings['accessToken']);
		}

		return '<div style="color: green;">You\'re now authenticated to your dropbox account</div>';
	}

	/**
	 * get configuration
	 *
	 * @param array $PA
	 * @return array
	 */
	public function getConfiguration($PA) {
		$config = array();
		if ($PA['row']['configuration']) {
			$xmlArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($PA['row']['configuration']);
			foreach ($xmlArray['data']['sDEF']['lDEF'] as $key => $value) {
				$config[$key] = $value['vDEF'];
			}
		}

		return $config;
	}
}
?>