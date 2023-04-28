<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Response;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Contains the access token and lifetime of the access token refresh request
 */
class AccessTokenResponse
{
    private string $accessToken = '';

    private int $expiresIn = 0;

    private int $timeOfResponse = 0;

    public function __construct(array $response)
    {
        $this->accessToken = $response['access_token'] ?? '';
        $this->expiresIn = $response['expires_in'] ?? 0;
        $this->timeOfResponse = time();
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getLifetimeRemaining(): int
    {
        return MathUtility::convertToPositiveInteger($this->timeOfResponse - time() + $this->expiresIn);
    }
}
