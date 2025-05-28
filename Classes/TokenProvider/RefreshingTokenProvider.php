<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\TokenProvider;

use GuzzleHttp\Exception\ClientException;
use Spatie\Dropbox\RefreshableTokenProvider;
use StefanFroemken\Dropbox\Response\AccessTokenResponse;
use StefanFroemken\Dropbox\Traits\GetRegistryKeyTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Registry;

/**
 * This token provider is passed to the Dropbox Client, which invokes the "refresh" method internally.
 * As this process is beyond our control, the class cannot be made stateless. Due to its use of dynamic
 * properties, it must remain stateful. Instantiate this class using "new" directly, as Dependency Injection
 * is not applicable in this case.
 */
readonly class RefreshingTokenProvider implements RefreshableTokenProvider
{
    use GetRegistryKeyTrait;

    private const TOKEN_API_ENDPOINT = 'https://api.dropbox.com/oauth2/token';

    public function __construct(
        private RequestFactory $requestFactory,
        private Registry $registry,
        private string $refreshToken,
        private string $appKey
    ) {}

    /**
     * Handles Dropbox API authentication failures by refreshing the access token.
     * Called when either:
     * - Initial request requires first-time token acquisition (error 400)
     * - Existing access token has expired (error 401)
     *
     * After token refresh, the original API request is automatically retried.
     *
     * @return bool Returns true if the token was successfully refreshed, false otherwise
     */
    public function refresh(ClientException $exception): bool
    {
        if (!in_array($exception->getCode(), [400, 401], true)) {
            return false;
        }

        try {
            $response = $this->requestFactory->request(
                self::TOKEN_API_ENDPOINT,
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

            // Cache the entire OAuth response to preserve access token expiration metadata for status reporting
            $this->registry->set(
                'ext_dropbox',
                $this->getRegistryKey($this->appKey),
                new AccessTokenResponse(
                    $responseArray['access_token'] ?? '',
                    $responseArray['expires_in'] ?? 0,
                    time(),
                )
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
        $accessTokenResponse = $this->registry->get('ext_dropbox', $this->getRegistryKey($this->appKey), []);

        return $accessTokenResponse instanceof AccessTokenResponse ? $accessTokenResponse->getAccessToken() : '';
    }
}
