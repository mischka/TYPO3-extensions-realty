<?php

namespace OliverKlee\Realty\Utility;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class to get the settings from Extension Manager
 *
 * @package TYPO3
 * @subpackage tx_realty
 */
class EmConfiguration
{

    /**
     * Parses the extension settings.
     *
     * @return \OliverKlee\Realty\Domain\Model\Dto\EmConfiguration
     * @throws \Exception If the configuration is invalid.
     */
    public static function getSettings()
    {
        $configuration = self::parseSettings();
        GeneralUtility::requireOnce(ExtensionManagementUtility::extPath('realty') . 'Classes/Domain/Model/Dto/EmConfiguration.php');
        $settings = new \OliverKlee\Realty\Domain\Model\Dto\EmConfiguration($configuration);
        return $settings;
    }

    /**
     * Parse settings and return it as array
     *
     * @return array unserialized extconf settings
     */
    public static function parseSettings()
    {
        $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['realty']);

        if (!is_array($settings)) {
            $settings = [];
        }
        return $settings;
    }

}