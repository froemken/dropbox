<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Configuration;

/**
 * Configuration container for Dropbox FAL driver settings.
 * Properties are populated from FlexForm values stored in sys_file_storage records.
 */
readonly class DropboxConfiguration
{
    /**
     * @param string $appKey The application key obtained from the Dropbox Developer Console
     * @param string $refreshToken OAuth2 refresh token used to generate short-lived (4-hour) access tokens
     */
    public function __construct(
        private string $appKey,
        private string $refreshToken,
    ) {}

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
