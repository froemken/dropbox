<?php

declare(strict_types=1);

/*
 * This file is part of the package sfroemken/fal_dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace SFroemken\FalDropbox\Service;

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * A Service to generate an accessToken via Dropbox
 */
class AccessTokenService
{
    /**
     * @var string
     */
    protected $dropboxAuthorizeUrl = 'https://www.dropbox.com/oauth2/authorize?client_id=%s&response_type=%s';

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $view;

    /**
     * initialize
     */
    public function initialize()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:fal_dropbox/Resources/Private/Templates/GetAccessToken.html'
            )
        );
        $this->view->setLayoutRootPaths([
            GeneralUtility::getFileAbsFileName(
                'EXT:fal_dropbox/Resources/Private/Layouts/'
            )
        ]);
    }

    /**
     * start HTML-Output
     *
     * @return Response
     * @throws RouteNotFoundException
     */
    public function main(): Response
    {
        $this->initialize();
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $formUri = (string)$uriBuilder->buildUriFromRoute('access_token');

        $formFields = GeneralUtility::_POST('dropbox');
        $parameters = GeneralUtility::_GET('P');
        if (empty($parameters)) {
            $parameters = GeneralUtility::_POST('parameters');
        }

        $appKey = htmlspecialchars($formFields['appKey'] ?? '');
        $appSecret = htmlspecialchars($formFields['appSecret'] ?? '');

        if (!empty($appKey)) {
            $dropbox = new Dropbox(new DropboxApp($appKey, $appSecret));
            $uri = $dropbox->getAuthHelper()->getAuthUrl();
            if (!empty($uri)) {
                $this->view->assign('getAuthCodeLink', $uri);
                $this->view->assign('parameters', $parameters);
            }
        } else {
            $this->view->assign('parameters', json_encode($parameters));
        }

        $this->view->assign('appKey', $appKey);
        $this->view->assign('appSecret', $appSecret);
        $this->view->assign('formUri', $formUri);
        $this->view->assign('errors', $this->errors);

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write($this->view->render());
        return $response;
    }

    /**
     * Add error
     *
     * @param int $errorNo
     * @param string $errorMessage
     */
    protected function addError($errorNo, $errorMessage)
    {
        $this->errors[] = [
            'number' => $errorNo,
            'message' => $errorMessage
        ];
    }
}
