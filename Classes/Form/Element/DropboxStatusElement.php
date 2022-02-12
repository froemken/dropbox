<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Form\Element;

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class retrieves and shows Dropbox Account information
 */
class DropboxStatusElement extends AbstractFormElement
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        if (is_string($this->data['databaseRow']['configuration'])) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $config = $flexFormService->convertFlexFormContentToArray($this->data['databaseRow']['configuration']);
        } else {
            $config = [];
            foreach ($this->data['databaseRow']['configuration']['data']['sDEF']['lDEF'] as $key => $value) {
                $config[$key] = $value['vDEF'];
            }
        }

        $resultArray['html'] = $this->getHtmlForConnected((string)$config['accessToken']);

        return $resultArray;
    }

    /**
     * Get HTML to show the user, that he is connected with his dropbox account
     */
    public function getHtmlForConnected(string $accessToken): string
    {
        $accessToken = trim($accessToken);
        if ($accessToken === '') {
            return 'Please setup access token first to see your account info here.';
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:dropbox/Resources/Private/Templates/ShowAccountInfo.html'
            )
        );

        try {
            $dropboxApp = GeneralUtility::makeInstance(DropboxApp::class, '', '', $accessToken);
            $dropbox = GeneralUtility::makeInstance(Dropbox::class, $dropboxApp);
            $view->assign('account', $dropbox->getCurrentAccount());
            $view->assign('quota', $dropbox->getSpaceUsage());
            $content = $view->render();
        } catch (DropboxClientException $dropboxClientException) {
            try {
                $errorResponse = \json_decode(
                    $dropboxClientException->getMessage(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
                $content = 'Dropbox Client Error: ' . $errorResponse['error']['.tag'] ?? 'None given';
            } catch (\JsonException $jsonException) {
                $content = 'Dropbox Client Response Error';
            }
        }

        return $content;
    }
}
