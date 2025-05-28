<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\TokenProvider;

use Spatie\Dropbox\RefreshableTokenProvider;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Registry;

readonly class TokenProviderFactory
{
    public function __construct(
        private RequestFactory $requestFactory,
        private Registry $registry,
    ) {}

    public function getTokenProvider(string $refreshToken, string $appKey): RefreshableTokenProvider
    {
        return new RefreshingTokenProvider($this->requestFactory, $this->registry, $refreshToken, $appKey);
    }
}
