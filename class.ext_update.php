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

/**
 * This class offers functions to update the database from one version to
 * another and to reorganize the district-city relations.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ext_update {
	/**
	 * Returns the update module content.
	 *
	 * @return string
	 *         the update module content, will be empty if nothing was updated
	 */
	public function main() {
		$result = '';

		try {
			if ($this->needsToUpdateDistricts()) {
				$result = $this->updateDistricts();
			}
			if ($this->needsToUpdateImages()) {
				$result .= $this->updateImages();
			}
			if ($this->needsToUpdateStatus()) {
				$result .= $this->updateStatus();
			}
			if ($this->needsToUpdatePhoneNumbers()) {
				$result .= $this->updatePhoneNumbers();
			}
		} catch (Tx_Oelib_Exception_Database $exception) {
		}

		return $result;
	}

	/**
	 * Returns whether the update module may be accessed.
	 *
	 * @return bool
	 *         TRUE if the update module may be accessed, FALSE otherwise
	 */
	public function access() {
		if (!ExtensionManagementUtility::isLoaded('oelib') || !ExtensionManagementUtility::isLoaded('realty')) {
			return FALSE;
		}
		if (!Tx_Oelib_Db::existsTable('tx_realty_objects')
			|| !Tx_Oelib_Db::existsTable('tx_realty_cities')
			|| !Tx_Oelib_Db::existsTable('tx_realty_districts')
			|| !Tx_Oelib_Db::existsTable('tx_realty_images')
		) {
			return FALSE;
		}

		try {
			$result = $this->needsToUpdateDistricts()
				|| $this->needsToUpdateImages() || $this->needsToUpdateStatus() || $this->needsToUpdatePhoneNumbers();
		} catch (Tx_Oelib_Exception_Database $exception) {
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * Checks whether the district -> city relations need to be updated.
	 *
	 * @return bool TRUE if the relation needs to be updated, FALSE otherwise
	 */
	protected function needsToUpdateDistricts() {
		if (!Tx_Oelib_Db::tableHasColumn('tx_realty_districts', 'city')) {
			return FALSE;
		}

		$districtsWithExactlyOneCity = $this->findDistrictsToAssignCity();

		return !empty($districtsWithExactlyOneCity);
	}

	/**
	 * Updates the district -> city relations.
	 *
	 * @return string output of the update function, will not be empty
	 */
	protected function updateDistricts() {
		$result = '<h2>Updating district-city relations</h2>' . LF .
			'<table summary="districts and cities">' . LF .
			'<thead>' . LF .
			'<tr><th>District</th><th>City</th></tr>' . LF .
			'</thead>' . LF .
			'<tbody>' . LF;

		$cityCache = array();

		foreach ($this->findDistrictsToAssignCity() as $uids) {
			$districtUid = $uids['district'];
			$cityUid = $uids['city'];

			Tx_Oelib_Db::update(
				'tx_realty_districts', 'uid = ' . $districtUid,
				array('city' => $cityUid)
			);

			$district = Tx_Oelib_Db::selectSingle(
				'title', 'tx_realty_districts', 'uid = ' . $districtUid
			);
			if (!isset($cityCache[$cityUid])) {
				$city = Tx_Oelib_Db::selectSingle(
					'title',  'tx_realty_cities', 'uid = ' . $cityUid
				);

				$cityCache[$cityUid] = $city['title'];
			}

			$result .= '<tr><td>' . htmlspecialchars($district['title']) .
				'</td><td>' . htmlspecialchars($cityCache[$cityUid]) .
				'</td></tr>' . LF;
		}


		$result .= '</tbody>' . LF . '</table>';

		return $result;
	}

	/**
	 * Finds all districts that have no city assigned yet, but have have exactly
	 * one city in the objects table.
	 *
	 * @return array[] two-dimensional array, the second dimension having the keys
	 *               "city" and "district" with the corresponding UIDs, will be
	 *               empty if there are no matches
	 */
	protected function findDistrictsToAssignCity() {
		$districtsWithoutCity = Tx_Oelib_Db::selectColumnForMultiple(
			'uid', 'tx_realty_districts',
			'city = 0' . Tx_Oelib_Db::enableFields('tx_realty_districts')
		);
		if (empty($districtsWithoutCity)) {
			return array();
		}

		return Tx_Oelib_Db::selectMultiple(
			'city, district',
			'tx_realty_objects',
			'district IN ('. implode(',', $districtsWithoutCity) . ') AND city > 0' .
				Tx_Oelib_Db::enableFields('tx_realty_objects'),
			'district HAVING COUNT(DISTINCT city) = 1',
			'city'
		);
	}

	/**
	 * Checks whether the image -> object relations need to be updated.
	 *
	 * @return bool TRUE if the relation needs to be updated, FALSE otherwise
	 */
	protected function needsToUpdateImages() {
		$hasBothColumns = Tx_Oelib_Db::tableHasColumn('tx_realty_images', 'realty_object_uid')
			&& Tx_Oelib_Db::tableHasColumn('tx_realty_images', 'object');
		if (!$hasBothColumns) {
			return FALSE;
		}

		return Tx_Oelib_Db::existsRecord(
			'tx_realty_images',
			'realty_object_uid > 0 AND object = 0'
		);
	}

	/**
	 * Updates the image -> object relations.
	 *
	 * @return string output of the update function, will not be empty
	 */
	protected function updateImages() {
		$databaseConnection = Tx_Oelib_Db::getDatabaseConnection();

		$result = '<h2>Updating image-object relations</h2>' . LF;

		$databaseConnection->sql_query(
			'UPDATE tx_realty_images SET object = realty_object_uid WHERE realty_object_uid > 0 AND object = 0'
		);
		$numberOfAffectedRows = $databaseConnection->sql_affected_rows();
		$result .= '<p>Updated ' . $numberOfAffectedRows . ' image records.</p>';

		$databaseConnection->sql_query(
			'UPDATE tx_realty_objects SET images = ' .
				'(SELECT COUNT(*) FROM tx_realty_images WHERE object = tx_realty_objects.uid ' .
				'AND tx_realty_images.deleted = 0 AND tx_realty_images.hidden = 0)'
		);
		$result .= '<p>Updated the image counter caches.</p>';

		return $result;
	}

	/**
	 * Checks whether the status field need to be updated.
	 *
	 * @return bool TRUE if the status needs to be updated, FALSE otherwise
	 */
	protected function needsToUpdateStatus() {
		if (!Tx_Oelib_Db::tableHasColumn('tx_realty_objects', 'rented')
			|| !Tx_Oelib_Db::tableHasColumn('tx_realty_objects', 'status')
		) {
			return FALSE;
		}

		return Tx_Oelib_Db::existsRecord(
			'tx_realty_objects', 'rented = 1 AND status = 0'
		);
	}

	/**
	 * Updates the "status" field (from the "rented" field).
	 *
	 * @return string output of the update function, will not be empty
	 */
	protected function updateStatus() {
		$result = '<h2>Updating the object status</h2>' . LF;

		$databaseConnection = Tx_Oelib_Db::getDatabaseConnection();
		$databaseConnection->sql_query(
			'UPDATE tx_realty_objects SET status = ' .
				tx_realty_Model_RealtyObject::STATUS_RENTED .
				' WHERE rented = 1 AND status = 0'
		);
		$numberOfAffectedRows = $databaseConnection->sql_affected_rows();

		$result .= '<p>Updated ' . $numberOfAffectedRows . ' object records.</p>';

		return $result;
	}

	/**
	 * Checks whether the phone numbers need to be updated.
	 *
	 * @return bool TRUE if the field
	 */
	protected function needsToUpdatePhoneNumbers() {
		$hasColumns = Tx_Oelib_Db::tableHasColumn('tx_realty_objects', 'phone_direct_extension')
			&& Tx_Oelib_Db::tableHasColumn('tx_realty_objects', 'contact_phone');
		if (!$hasColumns) {
			return FALSE;
		}

		$hasDataToUpdate = Tx_Oelib_Db::existsRecord(
			'tx_realty_objects',
			'phone_direct_extension = "" AND contact_phone <> ""'
		);
		return $hasDataToUpdate;
	}

	/**
	 * Copies contact_phone to phone_direct_extension.
	 *
	 * @return string output of the update function, will not be empty
	 */
	protected function updatePhoneNumbers() {
		$databaseConnection = Tx_Oelib_Db::getDatabaseConnection();

		$result = '<h2>Updating the phone numbers</h2>' . LF;

		$databaseConnection->sql_query(
			'UPDATE tx_realty_objects SET phone_direct_extension = contact_phone ' .
				'WHERE phone_direct_extension = "" AND contact_phone <> ""'
		);
		$numberOfAffectedRows = $databaseConnection->sql_affected_rows();

		$result .= '<p>Updated ' . $numberOfAffectedRows . ' object records.</p>';

		return $result;
	}
}