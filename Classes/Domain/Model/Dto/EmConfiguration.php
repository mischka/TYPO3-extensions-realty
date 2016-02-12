<?php
namespace OliverKlee\Realty\Domain\Model\Dto;

/**
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
 * Extension Manager configuration
 *
 * @package TYPO3
 * @subpackage tx_realty
 */
class EmConfiguration
{

    /**
     * Fill the properties properly
     *
     * @param array $configuration em configuration
     */
    public function __construct(array $configuration)
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }

        if ($storagePid = $this->getPidForRealtyObjectsAndImages()) {
            $this
                ->setObjectsPid($storagePid)
                ->setImagesPid($storagePid)
            ;
        }

        if ($storagePid = $this->getPidForAuxiliaryRecords()) {
            $this
                ->setApartmentTypesPid($storagePid)
                ->setCarPlacesPid($storagePid)
                ->setCitiesPid($storagePid)
                ->setDistrictsPid($storagePid)
                ->setDocumentsPid($storagePid)
                ->setHouseTypesPid($storagePid)
                ->setPetsPid($storagePid)
            ;
        }

    }

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $petsPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $objectsPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $imagesPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $citiesPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $houseTypesPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $apartmentTypesPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $carPlacesPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $districtsPid = 0;

    /**
     * Storage Uid
     *
     * @var integer
     */
    protected $documentsPid = 0;

    /**
     * Configuration check: Enables the configuration check in the front-end.
     *
     * @var boolean
     */
    protected $enableConfigCheck = true;

    /**
     * Import directory: Absolute path of the directory that contains the OpenImmo ZIP files to import.
     *
     * @var string
     */
    protected $importFolder;

    /**
     * Delete imported ZIPs:If this option is set, ZIP archives will be deleted from the import folder after their contents have been written to the database.
     *
     * @var boolean
     */
    protected $deleteZipsAfterImport = true;

    /**
     * Only import for registered FE users:If this is checked, only the records with an OpenImmoAnid ANID that matches a FE user will be imported. Non-imported records will be mentioned in the log.
     *
     * @var boolean
     */
    protected $onlyImportForRegisteredFrontEndUsers = false;

    /**
     * Restrict import to FE user groups:Comma-separated list of FE user group UIDs. Only realty objects of members of these user groups will be imported. Leave empty to set no restriction on FE user groups. This option will be ignored if the option above is not checked.
     *
     * @var string
     */
    protected $allowedFrontEndUserGroups = '';

    /**
     * PID for realty records and images:Page ID of the system folder where new realty and image records will be stored.
     *
     * @var integer
     */
    protected $pidForRealtyObjectsAndImages = null;

    /**
     * PID for auxiliary records:Page ID of the system folder where auxiliary records (like cities or house types) will be stored. Leave this field empty to store these records in the same system folder as realty records and images.
     * @var integer
     */
    protected $pidForAuxiliaryRecords = null;

    /**
     * PIDs for realty records by filename: This will sort imported realty records and images into system folders depending on the file name of the ZIP. This field is optional. Have a look in the manual for details on how to use this.
     *
     * @var string
     */
    protected $pidsForRealtyObjectsAndImagesByFileName;

    /**
     * Use FE user data as contact data: If checked, the data of the FE user who has the same OpenImmo ANID as found in the imported record is used. This means, the notification e-mail (if this option is enabled) will be sent to this user and if it is enabled to show contact data in the FE, it is also this user's data that will be used. No matter which data is used, the imported FE user data will be stored within the record.
     *
     * @var string
     */
    protected $useFrontEndUserDataAsContactDataForImportedRecords = false;

    /**
     * Recipient of the import logs:This e-mail address will receive the complete import log if "Notify contact persons" is disabled. If "Notify contact persons" is enabled, this address will receive only the log parts about OpenImmo files that did not contain a valid contact e-mail address. Leave this field empty to completely disable e-mailing the import logs.
     *
     * @var boolean
     */
    protected $emailAddress = '';

    /**
     * E-mail the log only on errors:Check this to only have the import send out e-mails if an errors has occurred. This will suppress the e-mailing the import log if everything has gone well.
     *
     * @var boolean
     */
    protected $onlyErrors = false;

    /**
     * Notify contact persons:If this is checked, the contact e-mail addresses listed in the OpenImmo files will receive the import log of their objects.
     *
     * @var boolean
     */
    protected $notifyContactPersons = false;

    /**
     * XML Schema file for validation:Absolute path of the XML Schema file (*.xsd) that will be used for validating the OpenImmo files during the import.
     *
     * @var string
     */
    protected $openImmoSchema = '';

    /**
     * Language of the import messages:This will determine the language that will be used in status output and e-mails during the import. Use the ISO 639-1 code, for example "en" or "de".
     *
     * @var string
     */
    protected $cliLanguage = 'en';

    /**
     * E-mail text template: Path of the text template file for the e-mail layout.
     *
     * @var string
     */
    protected $emailTemplate = 'EXT:realty/lib/tx_realty_emailNotification.tmpl';

    /**
     * @return int
     */
    public function getPetsPid()
    {
        return $this->petsPid;
    }

    /**
     * @param int $petsPid
     * @return $this
     */
    public function setPetsPid($petsPid)
    {
        $this->petsPid = $petsPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getObjectsPid()
    {
        return $this->objectsPid;
    }

    /**
     * @param int $objectsPid
     * @return $this
     */
    public function setObjectsPid($objectsPid)
    {
        $this->objectsPid = $objectsPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getImagesPid()
    {
        return $this->imagesPid;
    }

    /**
     * @param int $imagesPid
     * @return $this
     */
    public function setImagesPid($imagesPid)
    {
        $this->imagesPid = $imagesPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getCitiesPid()
    {
        return $this->citiesPid;
    }

    /**
     * @param int $citiesPid
     * @return $this
     */
    public function setCitiesPid($citiesPid)
    {
        $this->citiesPid = $citiesPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getHouseTypesPid()
    {
        return $this->houseTypesPid;
    }

    /**
     * @param int $houseTypesPid
     * @return $this
     */
    public function setHouseTypesPid($houseTypesPid)
    {
        $this->houseTypesPid = $houseTypesPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getApartmentTypesPid()
    {
        return $this->apartmentTypesPid;
    }

    /**
     * @param int $apartmentTypesPid
     * @return $this
     */
    public function setApartmentTypesPid($apartmentTypesPid)
    {
        $this->apartmentTypesPid = $apartmentTypesPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getCarPlacesPid()
    {
        return $this->carPlacesPid;
    }

    /**
     * @param int $carPlacesPid
     * @return $this
     */
    public function setCarPlacesPid($carPlacesPid)
    {
        $this->carPlacesPid = $carPlacesPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getDistrictsPid()
    {
        return $this->districtsPid;
    }

    /**
     * @param int $districtsPid
     * @return $this
     */
    public function setDistrictsPid($districtsPid)
    {
        $this->districtsPid = $districtsPid;
        return $this;
    }

    /**
     * @return int
     */
    public function getDocumentsPid()
    {
        return $this->documentsPid;
    }

    /**
     * @param int $documentsPid
     * @return $this
     */
    public function setDocumentsPid($documentsPid)
    {
        $this->documentsPid = $documentsPid;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnableConfigCheck()
    {
        return $this->enableConfigCheck;
    }

    /**
     * @param boolean $enableConfigCheck
     * @return $this
     */
    public function setEnableConfigCheck($enableConfigCheck)
    {
        $this->enableConfigCheck = $enableConfigCheck;
        return $this;
    }

    /**
     * @return string
     */
    public function getImportFolder()
    {
        return $this->importFolder;
    }

    /**
     * @param string $importFolder
     * @return $this
     */
    public function setImportFolder($importFolder)
    {
        $this->importFolder = $importFolder;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeleteZipsAfterImport()
    {
        return $this->deleteZipsAfterImport;
    }

    /**
     * @param boolean $deleteZipsAfterImport
     * @return $this
     */
    public function setDeleteZipsAfterImport($deleteZipsAfterImport)
    {
        $this->deleteZipsAfterImport = $deleteZipsAfterImport;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOnlyImportForRegisteredFrontEndUsers()
    {
        return $this->onlyImportForRegisteredFrontEndUsers;
    }

    /**
     * @param boolean $onlyImportForRegisteredFrontEndUsers
     * @return $this
     */
    public function setOnlyImportForRegisteredFrontEndUsers($onlyImportForRegisteredFrontEndUsers)
    {
        $this->onlyImportForRegisteredFrontEndUsers = $onlyImportForRegisteredFrontEndUsers;
        return $this;
    }

    /**
     * @return string
     */
    public function getAllowedFrontEndUserGroups()
    {
        return $this->allowedFrontEndUserGroups;
    }

    /**
     * @param string $allowedFrontEndUserGroups
     * @return $this
     */
    public function setAllowedFrontEndUserGroups($allowedFrontEndUserGroups)
    {
        $this->allowedFrontEndUserGroups = $allowedFrontEndUserGroups;
        return $this;
    }

    /**
     * @return int
     */
    public function getPidForRealtyObjectsAndImages()
    {
        return $this->pidForRealtyObjectsAndImages;
    }

    /**
     * @param int $pidForRealtyObjectsAndImages
     * @return $this
     */
    public function setPidForRealtyObjectsAndImages($pidForRealtyObjectsAndImages)
    {
        $this->pidForRealtyObjectsAndImages = $pidForRealtyObjectsAndImages;
        return $this;
    }

    /**
     * @return int
     */
    public function getPidForAuxiliaryRecords()
    {
        return $this->pidForAuxiliaryRecords;
    }

    /**
     * @param int $pidForAuxiliaryRecords
     * @return $this
     */
    public function setPidForAuxiliaryRecords($pidForAuxiliaryRecords)
    {
        $this->pidForAuxiliaryRecords = $pidForAuxiliaryRecords;
        return $this;
    }

    /**
     * @return string
     */
    public function getPidsForRealtyObjectsAndImagesByFileName()
    {
        return $this->pidsForRealtyObjectsAndImagesByFileName;
    }

    /**
     * @param string $pidsForRealtyObjectsAndImagesByFileName
     * @return $this
     */
    public function setPidsForRealtyObjectsAndImagesByFileName($pidsForRealtyObjectsAndImagesByFileName)
    {
        $this->pidsForRealtyObjectsAndImagesByFileName = $pidsForRealtyObjectsAndImagesByFileName;
        return $this;
    }

    /**
     * @return string
     */
    public function getUseFrontEndUserDataAsContactDataForImportedRecords()
    {
        return $this->useFrontEndUserDataAsContactDataForImportedRecords;
    }

    /**
     * @param string $useFrontEndUserDataAsContactDataForImportedRecords
     * @return $this
     */
    public function setUseFrontEndUserDataAsContactDataForImportedRecords(
        $useFrontEndUserDataAsContactDataForImportedRecords
    ) {
        $this->useFrontEndUserDataAsContactDataForImportedRecords = $useFrontEndUserDataAsContactDataForImportedRecords;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param boolean $emailAddress
     * @return $this
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOnlyErrors()
    {
        return $this->onlyErrors;
    }

    /**
     * @param boolean $onlyErrors
     * @return $this
     */
    public function setOnlyErrors($onlyErrors)
    {
        $this->onlyErrors = $onlyErrors;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isNotifyContactPersons()
    {
        return $this->notifyContactPersons;
    }

    /**
     * @param boolean $notifyContactPersons
     * @return $this
     */
    public function setNotifyContactPersons($notifyContactPersons)
    {
        $this->notifyContactPersons = $notifyContactPersons;
        return $this;
    }

    /**
     * @return string
     */
    public function getOpenImmoSchema()
    {
        return $this->openImmoSchema;
    }

    /**
     * @param string $openImmoSchema
     * @return $this
     */
    public function setOpenImmoSchema($openImmoSchema)
    {
        $this->openImmoSchema = $openImmoSchema;
        return $this;
    }

    /**
     * @return string
     */
    public function getCliLanguage()
    {
        return $this->cliLanguage;
    }

    /**
     * @param string $cliLanguage
     * @return $this
     */
    public function setCliLanguage($cliLanguage)
    {
        $this->cliLanguage = $cliLanguage;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmailTemplate()
    {
        return $this->emailTemplate;
    }

    /**
     * @param string $emailTemplate
     * @return $this
     */
    public function setEmailTemplate($emailTemplate)
    {
        $this->emailTemplate = $emailTemplate;
        return $this;
    }

}
