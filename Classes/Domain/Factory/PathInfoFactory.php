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
            $filePathInfo = new FilePathInfo($metaData['name'], $metaData['path_display']);
            $filePathInfo->setSize((int)($metaData['size'] ?? 0));
            $filePathInfo->setServerModified($metaData['server_modified'] ?? '');
            $filePathInfo->setClientModified($metaData['client_modified'] ?? '');

            return $filePathInfo;
        }

        if ($metaData['.tag'] === 'folder') {
            return new FolderPathInfo($metaData['name'], $metaData['path_display']);
        }

        return new InvalidPathInfo();
    }

    public function createPathInfoForRootFolder(): FolderPathInfo
    {
        return new FolderPathInfo('/', '/');
    }
}
