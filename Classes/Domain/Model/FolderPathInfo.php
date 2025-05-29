<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Domain\Model;

class FolderPathInfo implements PathInfoInterface
{
    private ?\ArrayObject $entries = null;

    public function __construct(
        private readonly string $name,
        private readonly string $path,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * While creating this object, just a path and name are given, but entries are still null (uninitialized).
     * If files or folders of this object are requested, this object will be set to "initialized".
     */
    public function isInitialized(): bool
    {
        return $this->entries !== null;
    }

    public function hasFolders(): bool
    {
        return $this->isInitialized() && $this->getFolders()->count();
    }

    /**
     * @return \ArrayObject<FolderPathInfo>
     */
    public function getFolders(): \ArrayObject
    {
        if (!$this->isInitialized()) {
            return new \ArrayObject([]);
        }

        return new \ArrayObject(array_filter($this->entries->getArrayCopy(), static function (PathInfoInterface $pathInfo): bool {
            return $pathInfo instanceof FolderPathInfo;
        }));
    }

    public function hasFiles(): bool
    {
        return $this->isInitialized() && $this->getFiles()->count();
    }

    /**
     * @return \ArrayObject<FilePathInfo>
     */
    public function getFiles(): \ArrayObject
    {
        if (!$this->isInitialized()) {
            return new \ArrayObject([]);
        }

        return new \ArrayObject(array_filter($this->entries->getArrayCopy(), static function (PathInfoInterface $pathInfo): bool {
            return $pathInfo instanceof FilePathInfo;
        }));
    }

    public function addEntry(PathInfoInterface $pathInfo): void
    {
        if ($pathInfo instanceof FilePathInfo || $pathInfo instanceof FolderPathInfo) {
            if ($this->entries === null) {
                $this->entries = new \ArrayObject();
            }

            $this->entries->append($pathInfo);
        }
    }

    public function isEmpty(): bool
    {
        return $this->entries === null || $this->entries->count();
    }
}
