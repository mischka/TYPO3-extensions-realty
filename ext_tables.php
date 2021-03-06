<?php
defined('TYPO3_MODE') or die('Access denied.');

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);
$extIconRelPath = $extRelPath . 'icons/';

$GLOBALS['TCA']['tx_realty_objects'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_objects',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'type' => 'object_type',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_objects.gif',
		'requestUpdate' => 'city,has_coordinates',
	)
);

$GLOBALS['TCA']['tx_realty_apartment_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_apartment_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_apartment_types.gif'
	)
);

$GLOBALS['TCA']['tx_realty_house_types'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_house_types',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_house_types.gif'
	)
);

$GLOBALS['TCA']['tx_realty_car_places'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_car_places',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_car_places.gif'
	)
);

$GLOBALS['TCA']['tx_realty_pets'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_pets',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_pets.gif'
	)
);

$GLOBALS['TCA']['tx_realty_images'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_images',
		'label' => 'caption',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY object',
		'delete' => 'deleted',
		'hideTable' => TRUE,
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_images.gif'
	)
);

$GLOBALS['TCA']['tx_realty_documents'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY object',
		'delete' => 'deleted',
		'hideTable' => TRUE,
		'enablecolumns' => array(),
		'dynamicConfigFile' => $extPath . 'tca.php',
		'iconfile' => $extIconRelPath . 'icon_tx_realty_documents.gif'
	)
);

$GLOBALS['TCA']['tx_realty_cities'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_cities',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_cities.gif'
	)
);

$GLOBALS['TCA']['tx_realty_districts'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_districts',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => $extPath.'tca.php',
		'iconfile' => $extIconRelPath.'icon_tx_realty_districts.gif'
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'fe_users',
	array(
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
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','--div--;LLL:EXT:realty/locallang_db.xml:fe_users.tx_realty_tab,tx_realty_openimmo_anid,tx_realty_maximum_objects;;;;1-1-1,');

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