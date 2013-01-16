<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_dropbox'] = array(
	'class' => 'Tx_FalDropbox_Driver_Dropbox',
	'shortName' => 'FAL Dropbox',
	'flexFormDS' => 'FILE:EXT:fal_dropbox/Configuration/FlexForms/Dropbox.xml',
	'label' => 'Dropbox'
);

if (!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_faldropbox_cache'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['tx_faldropbox_cache'] = array();
}
$TYPO3_CONF_VARS['FE']['eID_include']['falDropboxClearRegistry'] = 'EXT:fal_dropbox/Classes/Ajax/ClearRegistry.php';
?>