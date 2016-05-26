<?php
namespace SFroemken\FalDropbox\Service;

/**
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A Service to generate an accessToken via Dropbox
 */
class AccessTokenService {

    /**
     * @var string
     */
    protected $dropboxAuthorizeUrl = 'https://www.dropbox.com/oauth2/authorize?client_id=%s&response_type=%s';

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * inject view
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView $view
     * @return void
     */
    public function injectView(\TYPO3\CMS\Fluid\View\StandaloneView $view)
    {
        $this->view = $view;
        $this->view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:fal_dropbox/Resources/Private/Templates/GetAccessToken.html'
            )
        );
    }

    /**
     * start HTML-Output
     */
    public function main()
    {
        $formFields = GeneralUtility::_POST('dropbox');
        $parameters = GeneralUtility::_GP('P');
        $appKey = htmlspecialchars($formFields['appKey']);
        $appSecret = htmlspecialchars($formFields['appSecret']);

        if (!empty($formFields['appKey'])) {
            $uri = $this->createGetAuthCodeLink($appKey);
            if (!empty($uri)) {
                $this->view->assign('getAuthCodeLink', $uri);
            }
        }

        $this->view->assign('appKey', $appKey);
        $this->view->assign('appSecret', $appSecret);
        $this->view->assign('parameters', json_encode($parameters));
        $this->view->assign('errors', $this->errors);
        return $this->view->render();
    }

    /**
     * Create Link where you can authorize your appKey
     *
     * @param string $appKey
     * @return string
     */
    protected function createGetAuthCodeLink($appKey)
    {
        $uri = sprintf(
            $this->dropboxAuthorizeUrl,
            $appKey,
            'code'
        );
        $report = array();
        GeneralUtility::getUrl($uri, 2, false, $report);
        if (!empty($report['error'])) {
            $this->addError($report['error'], $report['message']);
            $uri = '';
        }
        return $uri;
    }

    /**
     * Add error
     *
     * @param int $errorNo
     * @param string $errorMessage
     * @return void
     */
    protected function addError($errorNo, $errorMessage)
    {
        $this->errors[] = array(
            'number' => $errorNo,
            'message' => $errorMessage
        );
    }
}