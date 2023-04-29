<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Dropbox FAL Driver',
    'description' => 'Provides a Dropbox driver for TYPO3 File Abstraction Layer.',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '5.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.2.99',
            'typo3' => '11.5.23-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
