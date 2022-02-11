<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Service;

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
    protected StandaloneView $view;

    public function initialize(): void
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:fal_dropbox/Resources/Private/Templates/GetAccessToken.html'
            )
        );
    }

    /**
     * Start HTML-Output
     * @throws \JsonException
     * @throws RouteNotFoundException
     */
    public function main(): Response
    {
        $this->initialize();
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
            $this->view->assign('parameters', \json_encode($parameters, JSON_THROW_ON_ERROR));
        }

        $this->view->assign('appKey', $appKey);
        $this->view->assign('appSecret', $appSecret);
        $this->view->assign('formUri', $formUri);
        $this->view->assign('errors', $this->errors);

        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write($this->view->render());

        return $response;
    }
}
