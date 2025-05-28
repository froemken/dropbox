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
use Spatie\Dropbox\Exceptions\BadRequest;
use StefanFroemken\Dropbox\Client\DropboxClientFactory;
use StefanFroemken\Dropbox\Response\AccessTokenResponse;
use StefanFroemken\Dropbox\Traits\GetRegistryKeyTrait;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class retrieves and shows Dropbox Account information
 */
class DropboxStatusElement extends AbstractFormElement
{
    use GetRegistryKeyTrait;

    private const ACCOUNT_INFO_TEMPLATE = 'EXT:dropbox/Resources/Private/Templates/ShowAccountInfo.html';

    public function __construct(
        private readonly DropboxClientFactory $dropboxClientFactory,
        private readonly Registry $registry,
    ) {}

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
            $config = array_map(static function (array $value): string|array {
                // Returns an array of options for TCA select-type form fields. Else string.
                return $value['vDEF'];
            }, $this->data['databaseRow']['configuration']['data']['sDEF']['lDEF']);
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   $this->getHtmlForConnected((string)$config['appKey'], (string)$config['refreshToken']);
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        return $resultArray;
    }

    /**
     * Get HTML to show the user that he is connected with his dropbox account
     */
    public function getHtmlForConnected(string $appKey, string $refreshToken): string
    {
        $refreshToken = trim($refreshToken);
        $appKey = trim($appKey);
        if ($refreshToken === '' || $appKey === '') {
            return 'Please setup refresh token first to see your account info here.';
        }

        $view = $this->getView();

        try {
            $dropboxClient = $this->dropboxClientFactory->createByConfiguration([
                'appKey' => $appKey,
                'refreshToken' => $refreshToken,
            ]);

            $view->assignMultiple([
                'account' => $dropboxClient->getClient()->getAccountInfo(),
                'quota' => $dropboxClient->getClient()->rpcEndpointRequest('users/get_space_usage'),
                'lifetimeRemaining' => $this->getLifetimeRemaining($this->getRegistryKey($appKey)),
            ]);

            $content = $view->render();
        } catch (ClientException $clientException) {
            $content = $clientException->getMessage();
        } catch (BadRequest $badRequest) {
            $content = 'Bad request to Dropbox Client: ' . $badRequest->getMessage();
        }

        return $content;
    }

    private function getLifetimeRemaining(string $registryKey): int
    {
        $accessTokenResponse = $this->registry->get('ext_dropbox', $registryKey, []);

        return $accessTokenResponse instanceof AccessTokenResponse ? $accessTokenResponse->getLifetimeRemaining() : 0;
    }

    private function getView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(self::ACCOUNT_INFO_TEMPLATE);

        return $view;
    }
}
