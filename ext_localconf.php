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
?>