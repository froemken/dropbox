<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(function ($extKey) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_dropbox'] = [
        'class' => \SFroemken\FalDropbox\Driver\DropboxDriver::class,
        'shortName' => 'Dropbox',
        'flexFormDS' => 'FILE:EXT:fal_dropbox/Configuration/FlexForms/Dropbox.xml',
        'label' => 'Dropbox'
    ];

    // create a temporary cache
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fal_dropbox']['backend'] = \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;

    $extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
    $extractorRegistry->registerExtractionService(\SFroemken\FalDropbox\Extractor\ImageExtractor::class);
}, $_EXTKEY);
