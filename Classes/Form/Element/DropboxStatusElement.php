<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Form\Element;

use GuzzleHttp\Exception\ClientException;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\Exceptions\BadRequest;
use StefanFroemken\Dropbox\Service\AutoRefreshingDropboxTokenService;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class retrieves and shows Dropbox Account information
 */
class DropboxStatusElement extends AbstractFormElement
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

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

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   $this->getHtmlForConnected((string)$config['refreshToken'], (string)$config['appKey']);
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }

    /**
     * Get HTML to show the user, that he is connected with his dropbox account
     */
    public function getHtmlForConnected(string $refreshToken, string $appKey): string
    {
        $refreshToken = trim($refreshToken);
        $appKey = trim($appKey);
        if ($refreshToken === '' || $appKey === '') {
            return 'Please setup refresh token first to see your account info here.';
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName(
                'EXT:dropbox/Resources/Private/Templates/ShowAccountInfo.html'
            )
        );

        try {
            $tokenService = GeneralUtility::makeInstance(AutoRefreshingDropboxTokenService::class, $refreshToken, $appKey);
            $dropboxClient = GeneralUtility::makeInstance(Client::class, $tokenService->getToken());
            $view->assign('account', $dropboxClient->getAccountInfo());
            $view->assign('quota', $dropboxClient->rpcEndpointRequest('users/get_space_usage'));
            $content = $view->render();
        } catch (ClientException $clientException) {
            $content = $clientException->getMessage();
        } catch (BadRequest $badRequest) {
            $content = 'Bad request to Dropbox Client: ' . $badRequest->getMessage();
        }

        return $content;
    }
}
