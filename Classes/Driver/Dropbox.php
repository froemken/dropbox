<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan froemken <firma@sfroemken.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once t3lib_extMgm::extPath('fal_dropbox', 'Classes/Dropbox/autoload.php');

/**
 *
 *
 * @package sfstefan
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_FalDropbox_Driver_Dropbox extends \TYPO3\CMS\Core\Resource\Driver\AbstractDriver {

	/**
	 * The mount object this driver instance belongs to
	 *
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * @var Dropbox_API
	 */
	protected $dropbox;

	/**
	 * @var Dropbox_OAuth_PHP
	 */
	protected $oauth;

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 */
	protected $registry;

	/**
	 * A list of all supported hash algorithms, written all lower case and
	 * without any dashes etc. (e.g. sha1 instead of SHA-1)
	 * Be sure to set this in inherited classes!
	 *
	 * @var array
	 */
	protected $supportedHashAlgorithms = array();

	/**
	 * The storage folder that forms the root of this FS tree
	 *
	 * @var \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $rootLevelFolder;

	/**
	 * The default folder new files should be put into.
	 *
	 * @var \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $defaultLevelFolder;

	/**
	 * The configuration of this driver
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * The callback method to handle the files when listing folder contents
	 *
	 * @var string
	 */
	protected $fileListCallbackMethod = 'getFileList_itemCallback';

	/**
	 * The callback method to handle the folders when listing folder contents
	 *
	 * @var string
	 */
	protected $folderListCallbackMethod = 'getFolderList_itemCallback';

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize() {
		// $this->determineBaseUrl();
		// The capabilities of this driver. See CAPABILITY_* constants for possible values
		$this->capabilities = \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_BROWSABLE | \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC | \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_WRITABLE;

		$this->registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$settings = $this->registry->get('fal_dropbox', 'config');

		$this->oauth = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Dropbox_OAuth_PEAR', $settings['appKey'], $settings['appSecret']);
		$this->oauth->setToken($settings['accessToken']);
		$this->dropbox = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Dropbox_API', $this->oauth, 'sandbox');
	}

	/**
	 * Checks if a configuration is valid for this driver.
	 * Throws an exception if a configuration will not work.
	 *
	 * @abstract
	 * @param array $configuration
	 * @return void
	 */
	static public function verifyConfiguration(array $configuration) {
	}

	/**
	 * processes the configuration, should be overridden by subclasses
	 *
	 * @return void
	 */
	public function processConfiguration() {
	}

	/**
	 * Generic handler method for directory listings - gluing together the
	 * listing items is done
	 *
	 * @param string $path
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param array $filterMethods The filter methods used to filter the directory items
	 * @param string $itemHandlerMethod
	 * @param array $itemRows
	 * @return array
	 */
	protected function getDirectoryItemList($path, $start, $numberOfItems, array $filterMethods, $itemHandlerMethod, $itemRows = array()) {
		$folders = array();
		$files = array();
		$info = $this->dropbox->getMetaData($path);
		foreach($info['contents'] as $entry) {
			if($entry['is_dir']) {
				$folder['ctime'] = time();
				$folder['mtime'] = time();
				$folder['name'] = trim($entry['path'], '/');
				$folder['identifier'] = $entry['path'] . '/';
				$folder['storage'] = $this->storage->getUid();

				$folders[] = $folder;
			} else {
				$file['ctime'] = time();
				$file['mtime'] = time();
				$file['name'] = trim($entry['path'], '/');
				$file['identifier'] = $entry['path'];
				$file['storage'] = $this->storage->getUid();

				$files[] = $file;
			}
		}

		if($itemHandlerMethod == 'getFileList_itemCallback') {
			return $files;
		}
		if($itemHandlerMethod == 'getFolderList_itemCallback') {
			return $folders;
		}
		return array();
	}

	/*******************
	 * CAPABILITIES
	 *******************/
	/**
	 * The capabilities of this driver. See Storage::CAPABILITY_* constants for possible values. This value should be set
	 * in the constructor of derived classes.
	 *
	 * @var integer
	 */
	protected $capabilities = 0;

	/**
	 * Returns the capabilities of this driver.
	 *
	 * @return integer
	 * @see Storage::CAPABILITY_* constants
	 */
	public function getCapabilities() {
		return $this->capabilities;
	}

	/**
	 * Returns TRUE if this driver has the given capability.
	 *
	 * @param int $capability A capability, as defined in a CAPABILITY_* constant
	 * @return boolean
	 */
	public function hasCapability($capability) {
		return $this->capabilities & $capability == $capability;
	}

	/*******************
	 * FILE FUNCTIONS
	 *******************/
	/**
	 * Returns the public URL to a file.
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resource
	 * @param bool  $relativeToCurrentScript    Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl(\TYPO3\CMS\Core\Resource\ResourceInterface $resource, $relativeToCurrentScript = FALSE) {
		if ($this->storage->isPublic()) {
			// as the storage is marked as public, we can simply use the public URL here.
			if (is_object($resource)) {
				return $this->baseUrl . ltrim($resource->getIdentifier(), '/');
			} else {
				return $this->baseUrl . ltrim($resource, '/');
			}
		}
	}

	/**
	 * Creates a (cryptographic) hash for a file.
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 */
	public function hash(\TYPO3\CMS\Core\Resource\FileInterface $file, $hashAlgorithm) {
		// hash
	}

	/**
	 * Creates a new file and returns the matching file object for it.
	 *
	 * @abstract
	 * @param string $fileName
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	public function createFile($fileName, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		/*$fileIdentifier = $parentFolder->getIdentifier() . $fileName;
		$fileUrl = $this->baseUrl . ltrim($fileIdentifier, '/');

		$this->dropbox->putFile($parentFolder->getIdentifier(), $file);

		$this->removeCacheForPath($parentFolder->getIdentifier());

		return $this->getFile($fileIdentifier);*/
	}

	/**
	 * Returns the contents of a file. Beware that this requires to load the
	 * complete file into memory and also may require fetching the file from an
	 * external location. So this might be an expensive operation (both in terms
	 * of processing resources and money) for large files.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return string The file contents
	 */
	public function getFileContents(\TYPO3\CMS\Core\Resource\FileInterface $file) {

	}

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $contents
	 * @return integer The number of bytes written to the file
	 * @throws RuntimeException if the operation failed
	 */
	public function setFileContents(\TYPO3\CMS\Core\Resource\FileInterface $file, $contents) {

	}

	/**
	 * Adds a file from the local server hard disk to a given path in TYPO3s virtual file system.
	 *
	 * This assumes that the local file exists, so no further check is done here!
	 *
	 * @param string $localFilePath
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName The name to add the file under
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $updateFileObject Optional file object to update (instead of creating a new object). With this parameter, this function can be used to "populate" a dummy file object with a real file underneath.
	 * @return \TYPO3\CMS\Core\Resource\FileInterface
	 */
	public function addFile($localFilePath, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName, \TYPO3\CMS\Core\Resource\AbstractFile $updateFileObject = NULL) {
		$fileIdentifier = $targetFolder->getIdentifier() . $fileName;

		$this->dropbox->putFile($fileIdentifier, $localFilePath);

		//$this->removeCacheForPath($targetFolder->getIdentifier());

		return $this->getFile($fileIdentifier);
	}

	/**
	 * Checks if a resource exists - does not care for the type (file or folder).
	 *
	 * @param $identifier
	 * @return boolean
	 */
	public function resourceExists($identifier) {
		if ($identifier == '') {
			throw new \InvalidArgumentException('Resource path cannot be empty');
		}
		try{
			$info = $this->dropbox->getMetaData($identifier);
			if($info['is_deleted']) return false;
		} catch(Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if a file exists.
	 *
	 * @abstract
	 * @param string $identifier
	 * @return boolean
	 */
	public function fileExists($identifier) {
		try {
			$file = $this->dropbox->getMetaData($identifier, false);
			if($file['is_dir']) {
				return false;
			}
		} catch(Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if a file inside a storage folder exists.
	 *
	 * @abstract
	 * @param string $fileName
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean
	 */
	public function fileExistsInFolder($fileName, \TYPO3\CMS\Core\Resource\Folder $folder) {
		$fileIdentifier = $folder->getIdentifier() . $fileName;

		return $this->fileExists($fileIdentifier);
	}

	/**
	 * Returns a (local copy of) a file for processing it. When changing the
	 * file, you have to take care of replacing the current version yourself!
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param bool $writable Set this to FALSE if you only need the file for read operations. This might speed up things, e.g. by using a cached local version. Never modify the file if you have set this flag!
	 * @return string The path to the file on the local disk
	 */
	// TODO decide if this should return a file handle object
	public function getFileForLocalProcessing(\TYPO3\CMS\Core\Resource\FileInterface $file, $writable = TRUE) {

	}

	/**
	 * Returns the permissions of a file as an array (keys r, w) of boolean flags
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return array
	 */
	public function getFilePermissions(\TYPO3\CMS\Core\Resource\FileInterface $file) {

	}

	/**
	 * Returns the permissions of a folder as an array (keys r, w) of boolean flags
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return array
	 */
	public function getFolderPermissions(\TYPO3\CMS\Core\Resource\Folder $folder) {
		return array('r' => TRUE, 'w' => TRUE);
	}

	/**
	 * Renames a file
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $newName
	 * @return string The new identifier of the file if the operation succeeds
	 * @throws RuntimeException if renaming the file failed
	 */
	public function renameFile(\TYPO3\CMS\Core\Resource\FileInterface $file, $newName) {

	}

	/**
	 * Replaces the contents (and file-specific metadata) of a file object with a local file.
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $file
	 * @param string $localFilePath
	 * @return boolean
	 */
	public function replaceFile(\TYPO3\CMS\Core\Resource\AbstractFile $file, $localFilePath) {

	}

	/**
	 * Returns information about a file for a given file identifier.
	 *
	 * @param string $identifier The (relative) path to the file.
	 * @return array
	 */
	public function getFileInfoByIdentifier($identifier) {
		$info = $this->dropbox->getMetaData($identifier, false);
		$fileInfo = array(
			'mtime' => time(),
			'ctime' => time(),
			'mimetype' => $info['mime_type'],
			'name' => basename($identifier),
			'size' => $info['bytes'],
			'identifier' => $identifier,
			'storage' => $this->storage->getUid()
		);

		return $fileInfo;
	}

	/**
	 * Returns information about a file for a given file object.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return array
	 */
	public function getFileInfo(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		return $this->getFileInfoByIdentifier($file->getIdentifier());
	}

	/**
	 * Returns a folder within the given folder. Use this method instead of doing your own string manipulation magic
	 * on the identifiers because non-hierarchical storages might fail otherwise.
	 *
	 * @param $name
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getFolderInFolder($name, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		$folderIdentifier = $parentFolder->getIdentifier() . $name . '/';
		return $this->getFolder($folderIdentifier);
	}

	/**
	 * Copies a file to a temporary path and returns that path.
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return string The temporary path
	 */
	public function copyFileToTemporaryPath(\TYPO3\CMS\Core\Resource\FileInterface $file) {

	}

	/**
	 * Moves a file *within* the current storage.
	 * Note that this is only about an intra-storage move action, where a file is just
	 * moved to another folder in the same storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName
	 * @return string The new identifier of the file
	 */
	public function moveFileWithinStorage(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName) {

	}

	/**
	 * Copies a file *within* the current storage.
	 * Note that this is only about an intra-storage copy action, where a file is just
	 * copied to another folder in the same storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName
	 * @return \TYPO3\CMS\Core\Resource\FileInterface The new (copied) file object.
	 */
	public function copyFileWithinStorage(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName) {

	}

	/**
	 * Folder equivalent to moveFileWithinStorage().
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderToMove
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $newFolderName
	 * @return array A map of old to new file identifiers
	 */
	public function moveFolderWithinStorage(\TYPO3\CMS\Core\Resource\Folder $folderToMove, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $newFolderName) {

	}

	/**
	 * Folder equivalent to copyFileWithinStorage().
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderToMove
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $newFileName
	 * @return boolean
	 */
	public function copyFolderWithinStorage(\TYPO3\CMS\Core\Resource\Folder $folderToMove, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $newFileName) {

	}

	/**
	 * Removes a file from this storage. This does not check if the file is
	 * still used or if it is a bad idea to delete it for some other reason
	 * this has to be taken care of in the upper layers (e.g. the Storage)!
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return boolean TRUE if deleting the file succeeded
	 */
	public function deleteFile(\TYPO3\CMS\Core\Resource\FileInterface $file) {

	}

	/**
	 * Removes a folder from this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param boolean $deleteRecursively
	 * @return boolean
	 */
	public function deleteFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $deleteRecursively = FALSE) {

	}

	/**
	 * Adds a file at the specified location. This should only be used internally.
	 *
	 * @abstract
	 * @param string $localFilePath
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $targetFileName
	 * @return string The new identifier of the file
	 */
	// TODO check if this is still necessary if we move more logic to the storage
	public function addFileRaw($localFilePath, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $targetFileName) {

	}

	/**
	 * Deletes a file without access and usage checks.
	 * This should only be used internally.
	 *
	 * This accepts an identifier instead of an object because we might want to
	 * delete files that have no object associated with (or we don't want to
	 * create an object for) them - e.g. when moving a file to another storage.
	 *
	 * @abstract
	 * @param string $identifier
	 * @return boolean TRUE if removing the file succeeded
	 */
	public function deleteFileRaw($identifier) {

	}

	/*******************
	 * FOLDER FUNCTIONS
	 *******************/
	/**
	 * Returns the root level folder of the storage.
	 *
	 * @abstract
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getRootLevelFolder() {
		return $this->getFolder('/');
	}

	/**
	 * Returns the default folder new files should be put into.
	 *
	 * @abstract
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getDefaultFolder() {

	}

	/**
	 * Creates a folder.
	 *
	 * @param string $newFolderName
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\Folder The new (created) folder object
	 */
	public function createFolder($newFolderName, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		$this->dropbox->createFolder($newFolderName);
	}

	/**
	 * Checks if a folder exists
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function folderExists($identifier) {
		try {
			$info = $this->dropbox->getMetaData($identifier);
			if($info['is_dir']) {
				return true;
			} else return false;
		} catch(Exception $e) {
			return false;
		}
	}

	/**
	 * Checks if a file inside a storage folder exists.
	 *
	 * @abstract
	 * @param string $folderName
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean
	 */
	public function folderExistsInFolder($folderName, \TYPO3\CMS\Core\Resource\Folder $folder) {
		$folderIdentifier = $folder->getIdentifier() . $folderName . '/';
		return $this->resourceExists($folderIdentifier);
	}

	/**
	 * Renames a folder in this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param string $newName The target path (including the file name!)
	 * @return array A map of old to new file identifiers
	 * @throws RuntimeException if renaming the folder failed
	 */
	public function renameFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $newName) {

	}

	/**
	 * Checks if a given object or identifier is within a container, e.g. if
	 * a file or folder is within another folder.
	 * This can e.g. be used to check for webmounts.
	 *
	 * @abstract
	 * @param \TYPO3\CMS\Core\Resource\Folder $container
	 * @param mixed $content An object or an identifier to check
	 * @return boolean TRUE if $content is within $container
	 */
	public function isWithin(\TYPO3\CMS\Core\Resource\Folder $container, $content) {

	}

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty(\TYPO3\CMS\Core\Resource\Folder $folder) {

	}

	/**
	 * Flushes the cache for a given path inside this storage.
	 *
	 * @param $path
	 * @return void
	 */
	protected function removeCacheForPath($path) {
		$this->directoryListingCache->remove($this->getCacheIdentifierForPath($path));
	}
}
?>