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
 * This configuration will be filled by the values of the FlexForm of Dropbox FAL Driver record (sys_file_storage)
 */
class DropboxConfiguration
{
    private string $appKey = '';

    private string $refreshToken = '';

    /**
     * @param string $appKey Lookup your AppKey from Dropbox Developer App corner
     * @param string $refreshToken Token to create new AccessTokens which are max. valid for 4 hours
     */
    public function __construct(string $appKey, string $refreshToken)
    {
        $this->appKey = $appKey;
        $this->refreshToken = $refreshToken;
    }

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
