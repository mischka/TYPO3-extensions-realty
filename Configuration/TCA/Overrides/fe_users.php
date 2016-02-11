<?php
defined('TYPO3_MODE') or exit();

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('realty');
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('realty');
$extIconRelPath = $extRelPath . 'icons/';
$ll = 'LLL:EXT:realty/locallang_db.xml';

$tempColumns = [
    'tx_realty_openimmo_anid' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_openimmo_anid',
        'config' => array(
            'type' => 'input',
            'size' => '31',
            'eval' => 'trim',
        )
    ),
    'tx_realty_maximum_objects' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_maximum_objects',
        'config' => array(
            'type' => 'input',
            'size' => '4',
            'max' => '4',
            'eval' => 'int',
            'checkbox' => '0',
            'range' => array(
                'upper' => '9999',
                'lower' => '0',
            ),
            'default' => 0,
        ),
    )
];


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','--div--;LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_tab,tx_realty_openimmo_anid,tx_realty_maximum_objects;;;;1-1-1,');

