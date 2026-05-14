<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Domain\Model;

readonly class FolderPathInfo implements PathInfoInterface
{
    private \ArrayObject $entries;

    public function __construct(
        private string $name,
        private string $path,
    ) {
        $this->entries = new \ArrayObject();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function hasFolders(): bool
    {
        return (bool)$this->getFolders()->count();
    }

    /**
     * @return \ArrayObject<FolderPathInfo>
     */
    public function getFolders(): \ArrayObject
    {
        return new \ArrayObject(array_filter($this->entries->getArrayCopy(), static function (PathInfoInterface $pathInfo): bool {
            return $pathInfo instanceof FolderPathInfo;
        }));
    }

    public function hasFiles(): bool
    {
        return (bool)$this->getFiles()->count();
    }

    /**
     * @return \ArrayObject<FilePathInfo>
     */
    public function getFiles(): \ArrayObject
    {
        return new \ArrayObject(array_filter($this->entries->getArrayCopy(), static function (PathInfoInterface $pathInfo): bool {
            return $pathInfo instanceof FilePathInfo;
        }));
    }

    public function addEntry(PathInfoInterface $pathInfo): void
    {
        if ($pathInfo instanceof FilePathInfo || $pathInfo instanceof FolderPathInfo) {
            $this->entries->append($pathInfo);
        }
    }

    public function isEmpty(): bool
    {
        return $this->entries->count() === 0;
    }
}
