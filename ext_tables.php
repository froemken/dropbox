<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Register accessToken wizard
if (!\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('8.0')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
        'access_token',
        'EXT:fal_dropbox/Classes/Wizards/AccessToken/'
    );
}
