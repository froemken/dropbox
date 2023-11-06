<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Client;

class DropboxThumbnailSizes
{
    /**
     * Dropbox API can only retrieve thumbnails in a specific dimension:
     * @link: https://www.dropbox.com/developers/documentation/http/documentation#files-get_thumbnail
     * List must be lower to higher
     */
    private array $thumbnailDimensions = [
        'w32h32' => [
            'width' => 32,
            'height' => 32,
        ],
        'w64h64' => [
            'width' => 64,
            'height' => 64,
        ],
        'w128h128' => [
            'width' => 128,
            'height' => 128,
        ],
        'w256h256' => [
            'width' => 256,
            'height' => 256,
        ],
        'w480h320' => [
            'width' => 480,
            'height' => 320,
        ],
        'w640h480' => [
            'width' => 640,
            'height' => 480,
        ],
        'w960h640' => [
            'width' => 960,
            'height' => 640,
        ],
        'w1024h768' => [
            'width' => 1024,
            'height' => 768,
        ],
        'w2048h1536' => [
            'width' => 2048,
            'height' => 1536,
        ],
    ];

    /**
     * Returns a specific dropbox API string for thumbnail dimension like: w64h64
     * Returns empty string, if someone tries to retrive too huge dimensions (> 2048 pixel)
     */
    public function getThumbnailSize(int $width, int $height): string
    {
        foreach ($this->thumbnailDimensions as $key => $thumbnailDimension) {
            if ($width < $thumbnailDimension['width'] && $height < $thumbnailDimension['height']) {
                return $key;
            }
        }

        return '';
    }
}
