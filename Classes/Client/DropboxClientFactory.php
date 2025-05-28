<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Client;

use Spatie\Dropbox\Client;
use StefanFroemken\Dropbox\Configuration\DropboxConfiguration;
use StefanFroemken\Dropbox\TokenProvider\TokenProviderFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class DropboxClientFactory
{
    public function __construct(
        private TokenProviderFactory $tokenProviderFactory,
    ) {}

    public function createByResourceStorage(ResourceStorage $resourceStorage): DropboxClient
    {
        $dropboxConfiguration = $this->buildDropboxConfiguration($resourceStorage->getConfiguration());

        $tokenProvider = $this->tokenProviderFactory->getTokenProvider(
            $dropboxConfiguration->getRefreshToken(),
            $dropboxConfiguration->getAppKey(),
        );

        return GeneralUtility::makeInstance(DropboxClient::class, new Client($tokenProvider));
    }

    public function createByConfiguration(array $configuration): DropboxClient
    {
        $dropboxConfiguration = $this->buildDropboxConfiguration($configuration);

        $tokenProvider = $this->tokenProviderFactory->getTokenProvider(
            $dropboxConfiguration->getRefreshToken(),
            $dropboxConfiguration->getAppKey(),
        );

        return GeneralUtility::makeInstance(DropboxClient::class, new Client($tokenProvider));
    }

    private function buildDropboxConfiguration(array $configuration): DropboxConfiguration
    {
        return new DropboxConfiguration(
            (string)($configuration['appKey'] ?? ''),
            (string)($configuration['refreshToken'] ?? ''),
        );
    }
}
