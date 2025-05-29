<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Domain\Model;

class FilePathInfo implements PathInfoInterface
{
    private int $size = 0;

    private string $serverModified = '';

    private string $clientModified = '';

    public function __construct(
        private readonly string $name,
        private readonly string $path
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

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getServerModified(): string
    {
        return $this->serverModified;
    }

    public function setServerModified(string $serverModified): void
    {
        $this->serverModified = $serverModified;
    }

    public function getClientModified(): string
    {
        return $this->clientModified;
    }

    public function setClientModified(string $clientModified): void
    {
        $this->clientModified = $clientModified;
    }
}
