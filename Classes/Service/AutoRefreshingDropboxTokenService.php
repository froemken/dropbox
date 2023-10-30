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
use Spatie\Dropbox\RefreshableTokenProvider;
use StefanFroemken\Dropbox\Response\AccessTokenResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AutoRefreshingDropboxTokenService implements RefreshableTokenProvider
{
    protected string $refreshToken;

    protected string $appKey;

    public function __construct(string $refreshToken, string $appKey)
    {
        $this->refreshToken = $refreshToken;
        $this->appKey = $appKey;
    }

    /**
     * If refresh() was called, the Dropbox Client fails to process the request,
     * which results in an exception which we will catch here and try to retrieve a fresh access token.
     *
     * @return bool Whether the token was refreshed or not.
     */
    public function refresh(ClientException $exception): bool
    {
        // We catch bad request (400) to build up first access token
        // We catch unauthorized (401) to refresh the access token
        if (
            $exception->getCode() !== 400
            && $exception->getCode() !== 401
        ) {
            return false;
        }

        try {
            $response = $this->getRequestFactory()->request(
                'https://api.dropbox.com/oauth2/token',
                'POST',
                [
                    'form_params' => [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $this->refreshToken,
                        'client_id' => $this->appKey,
                    ],
                ]
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);
            if (!is_array($responseArray)) {
                return false;
            }

            // Store complete response as it also contains the max. lifetime of the access token.
            // Useful for status output
            $this->getRegistry()->set(
                'ext_dropbox',
                $this->getRegistryKey(),
                GeneralUtility::makeInstance(AccessTokenResponse::class, $responseArray)
            );
        } catch (ClientException $clientException) {
            return false;
        }

        return true;
    }

    /**
     * Do not call Spatie Dropbox Client with the result of getToken(). Of course, you can,
     * but refresh() will never be called in that case.
     * Call Spatie Dropbox Client with this full class. getToken() and refresh()
     * will be called internally.
     */
    public function getToken(): string
    {
        $accessTokenResponse = $this->getRegistry()->get('ext_dropbox', $this->getRegistryKey(), []);

        return $accessTokenResponse instanceof AccessTokenResponse ? $accessTokenResponse->getAccessToken() : '';
    }

    /**
     * I don't want to store the App key in storage.
     * Hash the App key and return the first 10 chars
     */
    public function getRegistryKey(): string
    {
        return substr(md5($this->appKey), 0, 10);
    }

    private function getRequestFactory(): RequestFactory
    {
        return GeneralUtility::makeInstance(RequestFactory::class);
    }

    private function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }
}
