<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@stefanfroemken/dropbox/' => [
            'path' => 'EXT:dropbox/Resources/Public/JavaScript/',
        ],
    ],
];
