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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

require_once(PATH_formidableapi);

/**
 * This class provides functions used in the realty plugin's forms.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndForm extends tx_realty_pi1_FrontEndView {
	/**
	 * @var tx_ameosformidable
	 */
	protected $formCreator = NULL;

	/**
	 * @var tx_realty_Model_RealtyObject
	 */
	protected $realtyObject = NULL;

	/**
	 * @var int UID of the currently edited object, zero if the object is
	 *              going to be a new database record.
	 */
	protected $realtyObjectUid = 0;

	/**
	 * @var bool whether the constructor is called in test mode
	 */
	protected $isTestMode = FALSE;

	/**
	 * @var array this is used to fake form values for testing
	 */
	protected $fakedFormValues = array();

	/**
	 * @var string the path to the FORMidable XML file
	 */
	private $xmlPath;

	/**
	 * The constructor.
	 *
	 * @param array $configuration
	 *        TypoScript configuration for the plugin
	 * @param ContentObjectRenderer $contentObjectRenderer
	 *        the parent cObj content, needed for the flexforms
	 * @param int $uidOfObjectToEdit
	 *        UID of the object to edit, set to 0 to create a new database record, must not be negative
	 * @param string $xmlPath
	 *        path of the XML for the form, relative to this extension, must not begin with a slash and must not be empty
	 * @param bool $isTestMode
	 *        whether the FE editor is instantiated in test mode
	 */
	public function __construct(
		array $configuration, ContentObjectRenderer $contentObjectRenderer, $uidOfObjectToEdit, $xmlPath,
		$isTestMode = FALSE
	) {
		$this->isTestMode = $isTestMode;
		$this->realtyObjectUid = $uidOfObjectToEdit;
		$this->xmlPath = $xmlPath;

		$this->realtyObject = GeneralUtility::makeInstance('tx_realty_Model_RealtyObject', $this->isTestMode);
		$this->realtyObject->loadRealtyObject($this->realtyObjectUid, TRUE);

		parent::__construct($configuration, $contentObjectRenderer);
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		unset($this->formCreator, $this->realtyObject);

		parent::__destruct();
	}

	/**
	 * Instantiates $this->formCreator (if it hasn't been created yet).
	 *
	 * This function does nothing if this object is running in test mode.
	 *
	 * @return void
	 */
	protected function makeFormCreator() {
		if ($this->formCreator || $this->isTestMode) {
			return;
		}

		$this->formCreator = GeneralUtility::makeInstance('tx_ameosformidable');
		// FORMidable would produce an error message if it is initialized with
		// a non-existing UID.
		// The FORMidable object is never initialized for testing.
		if ($this->realtyObjectExistsInDatabase()) {
			$this->formCreator->init(
				$this,
				ExtensionManagementUtility::extPath('realty') . $this->xmlPath,
				($this->realtyObjectUid > 0) ? $this->realtyObjectUid : FALSE
			);
		}
	}

	/**
	 * Returns the FE editor in HTML if a user is logged in and authorized, and
	 * if the object to edit actually exists in the database. Otherwise the
	 * result will be an error view.
	 *
	 * @param array $unused unused
	 *
	 * @return string HTML for the FE editor or an error view if the
	 *                requested object is not editable for the current user
	 */
	public function render(array $unused = array()) {
		$this->addOnLoadHandler();
		$this->makeFormCreator();
		return $this->formCreator->render();
	}

	/**
	 * Adds an onload handler to the page header.
	 *
	 * This function is intended to be overridden by subclasses if needed.
	 *
	 * @return void
	 */
	public function addOnLoadHandler() {
	}


	//////////////////////////////////////
	// Functions to be used by the form.
	//////////////////////////////////////

	/**
	 * Returns the URL where to redirect to after saving a record.
	 *
	 * @return string complete URL of the configured FE page, if none is
	 *                configured, the redirect will lead to the base URL
	 */
	public function getRedirectUrl() {
		return GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL(array(
			'parameter' => $this->getConfValueInteger(
				'feEditorRedirectPid', 's_feeditor'
			),
			'useCacheHash' => TRUE,
		)));
	}

	/**
	 * Gets the path to the HTML template as set in the TS setup.
	 * The returned path will always be an absolute path in the file system;
	 * EXT: references will automatically get resolved.
	 *
	 * @return string the path to the HTML template as an absolute path in the
	 *                file system, will not be empty in a correct configuration
	 */
	static public function getTemplatePath() {
		return GeneralUtility::getFileAbsFileName(
			Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
				->getAsString('feEditorTemplateFile')
		);
	}


	////////////////////////////////////
	// Miscellaneous helper functions.
	////////////////////////////////////

	/**
	 * Returns a form value from the FORMidable object.
	 *
	 * Note: In test mode, this function will return faked values.
	 *
	 * @param string $key column name of tx_realty_objects as key, must not be empty
	 *
	 * @return string form value or an empty string if the value does not exist
	 */
	protected function getFormValue($key) {
		$this->makeFormCreator();

		if ($this->isTestMode) {
			$result = $this->getFakedFormValue($key);
		} else {
			/** @var formidable_maindatahandler $dataHandler */
			$dataHandler = $this->formCreator->oDataHandler;
			$result = $dataHandler->getThisFormData($key);
		}

		return $result;
	}

	/**
	 * Checks whether the realty object exists in the database and is enabled.
	 * For new objects, the result will always be TRUE.
	 *
	 * @return bool TRUE if the realty object is available for editing,
	 *                 FALSE otherwise
	 */
	private function realtyObjectExistsInDatabase() {
		if ($this->realtyObjectUid == 0) {
			return TRUE;
		}

		return !$this->realtyObject->isEmpty();
	}


	///////////////////////////////////
	// Utility functions for testing.
	///////////////////////////////////

	/**
	 * Fakes the setting of the current UID.
	 *
	 * This function is for testing purposes.
	 *
	 * @param int $uid
	 *        UID of the currently edited realty object. For creating a new database record, $uid must be zero.
	 *        Provided values must not be negative.
	 *
	 * @return void
	 */
	public function setRealtyObjectUid($uid) {
		$this->realtyObjectUid = $uid;

		if ($this->realtyObject->getUid() != $uid) {
			$this->realtyObject = GeneralUtility::makeInstance('tx_realty_Model_RealtyObject', $this->isTestMode);
			$this->realtyObject->loadRealtyObject($this->realtyObjectUid, TRUE);
		}
	}

	/**
	 * Fakes a form data value that is usually provided by the FORMidable
	 * object.
	 *
	 * This function is for testing purposes.
	 *
	 * @param string $key column name of tx_realty_objects as key, must not be empty
	 * @param string $value faked value
	 *
	 * @return void
	 */
	public function setFakedFormValue($key, $value) {
		$this->fakedFormValues[$key] = $value;
	}

	/**
	 * Gets a faked form data value that is usually provided by the FORMidable
	 * object.
	 *
	 * This function is for testing purposes.
	 *
	 * @param string $key column name of tx_realty_objects as key, must not be empty
	 *
	 * @return string faked value
	 */
	public function getFakedFormValue($key) {
		return isset($this->fakedFormValues[$key]) ? $this->fakedFormValues[$key] : '';
	}
}