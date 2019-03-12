<?php
namespace SFroemken\FalDropbox\Tca;

/*
 * This file is part of the TYPO3 CMS project.
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

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class retrieves and shows Dropbox Account information
 */
class RequestToken
{
    /**
     * @var array
     */
    protected $parentArray = [];

    /**
     * initializes this object
     *
     * @param array $parentArray
     * @return void
     */
    protected function initialize(array $parentArray)
    {
        $this->parentArray = $parentArray;
    }

    /**
     * get requestToken
     *
     * @param array $parentArray
     * @param object $formEngine
     * @return string
     */
    public function getRequestToken($parentArray, $formEngine)
    {
        $this->initialize($parentArray);
        if (is_string($parentArray['row']['configuration'])) {
            /** @var FlexFormService $flexFormService */
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $config = $flexFormService->convertFlexFormContentToArray($parentArray['row']['configuration']);
        } else {
            $config = [];
            foreach ($parentArray['row']['configuration']['data']['sDEF']['lDEF'] as $key => $value) {
                $config[$key] = $value['vDEF'];
            }
        }
        return $this->getHtmlForConnected($config['accessToken']);
    }

    /**
     * get HTML to show the user, that he is connected with his dropbox account
     *
     * @param string $accessToken
     *
     * @return string
     */
    public function getHtmlForConnected($accessToken)
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:fal_dropbox/Resources/Private/Templates/ShowAccountInfo.html'
            )
        );

        try {
            /** @var DropboxApp $dropboxClient */
            $dropboxApp = GeneralUtility::makeInstance(DropboxApp::class, '', '',  $accessToken);
            /** @var Dropbox $dropbox */
            $dropbox = GeneralUtility::makeInstance(Dropbox::class, $dropboxApp);
            $view->assign('account', $dropbox->getCurrentAccount());
            $view->assign('quota', $dropbox->getSpaceUsage());
            $content = $view->render();
        } catch (\Exception $e) {
            $content = 'Please setup access token first to see your account info here.';
        }

        return $content;
    }
}
