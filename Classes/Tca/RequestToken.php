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
use SFroemken\FalDropbox\Dropbox\Exception\InvalidAccessToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;

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
        /** @var FlexFormService $flexFormService */
        $flexFormService = $this->objectManager->get(FlexFormService::class);
        $config = $flexFormService->convertFlexFormContentToArray($parentArray['row']['configuration']);
        return $this->getHtmlForConnected($config['accessToken']);
    }

    /**
     * get HTML to show the user, that he is connected with his dropbox account
     *
     * @param string $accessToken
     * @return string
     */
    public function getHtmlForConnected($accessToken) {
        try {
            /** @var \SFroemken\FalDropbox\Dropbox\Client $dropboxClient */
            $dropboxClient = $this->objectManager->get(
                'SFroemken\\FalDropbox\\Dropbox\\Client',
                $accessToken,
                'TYPO3 CMS'
            );
            $accountInfo = $dropboxClient->getAccountInfo();
            if (!is_array($accountInfo)) {
                $accountInfo = array();
            }
            /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
            $view = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName(
                    'EXT:fal_dropbox/Resources/Private/Templates/ShowAccountInfo.html'
                )
            );
            $view->assign('account', $accountInfo);
            $content = $view->render();
        } catch (InvalidAccessToken $e) {
            $content = 'Access Token is invalid';
        } catch (\Exception $e) {
            $content = 'Could not retrieve account info from Dropbox';
        }

        return $content;
    }
}
