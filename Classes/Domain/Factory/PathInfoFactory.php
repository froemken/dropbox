<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Domain\Factory;

use StefanFroemken\Dropbox\Domain\Model\FilePathInfo;
use StefanFroemken\Dropbox\Domain\Model\FolderPathInfo;
use StefanFroemken\Dropbox\Domain\Model\InvalidPathInfo;
use StefanFroemken\Dropbox\Domain\Model\PathInfoInterface;

class PathInfoFactory
{
    public function createPathInfo(array $metaData): PathInfoInterface
    {
        if ($metaData['.tag'] === 'file') {
            return new FilePathInfo(
                name: $metaData['name'],
                path: $metaData['path_display'],
                size: (int)($metaData['size'] ?? 0),
                serverModified: $metaData['server_modified'] ?? '',
                clientModified: $metaData['client_modified'] ?? '',
            );
        }

        if ($metaData['.tag'] === 'folder') {
            return new FolderPathInfo(
                name: $metaData['name'],
                path: $metaData['path_display'],
            );
        }

        return new InvalidPathInfo();
    }

    public function createPathInfoForRootFolder(): FolderPathInfo
    {
        return new FolderPathInfo('/', '/');
    }
}
