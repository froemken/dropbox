<?php
namespace SFroemken\FalDropbox\Tca;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan froemken <froemken@gmail.com>
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
use SFroemken\FalDropbox\Dropbox\AppInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @package fal_dropbox
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RequestToken {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \SFroemken\FalDropbox\Dropbox\AppInfo
	 */
	protected $appInfo;

	/**
	 * @var \SFroemken\FalDropbox\Configuration\Dropbox
	 */
	protected $configuration;

	/**
	 * @var array
	 */
	protected $parentArray = array();

	/**
	 * initializes this object
	 *
	 * @param array $parentArray
	 * @return void
	 */
	protected function initialize(array $parentArray) {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->parentArray = $parentArray;
		$this->configuration = $this->getConfiguration();
		$this->appInfo = $this->getAppInfo();
	}

	/**
	 * Get App Info
	 *
	 * @return \SFroemken\FalDropbox\Dropbox\AppInfo
	 */
	public function getAppInfo() {
		if (!$this->configuration->getKey() || !$this->configuration->getSecret()) {
			return NULL;
		} else {
			return AppInfo::loadFromJson($this->configuration->getArray());
		}
	}

	/**
	 * get Configuration
	 *
	 * @return \SFroemken\FalDropbox\Configuration\Dropbox
	 */
	protected function getConfiguration() {
		$flexformConfiguration = array();
		if (isset($this->parentArray['row']) && isset($this->parentArray['row']['configuration'])) {
			$flexformConfiguration = GeneralUtility::xml2array(
				$this->parentArray['row']['configuration']
			);
			$flexformConfiguration = $flexformConfiguration['data']['sDEF']['lDEF'];
		}

		/** @var \SFroemken\FalDropbox\Configuration\Dropbox $configuration */
		$configuration = $this->objectManager->get('SFroemken\\FalDropbox\\Configuration\\Dropbox');
		$configuration->setConfiguration($flexformConfiguration);

		return $configuration;
	}

	/**
	 * get requestToken
	 *
	 * @param array $parentArray
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formEngine
	 * @return string
	 */
	public function getRequestToken($parentArray, \TYPO3\CMS\Backend\Form\FormEngine $formEngine) {
		$this->initialize($parentArray);

		if ($this->appInfo instanceof AppInfo) {
			if ($this->configuration->getAccessToken()) {
				return $this->getHtmlForConnected();
			} elseif ($this->configuration->getAuthCode()) {
				try {
					$webAuth = $this->objectManager->get('SFroemken\\FalDropbox\\Dropbox\\WebAuthNoRedirect', $this->appInfo, 'TYPO3 CMS');
					list($accessToken, $dropboxUserId) = $webAuth->finish($this->configuration->getAuthCode());
					return '<div>Copy and save as Access Token: ' . $accessToken . '</div><div>Copy and save as User ID: ' . $dropboxUserId . '</div>';
				} catch(\Exception $e) {
					return $this->getHtmlForDropboxLink();
				}
			} else {
				return $this->getHtmlForDropboxLink();
			}
		} else {
			// dropbox configuration is not completed
			return '<div style="background-color: lightcoral;">You are NOT connected with your dropbox account. Please enter key and secret first and save the record.</div>';
		}
	}

	/**
	 * get HTML to show the user, that he is connected with his dropbox account
	 *
	 * @return string
	 */
	public function getHtmlForConnected() {
		try {
			/** @var \SFroemken\FalDropbox\Dropbox\Client $dropboxClient */
			$dropboxClient = $this->objectManager->get(
				'SFroemken\\FalDropbox\\Dropbox\\Client',
				$this->configuration->getAccessToken(),
				'TYPO3 CMS'
			);
			$accountInfo = $dropboxClient->getAccountInfo();

			$content = '';
			$content .= '<div style="background-color: lightgreen;">You are now connected with your dropbox account</div>';
			$content .= '
			<table border="1" cellspacing="2" cellpadding="2">
				<tr>
					<td>Name</td>
					<td>' . $accountInfo['display_name'] . '</td>
				</tr>
				<tr>
					<td>E-Mail</td>
					<td>' . $accountInfo['email'] . '</td>
				</tr>
				<tr>
					<td>Quota</td>
					<td>' . GeneralUtility::formatSize($accountInfo['quota_info']['quota'], ' | KB| MB| GB') . '</td>
				</tr>
				<tr>
					<td>Used</td>
					<td>' . GeneralUtility::formatSize($accountInfo['quota_info']['normal'], ' | KB| MB| GB') . '</td>
				</tr>
			</table>
		';
		} catch (\Exception $e) {
			$this->registry->removeAllByNamespace('fal_dropbox');
			$content = $this->getHtmlForDropboxLink();
		}

		return $content;
	}

	/**
	 * get HTML-Code for Dropbox Link
	 */
	public function getHtmlForDropboxLink() {
		$webAuth = $this->objectManager->get('SFroemken\\FalDropbox\\Dropbox\\WebAuthNoRedirect', $this->appInfo, 'TYPO3 CMS');
		$authorizeUrl = $webAuth->start();
		return '
			<div>
				Link: <a href="' . $authorizeUrl . '" target="_blank" style="text-decoration: underline;">Connect App with your Dropbox account</a>
			</div>
		';
	}

}