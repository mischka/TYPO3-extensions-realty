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

/**
 * This class represents a front-end user and adds functions to check the number
 * of objects a user has or can enter.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_FrontEndUser extends Tx_Oelib_Model_FrontEndUser {
	/**
	 * @var int the number of objects belonging to the current user
	 */
	private $numberOfObjects = 0;

	/**
	 * @var bool whether the number of objects has already been calculated
	 */
	private $numberOfObjectsHasBeenCalculated = FALSE;

	/**
	 * Returns the maximum number of objects the user is allowed to enter.
	 *
	 * @return int the maximum number of objects the user is allowed to
	 *                 enter, will be >= 0
	 */
	public function getTotalNumberOfAllowedObjects() {
		return $this->getAsInteger('tx_realty_maximum_objects');
	}

	/**
	 * Returns the number of objects the user owns, including the hidden
	 * ones.
	 *
	 * @return int the number of objects belonging to this user, will be zero
	 *                 if the user has no objects
	 */
	public function getNumberOfObjects() {
		if (!$this->numberOfObjectsHasBeenCalculated) {
			$whereClause = 'tx_realty_objects' . '.owner=' . $this->getUid() .
				Tx_Oelib_Db::enableFields('tx_realty_objects', 1);

			$this->numberOfObjects = Tx_Oelib_Db::count(
				'tx_realty_objects',
				$whereClause
			);
			$this->numberOfObjectsHasBeenCalculated = TRUE;
		}

		return $this->numberOfObjects;
	}

	/**
	 * Returns the number of objects a user still can enter, depending on the
	 * maximum number set and the number of objects a user already has stored in
	 * the DB.
	 *
	 * @return int the number of objects a user can enter, will be >= 0
	 */
	public function getObjectsLeftToEnter() {
		$numberOfAllowedObjects = $this->getTotalNumberOfAllowedObjects();
		if ($numberOfAllowedObjects == 0) {
			return 0;
		}

		return max(
			($numberOfAllowedObjects - $this->getNumberOfObjects()),
			0
		);
	}

	/**
	 * Checks whether the user is allowed to enter any objects.
	 *
	 * @return bool TRUE if the user is allowed to enter objects, FALSE
	 *                 otherwise
	 */
	public function canAddNewObjects() {
		return (($this->getTotalNumberOfAllowedObjects() == 0)
			|| ($this->getObjectsLeftToEnter() > 0));
	}

	/**
	 * Forces the function getNumberOfObjects to recalculate the number of objects.
	 *
	 * @return void
	 */
	public function resetObjectsHaveBeenCalculated() {
		$this->numberOfObjectsHasBeenCalculated = FALSE;
	}

	/**
	 * Gets this user's OpenImmo offerer ID.
	 *
	 * @return string
	 *         the user's OpenImmo offerer ID, will be empty if non has been set
	 */
	public function getOpenImmoOffererId() {
		return $this->getAsString('tx_realty_openimmo_anid');
	}

	/**
	 * Checks whether this user has a non-empty OpenImmo offerer ID.
	 *
	 * @return bool
	 *         TRUE if this user has a non-empty OpenImmo offerer ID, FALSE
	 *         otherwise
	 */
	public function hasOpenImmoOffererId() {
		return $this->hasString('tx_realty_openimmo_anid');
	}
}