<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Service;

use GuzzleHttp\Exception\ClientException;
use Spatie\Dropbox\TokenProvider;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AutoRefreshingDropboxTokenService implements TokenProvider {

    /**
     * @var string
     */
    protected string $refreshToken;

    /**
     * @var string
     */
    protected string $appKey;

    /**
     * @param string $refreshToken
     * @param string $appKey
     */
    public function __construct(string $refreshToken, string $appKey){
        $this->refreshToken = $refreshToken;
        $this->appKey = $appKey;
    }

    /**
     * @return string
     */
    public function getToken() :string
    {
        try {
            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            $response = $requestFactory->request('https://api.dropbox.com/oauth2/token', 'POST', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->refreshToken,
                    'client_id' => $this->appKey
                ]
            ]);
            $responseArray = json_decode($response->getBody()->getContents(), true);
            $accessToken = $responseArray['access_token'];
        } catch (ClientException $clientException) {
            $accessToken = '';
        }
        return $accessToken;
    }
}
