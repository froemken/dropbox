<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_dropbox'] = array(
    'class' => \SFroemken\FalDropbox\Driver\DropboxDriver::class,
    'shortName' => 'Dropbox',
    'flexFormDS' => 'FILE:EXT:fal_dropbox/Configuration/FlexForms/Dropbox.xml',
    'label' => 'Dropbox'
);

// create a temporary cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fal_dropbox']['backend'] = \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_dropbox') . 'vendor/guzzlehttp/guzzle/src/functions_include.php');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_dropbox') . 'vendor/guzzlehttp/promises/src/functions_include.php');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_dropbox') . 'vendor/guzzlehttp/psr7/src/functions_include.php');

$extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$extractorRegistry->registerExtractionService(\SFroemken\FalDropbox\Extractor\ImageExtractor::class);
unset($extractorRegistry);
