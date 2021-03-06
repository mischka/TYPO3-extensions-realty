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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_NextPreviousButtonsViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_pi1_NextPreviousButtonsView
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var int the UID of the "list view" content object.
	 */
	private $listViewUid = 0;

	/**
	 * the UID of a dummy city for the object records
	 *
	 * @var int
	 */
	private $dummyCityUid = 0;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		/** @var TypoScriptFrontendController $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$frontEndController->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();
		$this->listViewUid = $this->testingFramework->createContentElement();
		$this->dummyCityUid = $this->testingFramework->createRecord('tx_realty_cities');

		$this->fixture = new tx_realty_pi1_NextPreviousButtonsView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'), $frontEndController->cObj
		);

		$this->fixture->setConfigurationValue('enableNextPreviousButtons', TRUE);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	//////////////////////
	// Utility Functions
	//////////////////////

	/**
	 * Creates a realty object with a city.
	 *
	 * @return int the UID of the created realty object, will be > 0
	 */
	private function createRealtyRecordWithCity() {
		return $this->testingFramework->createRecord(
			'tx_realty_objects', array('city' => $this->dummyCityUid)
		);
	}


	///////////////////////////////////////////
	// Tests concerning the utility functions
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityReturnsNonZeroUid() {
		self::assertTrue(
			$this->createRealtyRecordWithCity() > 0
		);
	}

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityRunTwiceCreatesTwoDifferentRecords() {
		self::assertTrue(
			$this->createRealtyRecordWithCity() != $this->createRealtyRecordWithCity()
		);
	}

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityCreatesRealtyObjectRecord() {
		$objectUid = $this->createRealtyRecordWithCity();
		self::assertTrue(
			$this->testingFramework->existsRecord(
				'tx_realty_objects',
				'uid = ' . $objectUid . ' AND is_dummy_record = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityAddsCityToRealtyObjectRecord() {
		$objectUid = $this->createRealtyRecordWithCity();
		self::assertTrue(
			$this->testingFramework->existsRecord(
				'tx_realty_objects',
				'uid = ' . $objectUid . ' AND city > 0 AND is_dummy_record = 1'
			)
		);
	}


	////////////////////////////////
	// Testing the basic functions
	////////////////////////////////

	/**
	 * @test
	 */
	public function renderForDisabledNextPreviousButtonsReturnsEmptyString() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => 'ABC112'));

		$this->fixture->setConfigurationValue('enableNextPreviousButtons', FALSE);

		$this->fixture->piVars = array(
			'showUid' => $realtyObject->getUid(),
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordReturnsEmptyString() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => 'ABC112'));

		$this->fixture->piVars = array(
			'showUid' => $realtyObject->getUid(),
			'recordPosition' => 0,
			'listViewLimitation' => json_encode(array('objectNumber' => 'ABC112')),
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndMultipleRecordsReturnsNextLink() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertContains(
			'nextPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionZeroNotReturnsPreviousButton() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertNotContains(
			'previousPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPostionOneAndTwoRecordsNotReturnsNextButton() {
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord('tx_realty_cities')
			)
		);
		$objectUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord('tx_realty_cities')
			)
		);

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewLimitation' => json_encode(array('objectNumber' => 'foo')),
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertNotContains(
			'nextPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsUidOfPreviousRecordToPreviousLink() {
		$objectUid1 = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord('tx_realty_cities')
			)
		);
		$objectUid2 = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord('tx_realty_cities')
			)
		);

		$this->fixture->piVars = array(
			'showUid' => $objectUid2,
			'recordPosition' => 1,
			'listViewType' => 'realty_list',
			'listViewLimitation' => json_encode(array('objectNumber' => 'foo')),
			'listUid' => $this->listViewUid,
		);

		self::assertContains(
			'showUid]=' . $objectUid1,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsUidOfNextRecordToNextLink() {
		$objectUid1 = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord('tx_realty_cities')
			)
		);
		$objectUid2 = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord('tx_realty_cities')
			)
		);

		$this->fixture->piVars = array(
			'showUid' => $objectUid1,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listViewLimitation' => json_encode(array('objectNumber' => 'foo')),
			'listUid' => $this->listViewUid,
		);

		self::assertContains(
			'showUid]=' . $objectUid2,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordOnListViewPageReturnsEmptyString() {
		$sysFolder = $this->testingFramework->createSystemFolder();
		$flexforms = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' .
			'<T3FlexForms>' .
				'<data>' .
					'<sheet index="sDEF">' .
						'<language index="lDEF">' .
							'<field index="pages">' .
								'<value index="vDEF">' . $sysFolder . '</value>' .
							'</field>' .
						'</language>' .
					'</sheet>' .
				'</data>' .
			'</T3FlexForms>';
		$listViewUid = $this->testingFramework->createContentElement(
			0, array('pi_flexform' => $flexforms)
		);

		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('pid' => $sysFolder));

		$this->fixture->piVars = array(
			'showUid' => $realtyObject,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $listViewUid,
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////////////////////////////
	// Tests concerning the URL of the "next" and "previous" buttons
	//////////////////////////////////////////////////////////////////
	//
	// The following tests only test the "next" button, since the link creation
	// for the "previous" button works the same.

	/**
	 * @test
	 */
	public function renderAddsListViewUidToNextButton() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listUid' => $this->listViewUid,
			'listViewType' => 'realty_list',
		);

		self::assertContains(
			'listUid]=' . $this->listViewUid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsListViewTypeToNextButton() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewType' => 'favorites',
			'listUid' => $this->listViewUid,
		);

		self::assertContains(
			'listViewType]=favorites',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsListViewLimitationToNextLink() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$listViewLimitation = json_encode(array('objectNumber' => 'foo'));

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewType' => 'favorites',
			'listViewLimitation' => $listViewLimitation,
			'listUid' => $this->listViewUid,
		);

		self::assertContains(
			'listViewLimitation]=' . urlencode($listViewLimitation),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNoListViewTypeReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 1,
			'listUid' => $this->listViewUid,
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForInvalidListViewTypeReturnsString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 1,
			'listViewType' => 'foo',
			'listUid' => $this->listViewUid,
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNegativeRecordPositionReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => -1,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionStringAddsRecordPositionOnetoNextLink() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();
		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 'foo',
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertContains(
			'recordPosition]=1',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionStringHidesPreviousButton() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 'foo',
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		self::assertNotContains(
			'previousPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForListUidNegativeReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 0,
			'listUid' => -1,
			'listViewType' => 'realty_list',
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForListUidPointingToNonExistingContentElementReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 0,
			'listUid' => $this->testingFramework->getAutoIncrement('tt_content'),
			'listViewType' => 'realty_list',
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNoListUidSetInPiVarsReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
		);

		self::assertEquals(
			'',
			$this->fixture->render()
		);
	}
}