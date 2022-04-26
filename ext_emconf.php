<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Dropbox FAL Driver',
    'description' => 'Provides a Dropbox driver for TYPO3 File Abstraction Layer.',
    'category' => 'fe',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '4.2.2',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.1.99',
            'typo3' => '10.4.19-11.99.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
