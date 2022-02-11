<?php
/**
 * Definitions for routes provided by EXT:fal_dropbox
 */
return [
    // Register accessToken wizard
    'access_token' => [
        'path' => '/wizard/accessToken',
        'target' => \StefanFroemken\Dropbox\Service\AccessTokenService::class . '::main'
    ]
];
