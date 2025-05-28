<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Client;

use Spatie\Dropbox\Client;

readonly class DropboxClient
{
    public function __construct(
        private Client $client,
    ) {}

    /**
     * Returns the metadata for an image incl. width/height
     *
     * Note: Metadata for the root folder is unsupported.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_metadata
     */
    public function getImageMetadata(string $path): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
            'include_media_info' => true,
        ];

        return $this->client->rpcEndpointRequest('files/get_metadata', $parameters);
    }

    private function normalizePath(string $path): string
    {
        if (preg_match("/^id:.*|^rev:.*|^(ns:[0-9]+(\/.*)?)/", $path) === 1) {
            return $path;
        }

        $path = trim($path, '/');

        return ($path === '') ? '' : '/' . $path;
    }

    /**
     * Useful if you want to fire your own requests to Dropbox API
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
