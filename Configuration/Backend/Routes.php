<?php
/**
 * Definitions for routes provided by EXT:fal_dropbox
 */
return [
    // Register accessToken wizard
    'access_token' => [
        'path' => '/wizard/accessToken',
        'target' => \SFroemken\FalDropbox\Service\AccessTokenService::class . '::main'
    ]
];
