<?php
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

// This checks permissions and exits if the users has no permission for entry.
$GLOBALS['BE_USER']->modAccess($MCONF, 1);
$GLOBALS['LANG']->includeLLFile('EXT:realty/BackEnd/locallang.xml');

/** @var tx_realty_BackEnd_Module $SOBE */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_realty_BackEnd_Module');
$SOBE->init();

echo $SOBE->render();