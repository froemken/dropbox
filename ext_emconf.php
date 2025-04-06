<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Dropbox FAL Driver',
    'description' => 'Provides a Dropbox driver for TYPO3 File Abstraction Layer.',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '6.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.3-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    "autoload" => [
        "psr-4" => [
            "StefanFroemken\\Dropbox\\" => "Classes",
            "Spatie\\Dropbox\\" => "Resources/Private/PHP/spatie/dropbox-api/src",
            "GrahamCampbell\\GuzzleFactory\\" => "Resources/Private/PHP/graham-campbell/guzzle-factory/src",
        ],
    ],
];
