<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Domain\Model;

readonly class FilePathInfo implements PathInfoInterface
{
    public function __construct(
        private string $name,
        private string $path,
        private int $size,
        private string $serverModified,
        private string $clientModified,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): string
    {
        // We need size as string in DropboxDriver
        return (string)$this->size;
    }

    public function getServerModified(): string
    {
        return $this->serverModified;
    }

    public function getClientModified(): string
    {
        return $this->clientModified;
    }
}
