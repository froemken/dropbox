<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use StefanFroemken\Dropbox\Driver\DropboxDriver;
use StefanFroemken\Dropbox\Extractor\ImageExtractor;
use StefanFroemken\Dropbox\Form\Element\DropboxStatusElement;
use StefanFroemken\Dropbox\Form\Element\RefreshTokenElement;
use StefanFroemken\Dropbox\Resource\Processing\ImageProcessing;
use StefanFroemken\Dropbox\Upgrade\RenameExtensionKeyUpgrade;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['dropbox'] = [
    'class' => DropboxDriver::class,
    'shortName' => 'Dropbox',
    'flexFormDS' => 'FILE:EXT:dropbox/Configuration/FlexForms/Dropbox.xml',
    'label' => 'Dropbox',
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dropbox']['backend'] = TransientMemoryBackend::class;

// Add wizard/control to access_token in XML structure
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1552228283] = [
    'nodeName' => 'refreshToken',
    'priority' => '70',
    'class' => RefreshTokenElement::class,
];
// Show dropbox status in file storage
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1589660489] = [
    'nodeName' => 'dropboxStatus',
    'priority' => '70',
    'class' => DropboxStatusElement::class,
];

// Image Extractor is used to get image dimension from Dropbox API
GeneralUtility::makeInstance(ExtractorRegistry::class)
    ->registerExtractionService(ImageExtractor::class);

// Register processor to resize images directly in dropbox and download just the thumbnail
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['dropboxImagePreviewProcessor'] = [
    'className' => ImageProcessing::class,
    'after' => [
        'SvgImageProcessor',
    ],
    'before' => [
        'LocalImageProcessor',
        'OnlineMediaPreviewProcessor',
        'DeferredBackendImageProcessor',
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['DropboxRenameExtensionKey'] = RenameExtensionKeyUpgrade::class;
