<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_dropbox'] = array(
	'class' => 'SFroemken\\FalDropbox\\Driver\\Dropbox',
	'shortName' => 'SF Dropbox',
	'flexFormDS' => 'FILE:EXT:fal_dropbox/Configuration/FlexForms/Dropbox.xml',
	'label' => 'Dropbox'
);

// create a temporary cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fal_dropbox']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend';