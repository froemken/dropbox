<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Register accessToken wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
    'access_token',
    'EXT:fal_dropbox/Classes/Wizards/AccessToken/'
);