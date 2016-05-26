<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Wizard to generate an accessToken via Dropbox
 */

/** @var \SFroemken\FalDropbox\Service\AccessTokenService $accessTokenService */
$accessTokenService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('SFroemken\\FalDropbox\\Service\\AccessTokenService');
echo $accessTokenService->main();
