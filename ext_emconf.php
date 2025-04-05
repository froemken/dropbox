<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Dropbox FAL Driver',
    'description' => 'Provides a Dropbox driver for TYPO3 File Abstraction Layer.',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '5.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.25-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
