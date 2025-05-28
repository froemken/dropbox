<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Traits;

trait GetRegistryKeyTrait
{
    /**
     * Hashes the Dropbox application key for secure storage in sys_registry.
     * Returns a truncated hash (first 10 characters) to prevent exposing sensitive credentials.
     */
    public function getRegistryKey(string $appKey): string
    {
        return substr(md5($appKey), 0, 10);
    }
}
