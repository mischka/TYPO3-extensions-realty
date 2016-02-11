<?php
defined('TYPO3_MODE') or die('Access denied.');


$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']
	= 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']
	= 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:realty/pi1/flexform_pi1_ds.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array(
		'LLL:EXT:realty/locallang_db.xml:tt_content.list_type_pi1',
		$_EXTKEY.'_pi1',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif',
	),
	'list_type'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY,'pi1/static/', 'Realty Manager');

if (TYPO3_MODE == 'BE') {
	$GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['tx_realty_pi1_wizicon']
		= $extPath.'pi1/class.tx_realty_pi1_wizicon.php';

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'web_txrealtyM1', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'BackEnd/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web', 'txrealtyM1', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'BackEnd/'
	);
}