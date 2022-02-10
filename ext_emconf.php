<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL Dropbox',
    'description' => 'Provides a DropBox driver for TYPO3 File Abstraction Layer.',
    'category' => 'fe',
    'author' => 'Stefan Froemken',
    'author_email' => 'froemken@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '4.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.19-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
