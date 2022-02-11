<?php
if (!defined('TYPO3')) {
    die ('Access denied.');
}

call_user_func(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_dropbox'] = [
        'class' => \StefanFroemken\Dropbox\Driver\DropboxDriver::class,
        'shortName' => 'Dropbox',
        'flexFormDS' => 'FILE:EXT:fal_dropbox/Configuration/FlexForms/Dropbox.xml',
        'label' => 'Dropbox'
    ];

    // create a temporary cache
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fal_dropbox']['backend']
        = \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;

    // Add wizard/control to access_token in XML structure
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1552228283] = [
        'nodeName' => 'accessToken',
        'priority' => '70',
        'class' => \StefanFroemken\Dropbox\Form\Element\AccessTokenElement::class
    ];
    // Show dropbox status in file storage
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1589660489] = [
        'nodeName' => 'dropboxStatus',
        'priority' => '70',
        'class' => \StefanFroemken\Dropbox\Form\Element\DropboxStatusElement::class
    ];

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::class
    )->registerExtractionService(\StefanFroemken\Dropbox\Extractor\ImageExtractor::class);
});
