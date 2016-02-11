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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Import_OpenImmoImportTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_openImmoImportChild
	 */
	protected $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	protected $testingFramework = NULL;

	/**
	 * @var Tx_Oelib_ConfigurationProxy
	 */
	protected $globalConfiguration = NULL;

	/**
	 * @var tx_realty_translator
	 */
	protected $translator = NULL;

	/**
	 * @var int PID of the system folder where imported records will
	 *              be stored
	 */
	protected $systemFolderPid = 0;

	/**
	 * @var string path to the import folder
	 */
	protected $importFolder = '';

	/**
	 * @var bool whether an import folder has been created
	 */
	protected $testImportFolderExists = FALSE;

	/**
	 * backup of $GLOBALS['TYPO3_CONF_VARS']['GFX']
	 *
	 * @var array
	 */
	protected $graphicsConfigurationBackup = array();

	/**
	 * @var MailMessage
	 */
	protected $message = NULL;

	protected function setUp() {
		$this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];

		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->systemFolderPid = $this->testingFramework->createSystemFolder();
		$this->importFolder = PATH_site . 'typo3temp/tx_realty_fixtures/';

		Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

		$this->globalConfiguration = Tx_Oelib_ConfigurationProxy::getInstance('realty');

		$this->translator = new tx_realty_translator();

		$this->fixture = new tx_realty_openImmoImportChild(TRUE);
		$this->setupStaticConditions();

		$this->message = $this->getMock(MailMessage::class, array('send', '__destruct'));
		GeneralUtility::addInstance(MailMessage::class, $this->message);
	}

	protected function tearDown() {
		// Get any surplus instances added via GeneralUtility::addInstance.
		GeneralUtility::makeInstance(MailMessage::class);

		$this->testingFramework->cleanUp();
		$this->deleteTestImportFolder();

		tx_realty_cacheManager::purgeCacheManager();
		$GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;
	}


	/*
	 * Utility functions.
	 */

	/**
	 * Sets the global configuration values which need to be static during the
	 * tests.
	 *
	 * @return void
	 */
	protected function setupStaticConditions() {
		// avoids using the extension's real upload folder
		$this->fixture->setUploadDirectory($this->importFolder);

		// TYPO3 default configuration
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai';

		$this->globalConfiguration->setAsString('emailAddress', 'default-address@example.org');
		$this->globalConfiguration->setAsBoolean('onlyErrors', FALSE);
		$this->globalConfiguration->setAsString('openImmoSchema', $this->importFolder . 'schema.xsd');
		$this->globalConfiguration->setAsString('importFolder', $this->importFolder);
		$this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', TRUE);
		$this->globalConfiguration->setAsBoolean('notifyContactPersons', TRUE);
		$this->globalConfiguration->setAsInteger('pidForRealtyObjectsAndImages', $this->systemFolderPid);
		$this->globalConfiguration->setAsString('pidsForRealtyObjectsAndImagesByFileName', '');
		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', FALSE);
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', FALSE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', '');
		$this->globalConfiguration->setAsString('emailTemplate', 'EXT:realty/lib/tx_realty_emailNotification.tmpl');
	}

	/**
	 * Disables the XML validation.
	 *
	 * @return void
	 */
	protected function disableValidation() {
		$this->globalConfiguration->setAsString('openImmoSchema', '');
	}

	/**
	 * Copies a file or a folder from the extension's tests/fixtures/ folder
	 * into the temporary test import folder.
	 *
	 * @param string $fileName
	 *        File or folder to copy. Must be a relative path to existent files within the tests/fixtures/ folder.
	 *        Leave empty to create an empty import folder.
	 * @param string $newFileName
	 *        new file name in case it should be different from the original one, may be empty
	 *
	 * @return void
	 */
	protected function copyTestFileIntoImportFolder($fileName, $newFileName = '') {
		// creates an import folder if there is none
		if (!is_dir($this->importFolder)) {
			GeneralUtility::mkdir($this->importFolder);
		}
		$this->testImportFolderExists = TRUE;

		if ($fileName !== '') {
			copy(
				ExtensionManagementUtility::extPath('realty') . 'tests/fixtures/tx_realty_fixtures/' . $fileName,
				$this->importFolder . (($newFileName !== '') ? $newFileName : basename($fileName))
			);
		}
	}

	/**
	 * Deletes the test import folder if it has been created during the tests.
	 * Otherwise does nothing.
	 *
	 * @return void
	 */
	protected function deleteTestImportFolder() {
		if ($this->testImportFolderExists) {
			GeneralUtility::rmdir($this->importFolder, TRUE);
			$this->testImportFolderExists = FALSE;
		}
	}

	/**
	 * Checks if the ZIPArchive class is available. If it is not available, the
	 * current test will be marked as skipped.
	 *
	 * @return void
	 */
	protected function checkForZipArchive() {
		if (!in_array('zip', get_loaded_extensions(), TRUE)) {
			self::markTestSkipped('This PHP installation does not provide the ZIPArchive class.');
		}
	}

	/*
	 * Tests concerning the ZIP extraction.
	 */

	/**
	 * @test
	 */
	public function getPathsOfZipsToExtract() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->copyTestFileIntoImportFolder('bar.zip');

		self::assertSame(
			glob($this->importFolder . '*.zip'),
			array_values($this->fixture->getPathsOfZipsToExtract($this->importFolder))
		);
	}

	/**
	 * @test
	 */
	public function getNameForExtractionFolder() {
		$this->copyTestFileIntoImportFolder('bar.zip');

		self::assertSame(
			'bar/',
			$this->fixture->getNameForExtractionFolder('bar.zip')
		);
	}

	/**
	 * @test
	 */
	public function unifyPathDoesNotChangeCorrectPath() {
		self::assertSame(
			'correct/path/',
			$this->fixture->unifyPath('correct/path/')
		);
	}

	/**
	 * @test
	 */
	public function unifyPathTrimsAndAddsNecessarySlash() {
		self::assertSame(
			'incorrect/path/',
			$this->fixture->unifyPath('incorrect/path')
		);
	}

	/**
	 * @test
	 */
	public function createExtractionFolderForExistingZip() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$dirName = $this->fixture->createExtractionFolder($this->importFolder . 'foo.zip');

		self::assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
		self::assertSame(
			$this->importFolder . 'foo/',
			$dirName
		);
	}

	/**
	 * @test
	 */
	public function createExtractionFolderForNonExistingZip() {
		$this->copyTestFileIntoImportFolder('');
		$dirName = $this->fixture->createExtractionFolder($this->importFolder . 'foobar.zip');

		self::assertFalse(
			is_dir($this->importFolder . 'foobar/')
		);
		self::assertSame(
			'',
			$dirName
		);
	}

	/**
	 * @test
	 */
	public function extractZipIfOneZipToExtractExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');

		self::assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function extractZipIfZipDoesNotExist() {
		$this->copyTestFileIntoImportFolder('');
		$this->fixture->extractZip($this->importFolder . 'foobar.zip');

		self::assertFalse(
			is_dir($this->importFolder . 'foobar/')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderWithOneXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');

		self::assertSame(
			$this->importFolder . 'foo/foo.xml',
			$this->fixture->getPathForXml($this->importFolder . 'foo.zip')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderNotExists() {
		$this->copyTestFileIntoImportFolder('foo.zip');

		self::assertSame(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'foo.zip')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderWithTwoXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar-bar.zip');

		self::assertSame(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'bar-bar.zip')
		);
	}

	/**
	 * @test
	 */
	public function getPathForXmlIfFolderWithoutXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->extractZip($this->importFolder . 'empty.zip');

		self::assertSame(
			'',
			$this->fixture->getPathForXml($this->importFolder . 'empty.zip')
		);
	}


	////////////////////////////////////////////////////////////
	// Tests concerning copyImagesAndDocumentsFromExtractedZip
	////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesJpgImagesIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		self::assertTrue(
			file_exists($this->importFolder . 'bar.jpg')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesPdfFilesIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('pdf.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'foo.pdf')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipNotCopiesPsFilesIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('ps.zip');
		$this->fixture->importFromZip();

		self::assertFalse(
			file_exists($this->importFolder . 'foo.ps')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesJpgImagesWithUppercasedExtensionsIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo-uppercased.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'foo.JPG')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipTwiceCopiesImagesUniquelyNamedIntoTheUploadFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->copyTestFileIntoImportFolder('foo.zip', 'foo2.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		self::assertTrue(
			file_exists($this->importFolder . 'foo_00.jpg')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipCopiesImagesForRealtyRecord() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'foo.jpg')
		);
		self::assertTrue(
			file_exists($this->importFolder . 'bar.jpg')
		);
	}

	/**
	 * @test
	 */
	public function copyImagesAndDocumentsFromExtractedZipNotCopiesImagesForRecordWithDeletionFlagSet() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo-deleted.zip');
		$this->fixture->importFromZip();

		self::assertFalse(
			file_exists($this->importFolder . 'foo.jpg')
		);
		self::assertFalse(
			file_exists($this->importFolder . 'bar.jpg')
		);
	}


	////////////////////////////////
	// Tests concerning cleanUp().
	////////////////////////////////

	/**
	 * @test
	 */
	public function cleanUpRemovesAFolderCreatedByTheImporter() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->createExtractionFolder($this->importFolder . 'foo.zip');
		$this->fixture->cleanUp($this->importFolder);

		self::assertFalse(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveAForeignFolderAlthoughItIsNamedLikeAZipToImport() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		GeneralUtility::mkdir($this->importFolder . 'foo/');
		$this->fixture->cleanUp($this->importFolder);

		self::assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipThatIsNotMarkedAsDeletable() {
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->cleanUp($this->importFolder . 'foo.zip');

		self::assertTrue(
			file_exists($this->importFolder . 'foo.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesCreatedFolderAlthoughTheExtractedArchiveContainsAFolder() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('contains-folder.zip');
		$this->fixture->importFromZip();

		self::assertFalse(
			is_dir($this->importFolder . 'contains-folder/')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemovesZipWithOneXmlInItIfDeletingZipsIsDisabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', FALSE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesZipWithOneXmlInItIfDeletingZipsIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipWithoutXmls() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('empty.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'empty.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipWithTwoXmls() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar-bar.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'bar-bar.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesZipFileInASubFolderOfTheImportFolder() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		// just to ensure the import folder exists
		$this->copyTestFileIntoImportFolder('empty.zip');
		// copyTestFileIntoImportFolder() cannot copy folders
		GeneralUtility::mkdir($this->importFolder . 'changed-copy-of-same-name/');
		copy(
			ExtensionManagementUtility::extPath('realty') . 'tests/fixtures/tx_realty_fixtures/' . 'changed-copy-of-same-name/same-name.zip',
			$this->importFolder . 'changed-copy-of-same-name/same-name.zip'
		);

		$this->fixture->importFromZip();

		self::assertFalse(
			file_exists($this->importFolder . 'changed-copy-of-same-name/same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipOfUnregisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpRemovesZipOfRegisteredOwnerIfOwnerRestrictionIsEnabled() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->testingFramework->createFrontEndUser('', array('tx_realty_openimmo_anid' => 'foo'));
		// 'deleteZipsAfterImport' is set to TRUE during setUp()
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertFalse(
			file_exists($this->importFolder . 'same-name.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveZipIfOwnerWhichHasReachedObjectLimitDuringImport() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->createFrontEndUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);

		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);

		$this->copyTestFileIntoImportFolder('two-objects.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'two-objects.zip')
		);
	}

	/**
	 * @test
	 */
	public function cleanUpDoesNotRemoveIfZipOwnerWhichHasNoObjectsLeftToEnter() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
		$this->testingFramework->createRecord('tx_realty_objects', array('owner' => $feUserUid));
		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->copyTestFileIntoImportFolder('two-objects.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			file_exists($this->importFolder . 'two-objects.zip')
		);
	}


	////////////////////////////////////////////////////////
	// Tests concerning loading and importing the XML file.
	////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function loadXmlFileIfFolderWithOneXmlExists() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		self::assertSame(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	/**
	 * @test
	 */
	public function loadXmlFileIfXmlIsValid() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->extractZip($this->importFolder . 'foo.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

		self::assertSame(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	/**
	 * @test
	 */
	public function loadXmlFileIfXmlIsInvalid() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->fixture->extractZip($this->importFolder . 'bar.zip');
		$this->fixture->loadXmlFile($this->importFolder . 'bar.zip');

		self::assertSame(
			'DOMDocument',
			get_class($this->fixture->getImportedXml())
		);
	}

	/**
	 * @test
	 */
	public function importARecordAndImportItAgainAfterContentsHaveChanged() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();
		$this->fixture->importFromZip();
		$result = Tx_Oelib_Db::selectSingle(
			'uid',
			'tx_realty_objects',
			'object_number = "bar1234567" AND zip = "zip"'
		);

		// overwrites "same-name.zip" in the import folder
		$this->copyTestFileIntoImportFolder('changed-copy-of-same-name/same-name.zip');
		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" AND zip="changed zip" '
					.'AND uid='.$result['uid']
			)
		);
	}

	/**
	 * @test
	 */
	public function importFromZipSkipsRecordsIfAFolderNamedLikeTheRecordAlreadyExists() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('foo.zip');
		GeneralUtility::mkdir($this->importFolder . 'foo/');
		$result = $this->fixture->importFromZip();

		self::assertContains(
			$this->translator->translate('message_surplus_folder'),
			$result
		);
		self::assertTrue(
			is_dir($this->importFolder . 'foo/')
		);
	}

	/**
	 * @test
	 */
	public function importFromZipImportsFromZipFileInASubFolderOfTheImportFolder() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		// just to ensure the import folder exists
		$this->copyTestFileIntoImportFolder('empty.zip');
		// copyTestFileIntoImportFolder() cannot copy folders
		GeneralUtility::mkdir($this->importFolder . 'changed-copy-of-same-name/');
		copy(
			ExtensionManagementUtility::extPath('realty') . 'tests/fixtures/tx_realty_fixtures/' .
				'changed-copy-of-same-name/same-name.zip',
			$this->importFolder . 'changed-copy-of-same-name/same-name.zip'
		);

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" AND zip="changed zip" '
			)
		);
	}

	/**
	 * @test
	 */
	public function recordIsNotWrittenToTheDatabaseIfTheRequiredFieldsAreNotSet() {
		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>foobar</strasse>'
							.'<plz>bar</plz>'
						.'</geo>'
						.'<freitexte>'
							.'<lage>foo</lage>'
						.'</freitexte>'
						.'<verwaltung_techn>'
							.'<objektnr_extern>'.$objectNumber.'</objektnr_extern>'
						.'</verwaltung_techn>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="' . $objectNumber . '"' .
					Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function addWithAllRequiredFieldsSavesNewRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
				. '<anbieter>'
					. '<immobilie>'
						. '<objektkategorie>'
							. '<nutzungsart WOHNEN="1"/>'
							. '<vermarktungsart KAUF="1"/>'
							. '<objektart><zimmer/></objektart>'
						. '</objektkategorie>'
						. '<geo>'
							. '<plz>bar</plz>'
						. '</geo>'
						. '<kontaktperson>'
							. '<name>bar</name>'
							. '<email_zentrale>bar</email_zentrale>'
						. '</kontaktperson>'
						. '<verwaltung_techn>'
							. '<openimmo_obid>foo</openimmo_obid>'
							. '<aktion/>'
							. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
						. '</verwaltung_techn>'
					. '</immobilie>'
					. '<openimmo_anid>foo</openimmo_anid>'
					. '<firma>bar</firma>'
				. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertTrue(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function updateWithAllRequiredFieldsSavesNewRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
				. '<anbieter>'
					. '<immobilie>'
						. '<objektkategorie>'
							. '<nutzungsart WOHNEN="1"/>'
							. '<vermarktungsart KAUF="1"/>'
							. '<objektart><zimmer/></objektart>'
						. '</objektkategorie>'
						. '<geo>'
							. '<plz>bar</plz>'
						. '</geo>'
						. '<kontaktperson>'
							. '<name>bar</name>'
							. '<email_zentrale>bar</email_zentrale>'
						. '</kontaktperson>'
						. '<verwaltung_techn>'
							. '<openimmo_obid>foo</openimmo_obid>'
							. '<aktion aktionart="CHANGE"/>'
							. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
						. '</verwaltung_techn>'
					. '</immobilie>'
					. '<openimmo_anid>foo</openimmo_anid>'
					. '<firma>bar</firma>'
				. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertTrue(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function deleteWithAllRequiredFieldsWithoutRecordInDatabaseNotSavesNewRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
				. '<anbieter>'
					. '<immobilie>'
						. '<objektkategorie>'
							. '<nutzungsart WOHNEN="1"/>'
							. '<vermarktungsart KAUF="1"/>'
							. '<objektart><zimmer/></objektart>'
						. '</objektkategorie>'
						. '<geo>'
							. '<plz>bar</plz>'
						. '</geo>'
						. '<kontaktperson>'
							. '<name>bar</name>'
							. '<email_zentrale>bar</email_zentrale>'
						. '</kontaktperson>'
						. '<verwaltung_techn>'
							. '<openimmo_obid>foo</openimmo_obid>'
							. '<aktion aktionart="DELETE"/>'
							. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
						. '</verwaltung_techn>'
					. '</immobilie>'
					. '<openimmo_anid>foo</openimmo_anid>'
					. '<firma>bar</firma>'
				. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertFalse(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function addWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectData = '<immobilie>'
		. '<objektkategorie>'
		. '<nutzungsart WOHNEN="1"/>'
		. '<vermarktungsart KAUF="1"/>'
		. '<objektart><zimmer/></objektart>'
		. '</objektkategorie>'
		. '<geo>'
		. '<plz>bar</plz>'
		. '</geo>'
		. '<kontaktperson>'
		. '<name>bar</name>'
		. '<email_zentrale>bar</email_zentrale>'
		. '</kontaktperson>'
		. '<verwaltung_techn>'
		. '<openimmo_obid>foo</openimmo_obid>'
		. '<aktion/>'
		. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
		. '</verwaltung_techn>'
		. '</immobilie>';

		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. $objectData . $objectData
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function updateWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectData = '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>';

		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. $objectData . $objectData
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function deleteWithTwoIdenticalObjectsWithAllRequiredFieldsWithoutRecordInDatabaseNotSavesNewRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectData = '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>';

		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. $objectData . $objectData
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertFalse(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function addWithAllRequiredFieldsUpdatesMatchingExistingRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId .  '</openimmo_obid>'
			. '<aktion/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function updateWithAllRequiredFieldsUpdatesMatchingExistingRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>' . $objectId . '</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function deleteWithAllRequiredFieldsMarksMatchingExistingRecordAsDeleted() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId . '</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertFalse(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0')
		);
		self::assertTrue(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1')
		);
	}

	/**
	 * @test
	 */
	public function deleteTwoTimesWithAllRequiredFieldsMarksMatchingExistingRecordAsDeleted() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
			)
		);

		$objectData = '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId . '</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>';

		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. $objectData . $objectData
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertFalse(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0')
		);
		self::assertTrue(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1')
		);
	}

	/**
	 * @test
	 */
	public function addWithAllRequiredFieldsAndMatchingExistingDeletedRecordCreatesNewRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'deleted' => 1,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId .  '</openimmo_obid>'
			. '<aktion/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0')
		);
		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1')
		);
	}

	/**
	 * @test
	 */
	public function updateWithAllRequiredFieldsAndMatchingExistingDeletedRecordCreatesNewRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'deleted' => 1,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>' . $objectId . '</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0')
		);
		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1')
		);
	}

	/**
	 * @test
	 */
	public function deleteWithAllRequiredFieldsWithMatchingExistingDeletedRecordNotAddsSecondDeletedRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'deleted' => 1,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId . '</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertFalse(
			$this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0')
		);
		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1')
		);
	}

	/**
	 * @test
	 */
	public function addWithAllRequiredFieldsUpdatesMatchingExistingHiddenRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'hidden' => 1,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId .  '</openimmo_obid>'
			. '<aktion/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND hidden = 1')
		);
		self::assertSame(
			0,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND hidden = 0')
		);
	}

	/**
	 * @test
	 */
	public function updateWithAllRequiredFieldsUpdatesMatchingExistingHiddenRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'hidden' => 1,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>' . $objectId . '</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND hidden = 1')
		);
		self::assertSame(
			0,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '" AND hidden = 0')
		);
	}

	/**
	 * @test
	 */
	public function deleteWithAllRequiredFieldsMarksMatchingExistingHiddenRecordAsDeleted() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$objectId = 'foo';
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'object_number' => $objectNumber,
				'openimmo_obid' => $objectId,
				'hidden' => 1,
			)
		);
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>' . $objectId . '</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertFalse(
			$this->testingFramework->existsRecord(
				'tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0 AND hidden = 1'
			)
		);
		self::assertFalse(
			$this->testingFramework->existsRecord(
				'tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 0 AND hidden = 0'
			)
		);
		self::assertFalse(
			$this->testingFramework->existsRecord(
				'tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1 AND hidden = 0'
			)
		);
		self::assertTrue(
			$this->testingFramework->existsRecord(
				'tx_realty_objects', 'object_number="' . $objectNumber . '" AND deleted = 1 AND hidden = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function addAndChangeWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function changeAndAddWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function deleteAndChangeWithTwoIdenticalObjectsWithAllRequiredFieldsSavesNoRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			0,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function changeAndDeleteWithTwoIdenticalObjectsWithAllRequiredFieldsSavesOneRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>'
			. '<anbieter>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="CHANGE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<immobilie>'
			. '<objektkategorie>'
			. '<nutzungsart WOHNEN="1"/>'
			. '<vermarktungsart KAUF="1"/>'
			. '<objektart><zimmer/></objektart>'
			. '</objektkategorie>'
			. '<geo>'
			. '<plz>bar</plz>'
			. '</geo>'
			. '<kontaktperson>'
			. '<name>bar</name>'
			. '<email_zentrale>bar</email_zentrale>'
			. '</kontaktperson>'
			. '<verwaltung_techn>'
			. '<openimmo_obid>foo</openimmo_obid>'
			. '<aktion aktionart="DELETE"/>'
			. '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
			. '</verwaltung_techn>'
			. '</immobilie>'
			. '<openimmo_anid>foo</openimmo_anid>'
			. '<firma>bar</firma>'
			. '</anbieter>'
			. '</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function changeAndAddDeleteWithTwoIdenticalObjectsWithAllRequiredFieldsAndContactDataNotSavesAnyRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>
			<uebertragung xmlns="" art="OFFLINE" umfang="TEIL" modus="CHANGE" version="1.2.4" sendersoftware="OOF" senderversion="$Rev: 49210 $" techn_email="heidi.loehr@lob-immobilien.de" timestamp="2015-06-22T13:55:07.0+00:00"/>
			<anbieter xmlns="">
			<firma>Doe Immobilien</firma>
			<openimmo_anid>123456</openimmo_anid>

			<immobilie>
			<objektkategorie>
			<nutzungsart WOHNEN="1" GEWERBE="0"/>
			<vermarktungsart KAUF="1" MIETE_PACHT="0"/>
			<objektart>
			<wohnung wohnungtyp="ETAGE"/>
			</objektart>
			</objektkategorie>
			<geo>
			<plz>55127</plz>
			<ort>Mainz / Lerchenberg</ort>
			<strasse>Rubensallee</strasse>
			<hausnummer>1</hausnummer>
			<land iso_land="DEU"/>
			<etage>3</etage>
			<anzahl_etagen>7</anzahl_etagen>
			</geo>
			<kontaktperson>
			<email_zentrale>offerer@example.com</email_zentrale>
			<email_direkt>offerer@example.com</email_direkt>
			<name>Doe</name>
			<vorname>Jane</vorname>
			<anrede>Frau</anrede>
			<anrede_brief>Sehr geehrte Frau Doe,</anrede_brief>
			<firma>Doe Immobilien</firma>
			<zusatzfeld/>
			<strasse>Dessauer Straße</strasse>
			<hausnummer>1</hausnummer>
			<plz>55000</plz>
			<ort>Bad Kreuznach</ort>
			<land iso_land="DEU"/>
			<url>www.oliverklee.de</url>
			</kontaktperson>
			<preise>
			<kaufpreis>149000.00</kaufpreis>
			<hausgeld>345.00</hausgeld>
			<aussen_courtage mit_mwst="1">5,95 % inkl. 19% MwSt.</aussen_courtage>
			<waehrung iso_waehrung="EUR"/>
			<stp_carport stellplatzmiete="0.00" anzahl="0"/>
			<stp_duplex stellplatzmiete="0.00" anzahl="0"/>
			<stp_freiplatz stellplatzmiete="0.00" anzahl="1"/>
			<stp_garage stellplatzmiete="0.00" anzahl="0"/>
			<stp_parkhaus stellplatzmiete="0.00" anzahl="0"/>
			<stp_tiefgarage stellplatzmiete="0.00" anzahl="0"/>
			<stp_sonstige platzart="SONSTIGES" stellplatzmiete="0.00" anzahl="0"/>
			</preise>
			<versteigerung/>
			<flaechen>
			<wohnflaeche>88.00</wohnflaeche>
			<anzahl_zimmer>3.00</anzahl_zimmer>
			<anzahl_badezimmer>1.00</anzahl_badezimmer>
			<anzahl_sep_wc>1.00</anzahl_sep_wc>
			<anzahl_stellplaetze>1</anzahl_stellplaetze>
			</flaechen>
			<ausstattung>
			<heizungsart FERN="1"/>
			<fahrstuhl PERSONEN="1"/>
			<kabel_sat_tv>1</kabel_sat_tv>
			<unterkellert keller="JA"/>
			</ausstattung>
			<zustand_angaben>
			<baujahr>1971</baujahr>
			<zustand zustand_art="GEPFLEGT"/>
			<verkaufstatus stand="OFFEN"/>
			</zustand_angaben>
			<verwaltung_objekt>
			<objektadresse_freigeben>0</objektadresse_freigeben>
			<verfuegbar_ab>01.08.2015</verfuegbar_ab>
			</verwaltung_objekt>
			<verwaltung_techn>
			<objektnr_intern>550</objektnr_intern>
			<objektnr_extern>OR273</objektnr_extern>
			<aktion aktionart="CHANGE"/>
			<openimmo_obid>123456_550_OR273</openimmo_obid>
			<kennung_ursprung>onOffice Software</kennung_ursprung>
			<stand_vom>2015-06-22</stand_vom>
			<weitergabe_generell>1</weitergabe_generell>
			</verwaltung_techn>
			</immobilie>

			<immobilie>
			<objektkategorie>
			<nutzungsart WOHNEN="1" GEWERBE="0"/>
			<vermarktungsart KAUF="1" MIETE_PACHT="0"/>
			<objektart>
			<wohnung wohnungtyp="ETAGE"/>
			</objektart>
			</objektkategorie>
			<geo>
			<plz>55127</plz>
			<ort>Mainz / Lerchenberg</ort>
			<geokoordinaten breitengrad="49.96550" laengengrad="8.18754"/>
			</geo>
			<kontaktperson>
			<email_zentrale>offerer@example.com</email_zentrale>
			<email_direkt>offerer@example.com</email_direkt>
			<name>Doe</name>
			<vorname>Jane</vorname>
			<anrede>Frau</anrede>
			<name/>
			</kontaktperson>
			<verwaltung_techn>
			<objektnr_intern>550</objektnr_intern>
			<objektnr_extern>OR273</objektnr_extern>
			<aktion aktionart="DELETE"/>
			<openimmo_obid>123456_550_OR273</openimmo_obid>
			<kennung_ursprung>onOffice Software</kennung_ursprung>
			<stand_vom>2015-06-22</stand_vom>
			<weitergabe_generell>1</weitergabe_generell>
			</verwaltung_techn>
			</immobilie>

			</anbieter>
			</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			0,
			$this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
		);
	}

	/**
	 * @test
	 */
	public function ensureContactEmailNotChangesAddressIfValidAddressIsSet() {
		$this->fixture->loadRealtyObject(
			array('contact_email' => 'foo-valid@email-address.org')
		);
		$this->fixture->ensureContactEmail();

		self::assertSame(
			'foo-valid@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	/**
	 * @test
	 */
	public function ensureContactEmailSetsDefaultAddressIfEmptyAddressSet() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);
		$this->fixture->loadRealtyObject(array('contact_email' => ''));
		$this->fixture->ensureContactEmail();

		self::assertSame(
			'default_address@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	/**
	 * @test
	 */
	public function ensureContactEmailSetsDefaultAddressIfInvalidAddressIsSet() {
		$this->globalConfiguration->setAsString(
			'emailAddress',
			'default_address@email-address.org'
		);
		$this->fixture->loadRealtyObject(array('contact_email' => 'foo'));
		$this->fixture->ensureContactEmail();

		self::assertSame(
			'default_address@email-address.org',
			$this->fixture->getContactEmailFromRealtyObject()
		);
	}

	/**
	 * @test
	 */
	public function importStoresZipsWithLeadingZeroesIntoDb() {
		$this->testingFramework->markTableAsDirty(
			'tx_realty_objects' . ',' . 'tx_realty_house_types'
		);

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>01234</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
							'<email_zentrale>bar</email_zentrale>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<aktion/>' .
							'<objektnr_extern>' .
								$objectNumber .
							'</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="' . $objectNumber . '" AND zip="01234"' .
					Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importStoresNumberOfRoomsWithDecimalsIntoDb() {
		$this->testingFramework->markTableAsDirty(
			'tx_realty_objects' . ',' . 'tx_realty_house_types'
		);

		$objectNumber = 'bar1234567';
		$dummyDocument = new DOMDocument();
		$dummyDocument->loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<flaechen>' .
							'<anzahl_zimmer>1.25</anzahl_zimmer>' .
						'</flaechen>' .
						'<geo>' .
							'<plz>01234</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>' .
								$objectNumber .
							'</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($dummyDocument);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="' . $objectNumber . '" AND ' .
					'number_of_rooms = 1.25' .
					Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importUtf8FileWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->copyTestFileIntoImportFolder('charset-UTF8.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_objects',
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}

	/**
	 * @test
	 */
	public function importUtf8FileWithUtf8AsDefaultEncodingAndNoXmlPrologueWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->copyTestFileIntoImportFolder('charset-UTF8-default.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_objects',
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}

	/**
	 * @test
	 */
	public function importIso88591FileWithCorrectUmlauts() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->copyTestFileIntoImportFolder('charset-ISO8859-1.zip');
		$this->fixture->importFromZip();

		self::assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				'tx_realty_objects',
				'openimmo_anid="test-anid-with-umlaut-ü"'
			)
		);
	}


	//////////////////////////////////////////////////////////////////
	// Tests concerning the restricted import for registered owners.
	//////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function recordWithAnidThatMatchesAnExistingFeUserIsImportedForEnabledOwnerRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$feUserUid = $this->testingFramework->createFrontEndUser('', array('tx_realty_openimmo_anid' => 'foo'));
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'openimmo_anid="foo" AND owner=' . $feUserUid
			)
		);
	}

	/**
	 * @test
	 */
	public function recordWithAnidThatDoesNotMatchAnExistingFeUserIsNotImportedForEnabledOwnerRestriction() {
		$this->checkForZipArchive();

		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertSame(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'openimmo_anid="foo"'
			)
		);
	}

	/**
	 * @test
	 */
	public function recordWithAnidThatMatchesAnExistingFeUserInAnAllowedGroupIsImportedForEnabledOwnerAndGroupRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo'));
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'openimmo_anid="foo" AND owner=' . $feUserUid
			)
		);
	}

	/**
	 * @test
	 */
	public function recordWithAnidThatMatchesAnExistingFeUserInAForbiddenGroupIsNotImportedForEnabledOwnerAndGroupRestriction() {
		$this->checkForZipArchive();

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo'));
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid + 1);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertSame(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'openimmo_anid="foo" AND owner=' . $feUserUid
			)
		);
	}


	////////////////////////////////////////////////
	// Tests concerning the object limit for users
	////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithObjectLimitReachedDoesNotImportAnyFurtherRecords() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects',
			array('owner' => $feUserUid)
		);

		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

		$singleObject = new DOMDocument();
		$singleObject->loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($singleObject);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'owner =' . $feUserUid
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithObjectLimitNotReachedDoesImportRecords() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 42,
			)
		);

		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

		$multipleRecords = new DOMDocument();
		$multipleRecords->loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar2345678</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($multipleRecords);
		$this->fixture->writeToDatabase($records[0]);
		$this->fixture->writeToDatabase($records[1]);

		self::assertSame(
			2,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'owner =' . $feUserUid
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithoutObjectLimitDoesImportRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');
		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, array('tx_realty_openimmo_anid' => 'foo'));
		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

		$singleObject = new DOMDocument();
		$singleObject->loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($singleObject);
		$this->fixture->writeToDatabase($records[0]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'owner =' . $feUserUid
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseForUserWithOneObjectLeftToLimitImportsOnlyOneRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
			)
		);

		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

		$multipleRecords = new DOMDocument();
		$multipleRecords->loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar1234567</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1"/>' .
							'<vermarktungsart KAUF="1"/>' .
							'<objektart><zimmer/></objektart>' .
						'</objektkategorie>' .
						'<geo>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<kontaktperson>' .
							'<name>bar</name>' .
						'</kontaktperson>' .
						'<verwaltung_techn>' .
							'<openimmo_obid>foo</openimmo_obid>' .
							'<objektnr_extern>bar2345678</objektnr_extern>' .
						'</verwaltung_techn>' .
					'</immobilie>' .
					'<openimmo_anid>foo</openimmo_anid>' .
					'<firma>bar</firma>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$records = $this->fixture->convertDomDocumentToArray($multipleRecords);
		$this->fixture->writeToDatabase($records[0]);
		$this->fixture->writeToDatabase($records[1]);

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'owner =' . $feUserUid
			)
		);
	}

	/**
	 * @test
	 */
	public function importFromZipForUserWithObjectLimitReachedReturnsObjectLimitReachedErrorMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->testingFramework->createFrontEndUserGroup();
		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$feUserGroupUid,
			array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
				'username' => 'fooBar',
			)
		);
		$this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', TRUE);
		$this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
		$this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', TRUE);
		$this->copyTestFileIntoImportFolder('two-objects.zip');

		self::assertContains(
			sprintf(
				$this->translator->translate('message_object_limit_reached'),
				'fooBar', $feUserUid, 1
			),
			$this->fixture->importFromZip()
		);
	}


	////////////////////////////////////////////////////////////////////
	// Tests concerning the preparation of e-mails containing the log.
	////////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function prepareEmailsReturnsEmptyArrayWhenEmptyArrayGiven() {
		$emailData = array();

		self::assertSame(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsReturnsEmptyArrayWhenInvalidArrayGiven() {
		$emailData = array('invalid' => 'array');

		self::assertSame(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsFillsEmptyEmailFieldWithDefaultAddressIfNotifyContactPersonsIsEnabled() {
		$this->globalConfiguration->setAsString('emailAddress', 'default_address@email-address.org');

		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		self::assertSame(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsReplacesNonEmptyEmailAddressIfNotifyContactPersonsIsDisabled() {
		$this->globalConfiguration->setAsString('emailAddress', 'default_address@email-address.org');
		$this->globalConfiguration->setAsBoolean('notifyContactPersons', FALSE);
		$emailData = array(
			array(
				'recipient' => 'foo-valid@email-address.org',
				'objectNumber' => 'foo',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		self::assertSame(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsUsesLogEntryIfOnlyErrorsIsDisabled() {
		$this->globalConfiguration->setAsString('emailAddress', 'default_address@email-address.org');

		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'log entry',
				'errorLog' => 'error log'
			)
		);

		self::assertSame(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'log entry')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsUsesLogEntryIfOnlyErrorsIsEnabled() {
		$this->globalConfiguration->setAsBoolean('onlyErrors', TRUE);
		$this->globalConfiguration->setAsString('emailAddress', 'default_address@email-address.org');

		$emailData = array(
			array(
				'recipient' => '',
				'objectNumber' => 'foo',
				'logEntry' => 'log entry',
				'errorLog' => 'error log'
			)
		);

		self::assertSame(
			array(
				'default_address@email-address.org' => array(
					array('foo' => 'error log')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsFillsEmptyObjectNumberFieldWithWrapper() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => '',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			)
		);

		self::assertSame(
			array(
				'foo' => array(
					array('------' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsSortsMessagesForOneRecepientWhichHaveTheSameObjectNumber() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			),
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'foo',
				'errorLog' => 'foo'
			),
		);

		self::assertSame(
			array(
				'foo' => array(
					array('number' => 'bar'),
					array('number' => 'foo')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsSortsMessagesForTwoRecepientWhichHaveTheSameObjectNumber() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'foo',
				'errorLog' => 'foo'
			),
			array(
				'recipient' => 'bar',
				'objectNumber' => 'number',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			),
		);

		self::assertSame(
			array(
				'foo' => array(
					array('number' => 'foo')
				),
				'bar' => array(
					array('number' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsSnipsObjectNumbersWithNothingToReport() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => 'bar',
				'errorLog' => 'bar'
			),
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => '',
				'errorLog' => ''
			)
		);

		self::assertSame(
			array(
				'foo' => array(
					array('number' => 'bar')
				)
			),
			$this->fixture->prepareEmails($emailData)
		);
	}

	/**
	 * @test
	 */
	public function prepareEmailsSnipsRecipientWhoDoesNotReceiveMessages() {
		$emailData = array(
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => '',
				'errorLog' => ''
			),
			array(
				'recipient' => 'foo',
				'objectNumber' => 'number',
				'logEntry' => '',
				'errorLog' => ''
			),
		);

		self::assertSame(
			array(),
			$this->fixture->prepareEmails($emailData)
		);
	}


	/////////////////////////////////
	// Test for clearing the cache.
	/////////////////////////////////

	/**
	 * @test
	 */
	public function importFromZipClearsFrontEndCacheAfterImport() {
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('foo.zip');
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement($pageUid, array('list_type' => 'realty_pi1'));

		/** @var $cacheFrontEnd t3lib_cache_frontend_AbstractFrontend|PHPUnit_Framework_MockObject_MockObject */
		$cacheFrontEnd = $this->getMock(
			't3lib_cache_frontend_AbstractFrontend',
			array('getIdentifier', 'set', 'get', 'getByTag', 'getBackend'),
			array(), '', FALSE
		);
		$cacheFrontEnd->expects(self::once())->method('getIdentifier')->will(self::returnValue('cache_pages'));
		/** @var \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface|PHPUnit_Framework_MockObject_MockObject $cacheBackEnd */
		$cacheBackEnd = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\TaggableBackendInterface');
		$cacheFrontEnd->expects(self::any())->method('getBackend')->will(self::returnValue($cacheBackEnd));
		$cacheBackEnd->expects(self::atLeastOnce())->method('flushByTag');

		$cacheManager = new CacheManager();
		$cacheManager->registerCache($cacheFrontEnd);
		tx_realty_cacheManager::injectCacheManager($cacheManager);

		$this->fixture->importFromZip();
	}

	/*
	 * Tests concerning the log messages.
	 */

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setAsString('openImmoSchema', '');

		self::assertContains(
			$this->translator->translate('message_no_schema_file'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->globalConfiguration->setAsString('openImmoSchema', '/any/not/existing/path');

		self::assertContains(
			$this->translator->translate('message_invalid_schema_file_path'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageMissingRequiredFields() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->disableValidation();

		self::assertContains(
			$this->translator->translate('message_fields_required'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsLogMessageThatNoRecordWasLoadedForZipWithNonOpenImmoXml() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('bar.zip');
		$this->disableValidation();

		self::assertContains(
			$this->translator->translate('message_object_not_loaded'),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipReturnsMessageThatTheLogWasSentToTheDefaultAddressIfNoRecordWasLoaded() {
		$this->checkForZipArchive();

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->disableValidation();

		self::assertContains(
			'default-address@example.org',
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipForNonExistingImportFolderReturnsFolderNotExistingErrorMessage() {
		$this->checkForZipArchive();

		$path = '/any/not/existing/import-path/';
		$this->globalConfiguration->setAsString('importFolder', $path);

		self::assertContains(
			sprintf(
				$this->translator->translate('message_import_directory_not_existing'),
				$path,
				get_current_user()
			),
			$this->fixture->importFromZip()
		);
	}

	/**
	 * @test
	 */
	public function importFromZipForNonExistingUploadFolderReturnsFolderNotExistingErrorMessage() {
		$this->checkForZipArchive();
		$this->copyTestFileIntoImportFolder('foo.zip');

		$path = '/any/not/existing/upload-path/';
		$this->fixture->setUploadDirectory($path);

		self::assertContains(
			sprintf(
				$this->translator->translate('message_upload_directory_not_existing'),
				$path
			),
			$this->fixture->importFromZip()
		);
	}


	//////////////////////////////////////////////////////////////
	// Tests for setting the PID depending on the ZIP file name.
	//////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function importedRecordHasTheConfiguredPidByDefault() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" '
					. 'AND pid=' . $this->systemFolderPid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importedRecordHasTheConfiguredPidIfTheFilenameHasNoMatches() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->disableValidation();

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString('pidsForRealtyObjectsAndImagesByFileName', 'nomatch:' . $pid . ';');
		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $this->systemFolderPid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheOnlyPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString('pidsForRealtyObjectsAndImagesByFileName', 'same:' . $pid . ';');

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function overridePidCanMatchTheStartOfAString() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString('pidsForRealtyObjectsAndImagesByFileName', '^same:' . $pid . ';');

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function overridePidCanMatchTheEndOfAString() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString('pidsForRealtyObjectsAndImagesByFileName', 'name$:' . $pid . ';');

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheFirstPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'same:' . $pid . ';' . 'nomatch:' . $this->systemFolderPid . ';'
		);

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheLastPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'nomatch:' . $this->systemFolderPid . ';' . 'same:' . $pid . ';'
		);

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importedRecordOverridesPidIfTheFilenameMatchesTheMiddlePattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'nomatch1:' . $this->systemFolderPid . ';'
				.'same:' . $pid . ';'
				.'nomatch2:' . $this->systemFolderPid . ';'
		);

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}

	/**
	 * @test
	 */
	public function importedRecordOverridesPidStopsAtFirstMatchingPattern() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$this->disableValidation();
		$this->copyTestFileIntoImportFolder('same-name.zip');

		$pid = $this->testingFramework->createSystemFolder();
		$this->globalConfiguration->setAsString(
			'pidsForRealtyObjectsAndImagesByFileName',
			'sam:' . $pid . ';' . 'same:' . $this->systemFolderPid . ';'
		);

		$this->fixture->importFromZip();

		self::assertSame(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'object_number="bar1234567" ' .
					'AND pid=' . $pid . Tx_Oelib_Db::enableFields('tx_realty_objects')
			)
		);
	}


	/////////////////////////////////
	// Testing the e-mail contents.
	/////////////////////////////////
	// * Tests for the subject.
	/////////////////////////////

	/**
	 * @test
	 */
	public function emailSubjectIsSetCorrectly() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		self::assertSame(
			$this->translator->translate('label_subject_openImmo_import'),
			$this->message->getSubject()
		);
	}


	//////////////////////////////////////
	// * Tests concerning the recipient.
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function emailIsSentToContactEmailForValidContactEmailAndObjectAsContactDataSource() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('valid-email.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'contact-email-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultEmailForInvalidContactEmailAndObjectAsContactDataSource() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultAddressIfARecordIsNotLoadable() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToOwnersAddressForMatchingAnidAndNoContactEmailProvidedAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontEndUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'owner-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToOwnersAddressForMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontEndUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'owner-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToContactAddressForNonMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontEndUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'another-test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'contact-email-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToContactAddressForNoAnidAndSetContactEmailAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontEndUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->copyTestFileIntoImportFolder('valid-email.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'contact-email-address@valid-email.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultAddressForNonMatchingAnidAndNoContactEmailAndOwnerContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontEndUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'another-test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function emailIsSentToDefaultAddressForNeitherAnidNorContactEmailProvidedAndOwnerAsContactDataSource() {
		$this->checkForZipArchive();

		$this->testingFramework->createFrontEndUser(
			'',
			array(
				'tx_realty_openimmo_anid' => 'test-anid',
				'email' => 'owner-address@valid-email.org'
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->copyTestFileIntoImportFolder('foo.zip');
		$this->fixture->importFromZip();

		self::assertArrayHasKey(
			'default-address@example.org',
			$this->message->getTo()
		);
	}


	///////////////////////////////////
	// * Testing the e-mail contents.
	///////////////////////////////////

	/**
	 * @test
	 */
	public function sentEmailContainsTheObjectNumberLabel() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		self::assertContains(
			$this->translator->translate('label_object_number'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheIntroductionMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		self::assertContains(
			$this->translator->translate('message_introduction'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsTheExplanationMessage() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->copyTestFileIntoImportFolder('email.zip');
		$this->fixture->importFromZip();

		self::assertContains(
			$this->translator->translate('message_explanation'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailContainsMessageThatARecordWasNotImportedForMismatchingAnidsAndEnabledOwnerRestriction() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');

		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('same-name.zip');
		$this->fixture->importFromZip();

		self::assertContains(
			$this->translator->translate('message_openimmo_anid_not_matches_allowed_fe_user'),
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function sentEmailForUserWhoReachedHisObjectLimitContainsMessageThatRecordWasNotImported() {
		$this->checkForZipArchive();
		$this->testingFramework->markTableAsDirty('tx_realty_objects');
		$this->testingFramework->markTableAsDirty('tx_realty_house_types');

		$feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$feUserGroupUid, array(
				'tx_realty_openimmo_anid' => 'foo',
				'tx_realty_maximum_objects' => 1,
				'username' => 'fooBar',
			)
		);
		$this->globalConfiguration->setAsBoolean(
			'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
		);
		$this->globalConfiguration->setAsString(
			'allowedFrontEndUserGroups', $feUserGroupUid
		);

		$this->globalConfiguration->setAsBoolean(
			'onlyImportForRegisteredFrontEndUsers', TRUE
		);
		$this->copyTestFileIntoImportFolder('two-objects.zip');
		$this->fixture->importFromZip();

		self::assertContains(
			sprintf(
				$this->translator->translate('message_object_limit_reached'),
				'fooBar', $feUserUid, 1
			),
			$this->message->getBody()
		);
	}
}