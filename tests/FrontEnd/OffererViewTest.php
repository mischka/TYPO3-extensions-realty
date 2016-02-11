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
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_OffererViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_pi1_OffererView
	 */
	protected $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	protected $testingFramework = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		/** @var TypoScriptFrontendController $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_pi1_OffererView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'), $frontEndController->cObj
		);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'company'
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a realty object with an FE user as owner.
	 *
	 * @param array $ownerData the data to store for the owner
	 *
	 * @return tx_realty_Model_RealtyObject the realty object with the owner
	 */
	protected function getRealtyObjectWithOwner(array $ownerData = array()) {
		return Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')->getLoadedTestingModel(
			array(
				'owner' => $this->testingFramework->createFrontEndUser('', $ownerData),
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
			)
		);
	}


	///////////////////////////////////////////
	// Tests concerning the utility functions
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getRealtyObjectWithOwnerReturnsRealtyObjectModel() {
		self::assertTrue(
			$this->getRealtyObjectWithOwner() instanceof tx_realty_Model_RealtyObject
		);
	}

	/**
	 * @test
	 */
	public function getRealtyObjectWithOwnerAddsAnOwnerToTheModel() {
		self::assertTrue(
			$this->getRealtyObjectWithOwner()->hasOwner()
		);
	}

	/**
	 * @test
	 */
	public function getRealtyObjectWithCanStoreDataToOwner() {
		$owner = $this->getRealtyObjectWithOwner(array('name' => 'foo'))->getOwner();

		self::assertEquals(
			'foo',
			$owner->getName()
		);
	}


	/////////////////////////////
	// Testing the offerer view
	/////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'foo'));

		self::assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'foo'));

		$result = $this->fixture->render(array('showUid' => $realtyObject->getUid()));

		self::assertNotEquals(
			'',
			$result
		);
		self::assertNotContains(
			'###',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsEmployerForValidRealtyObject() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'foo'));

		self::assertContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForValidRealtyObjectWithoutData() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());

		self::assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	//////////////////////////////////////////////
	// Testing the displayed offerer information
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsContactInformationIfEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('phone_switchboard' => '12345'));

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		self::assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsPhoneNumberIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('phone_switchboard' => '12345'));

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		self::assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsBasicContactNameIfOffererDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('contact_person' => 'Ali Baba'));

		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');

		self::assertContains(
			'Ali Baba',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsFullContactNameIfOffererDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')->getLoadedTestingModel(
				array(
					'contact_person' => 'Green',
					'contact_person_first_name' => 'Laci',
					'contact_person_salutation' => 'Ms.',
				)
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');

		self::assertContains(
			'Ms. Laci Green',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisplayContactTelephoneEnabledContactFromObjectAndDirectExtensionSetShowsDirectExtensionNumber() {
		/** @var tx_realty_Model_RealtyObject|PHPUnit_Framework_MockObject_MockObject $model */
		$model = $this->getMock(
			'tx_realty_Model_RealtyObject',
			array('getContactPhoneNumber', 'getProperty')
		);
		$model->expects(self::once())->method('getContactPhoneNumber');
		$model->setData(array());

		/** @var tx_realty_Mapper_RealtyObject|PHPUnit_Framework_MockObject_MockObject $mapper */
		$mapper = $this->getMock('tx_realty_Mapper_RealtyObject', array('find'));
		$mapper->expects(self::any())->method('find')
			->will(self::returnValue($model));
		Tx_Oelib_MapperRegistry::set('tx_realty_Mapper_RealtyObject', $mapper);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->fixture->render(array('showUid' => 0));
	}

	/**
	 * @test
	 */
	public function renderReturnsCompanyIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('employer' => 'test company'));

		self::assertContains(
			'test company',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsOwnersPhoneNumberIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('telephone' => '123123')
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		self::assertContains(
			'123123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsOwnersCompanyIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('company' => 'any company')
		);

		self::assertContains(
			'any company',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsContactInformationIfOptionIsDisabled() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('phone_switchboard' => '12345'));

		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		self::assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsContactInformationForEnabledOptionAndDeletedOwner() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('company' => 'any company', 'deleted' => 1)
		);

		self::assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsContactInformationForEnabledOptionAndOwnerWithoutData() {
		$realtyObject = $this->getRealtyObjectWithOwner();

		self::assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsLabelForLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		self::assertContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsLabelOffererIfTheLinkToTheObjectsByOwnerListIsEnabled() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		self::assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);
		$objectsByOwnerPid = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $objectsByOwnerPid);

		self::assertContains(
			'?id=' . $objectsByOwnerPid,
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsOwnerUidInLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'owner' => $ownerUid,
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
		));

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		self::assertContains(
			'tx_realty_pi1[owner]=' . $ownerUid,
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledOptionAndOwnerSetHidesObjectsByOwnerLink() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		self::assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsLinkToTheObjectsByOwnerListForEnabledOptionAndNoOwnerSet() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT
		));

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		self::assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsLinkToTheObjectsByOwnerListForDisabledContactInformationAndOwnerAndPidSet() {
		$realtyObject = $this->getRealtyObjectWithOwner(
			array('username' => 'foo')
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		self::assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForNoObjectsByOwnerPidSetAndOwnerSetReturnsLinkWithoutId() {
		$realtyObject = $this->getRealtyObjectWithOwner(array('username' => 'foo'));

		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'offerer_label,objects_by_owner_link'
		);

		self::assertNotContains(
			'?id=',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}