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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class renders the "next" and "previous" buttons.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_NextPreviousButtonsView extends tx_realty_pi1_FrontEndView {
	/**
	 * Renders the "previous" and "next" buttons.
	 *
	 * @param array $piVars piVars array, may be empty
	 *
	 * @return string the HTML output for the "previous" and "next" buttons,
	 *                will be empty if both buttons are hidden
	 */
	public function render(array $piVars = array()) {
		$this->piVars = $this->sanitizePiVars();
		if (!$this->canButtonsBeRendered()) {
			return '';
		}

		/** @var Tx_Oelib_Visibility_Tree $visibilityTree */
		$visibilityTree = GeneralUtility::makeInstance(
			Tx_Oelib_Visibility_Tree::class,
			array('nextPreviousButtons' => array('previousButton' => FALSE, 'nextButton' => FALSE))
		);

		$recordPosition = $this->piVars['recordPosition'];
		if ($recordPosition > 0) {
			$previousRecordUid = $this->getPreviousRecordUid();
			$this->setMarker(
				'previous_url',
				$this->getButtonUrl($recordPosition - 1, $previousRecordUid)
			);
			$visibilityTree->makeNodesVisible(array('previousButton'));
		}

		$nextRecordUid = $this->getNextRecordUid();
		if ($nextRecordUid > 0) {
			$visibilityTree->makeNodesVisible(array('nextButton'));
			$this->setMarker(
				'next_url',
				$this->getButtonUrl($recordPosition + 1, $nextRecordUid)
			);
		}

		$this->hideSubpartsArray(
			$visibilityTree->getKeysOfHiddenSubparts(), 'FIELD_WRAPPER'
		);

		return $this->getSubpart('FIELD_WRAPPER_NEXTPREVIOUSBUTTONS');
	}

	/**
	 * Checks whether all preconditions are fulfilled for the rendering of the
	 * buttons.
	 *
	 * @return bool TRUE if the buttons can be rendered, FALSE otherwise
	 */
	protected function canButtonsBeRendered() {
		if (!$this->getConfValueBoolean('enableNextPreviousButtons')) {
			return FALSE;
		}
		if ($this->piVars['recordPosition'] < 0) {
			return FALSE;
		}
		if (!in_array(
				$this->piVars['listViewType'],
				array('my_objects', 'favorites', 'objects_by_offerer', 'realty_list')
			)
		) {
			return FALSE;
		}
		if ($this->piVars['listUid'] <= 0) {
			return FALSE;
		}

		return Tx_Oelib_Db::existsRecordWithUid(
			'tt_content',
			$this->piVars['listUid'],
			Tx_Oelib_Db::enableFields('tt_content')
		);
	}


	/////////////////////////
	// Sanitizing functions
	/////////////////////////

	/**
	 * Sanitizes the piVars needed for this view.
	 *
	 * This function will store the sanitized piVars into $this->piVars.
	 *
	 * @return array the sanitized piVars, will be empty if an empty array was
	 *               given.
	 */
	protected function sanitizePiVars() {
		$sanitizedPiVars = array();

		$sanitizedPiVars['recordPosition'] = (isset($this->piVars['recordPosition']))
			? (int)$this->piVars['recordPosition'] : -1;
		$sanitizedPiVars['listUid'] = (isset($this->piVars['listUid']))
			? max((int)$this->piVars['listUid'], 0) : 0;

		$sanitizedPiVars['listViewType'] = (isset($this->piVars['listViewType']))
			? $this->piVars['listViewType']
			: '';

		// listViewLimitation will be sanitized, only if it actually is used.
		if (isset($this->piVars['listViewLimitation'])) {
		  	$sanitizedPiVars['listViewLimitation']
		  		= $this->piVars['listViewLimitation'];
		}

		return $sanitizedPiVars;
	}

	/**
	 * Sanitizes and decodes the listViewLimitation piVar.
	 *
	 * @return string[] the data stored in the listViewLimitation string as array.
	 */
	protected function sanitizeAndSplitListViewLimitation() {
		$rawData = json_decode($this->piVars['listViewLimitation'], TRUE);
		if (!is_array($rawData) || empty($rawData)) {
			return array();
		}

		$allowedKeys = array_merge(
			array('search', 'orderBy', 'descFlag'),
			tx_realty_filterForm::getPiVarKeys()
		);
		$result = array();

		foreach ($allowedKeys as $allowedKey) {
			if (isset($rawData[$allowedKey])) {
				$result[$allowedKey] = $rawData[$allowedKey];
			}
		}

		return $result;
	}


	/////////////////////////////////////////////
	// Functions for retrieving the record UIDs
	/////////////////////////////////////////////

	/**
	 * Retrieves the UID of the record previous to the currently shown one.
	 *
	 * Before calling this function, ensure that $this->piVars['recordPosition']
	 * is >= 1.
	 *
	 * @return int the UID of the previous record, will be > 0
	 */
	protected function getPreviousRecordUid() {
		return $this->getRecordAtPosition($this->piVars['recordPosition'] - 1);
	}

	/**
	 * Retrieves the UID of the record next to to the currently shown one.
	 *
	 * A return value of 0 means that no record could be found at the given
	 * position.
	 *
	 * @return int the UID of the next record, will be >= 0
	 */
	protected function getNextRecordUid() {
		return $this->getRecordAtPosition($this->piVars['recordPosition'] + 1);
	}

	/**
	 * Retrieves the UID for the record at the given record position.
	 *
	 * @param int $recordPosition
	 *        the position of the record to find, must be >= 0
	 *
	 * @return int the UID of the record at the given position, will be >= 0
	 */
	protected function getRecordAtPosition($recordPosition) {
		$contentData = Tx_Oelib_Db::selectSingle(
			'*', 'tt_content',
			'uid = ' . (int)$this->piVars['listUid'] . Tx_Oelib_Db::enableFields('tt_content')
		);
		/** @var ContentObjectRenderer $contentObject */
		$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$contentObject->start($contentData, 'tt_content');
		$listView = tx_realty_pi1_ListViewFactory::make(
			$this->piVars['listViewType'], $this->conf, $contentObject
		);
		// TODO: use tslib_content::readFlexformIntoConf when TYPO3 4.3 is required
		$listView->pi_initPIflexForm();

		$listView->setPiVars($this->sanitizeAndSplitListViewLimitation());

		return $listView->getUidForRecordNumber($recordPosition);
	}


	//////////////////////////////////////////
	// Functions for building the button URL
	//////////////////////////////////////////

	/**
	 * Returns the URL for the buttons.
	 *
	 * @param int $recordPosition
	 *        the position of the record the URL points to
	 * @param int $recordUid
	 *        the UID of the record the URL points to
	 *
	 * @return string the htmlspecialchared URL for the button, will not be empty
	 */
	protected function getButtonUrl($recordPosition, $recordUid) {
		$additionalParameters = $this->piVars;
		$additionalParameters['recordPosition'] = $recordPosition;
		$additionalParameters['showUid'] = $recordUid;
		$urlParameters = array(
			'parameter' => $this->cObj->data['pid'],
			'additionalParams' => GeneralUtility::implodeArrayForUrl(
				$this->prefixId, $additionalParameters
			),
			'useCacheHash' => TRUE,
		);

		return htmlspecialchars($this->cObj->typoLink_URL($urlParameters));
	}
}