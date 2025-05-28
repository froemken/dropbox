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
 * Represents the OAuth2 access token data obtained from a refresh token request.
 * Contains both the token string and its expiration timestamp.
 */
readonly class AccessTokenResponse
{
    public function __construct(
        private string $accessToken,
        private int $expiresIn,
        private int $timeOfResponse,
    ) {}

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getLifetimeRemaining(): int
    {
        $remainingLifetime = $this->timeOfResponse - time() + $this->expiresIn;

        return MathUtility::forceIntegerInRange($remainingLifetime, 0, PHP_INT_MAX);
    }
}
