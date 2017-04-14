<?php

namespace SFroemken\FalDropbox\ViewHelpers;

/*
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CompatVersionViewHelper extends AbstractViewHelper
{
    /**
     * Version Compare
     *
     * @param string $version
     *
     * @return bool
     */
    public function render($version = '')
    {
        return GeneralUtility::compat_version($version);
    }
}
