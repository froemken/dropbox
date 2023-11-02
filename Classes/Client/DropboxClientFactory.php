<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Client;

use StefanFroemken\Dropbox\Configuration\DropboxConfiguration;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DropboxClientFactory
{
    public function createByResourceStorage(ResourceStorage $resourceStorage): DropboxClient
    {
        return GeneralUtility::makeInstance(
            DropboxClient::class,
            $this->buildDropboxConfiguration($resourceStorage->getConfiguration())
        );
    }

    public function createByConfiguration(array $configuration): DropboxClient
    {
        return GeneralUtility::makeInstance(
            DropboxClient::class,
            $this->buildDropboxConfiguration($configuration)
        );
    }

    private function buildDropboxConfiguration(array $configuration): DropboxConfiguration
    {
        return GeneralUtility::makeInstance(
            DropboxConfiguration::class,
            (string)($configuration['appKey'] ?? ''),
            (string)($configuration['refreshToken'] ?? '')
        );
    }
}
