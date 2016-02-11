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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Service_ListViewFactoryTest extends Tx_Phpunit_TestCase {
	/**
	 * @var ContentObjectRenderer|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $cObjMock;

	protected function setUp() {
		$this->cObjMock = $this->getMock(ContentObjectRenderer::class);
	}

	/////////////////////////////////////////////
	// Tests concerning the basic functionality
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function canCreateARealtyListViewInstance() {
		self::assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'realty_list', array(), $this->cObjMock
			) instanceof tx_realty_pi1_DefaultListView
		);
	}

	/**
	 * @test
	 */
	public function canCreateAFavoritesListViewInstance() {
		self::assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'favorites', array(), $this->cObjMock
			) instanceof tx_realty_pi1_FavoritesListView
		);
	}

	/**
	 * @test
	 */
	public function canCreateAMyObjectsListViewInstance() {
		self::assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'my_objects', array(), $this->cObjMock
			) instanceof tx_realty_pi1_MyObjectsListView
		);
	}

	/**
	 * @test
	 */
	public function canCreateAnObjectsByOwnerListViewInstance() {
		self::assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'objects_by_owner', array(), $this->cObjMock
			) instanceof tx_realty_pi1_ObjectsByOwnerListView
		);
	}

	/**
	 * @test
	 */
	public function throwsExceptionForInvalidViewType() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'The given list view type "foo" is invalid.'
		);

		tx_realty_pi1_ListViewFactory::make('foo', array(), $this->cObjMock);
	}
}