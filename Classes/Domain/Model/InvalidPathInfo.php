<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Domain\Model;

/**
 * In case of an exception in DropboxDriver we will use this class
 * that is neither a file nor a folder
 */
class InvalidPathInfo implements PathInfoInterface
{
    public function getName(): string
    {
        return '';
    }

    public function getPath(): string
    {
        return '';
    }
}
