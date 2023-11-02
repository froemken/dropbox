<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['dropbox'] = [
        'class' => \StefanFroemken\Dropbox\Driver\DropboxDriver::class,
        'shortName' => 'Dropbox',
        'flexFormDS' => 'FILE:EXT:dropbox/Configuration/FlexForms/Dropbox.xml',
        'label' => 'Dropbox',
    ];

    // create a temporary cache
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dropbox']['backend']
        = \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;

    // Add wizard/control to access_token in XML structure
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1552228283] = [
        'nodeName' => 'refreshToken',
        'priority' => '70',
        'class' => \StefanFroemken\Dropbox\Form\Element\RefreshTokenElement::class,
    ];
    // Show dropbox status in file storage
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1589660489] = [
        'nodeName' => 'dropboxStatus',
        'priority' => '70',
        'class' => \StefanFroemken\Dropbox\Form\Element\DropboxStatusElement::class,
    ];

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::class
    )->registerExtractionService(\StefanFroemken\Dropbox\Extractor\ImageExtractor::class);

    // Register processor to resize images directly in dropbox and download just the thumbnail
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['dropboxImagePreviewProcessor'] = [
        'className' => \StefanFroemken\Dropbox\Resource\Processing\ImageProcessing::class,
        'after' => [
            'SvgImageProcessor',
        ],
        'before' => [
            'LocalImageProcessor',
            'OnlineMediaPreviewProcessor',
            'DeferredBackendImageProcessor',
        ],
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['DropboxRenameExtensionKey']
        = \StefanFroemken\Dropbox\Upgrade\RenameExtensionKeyUpgrade::class;
});
