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
 * @author Stefan Froemken <firma@sfroemken.de>
 * @package fal_dropbox
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_FalDropbox_Driver_Dropbox extends \TYPO3\CMS\Core\Resource\Driver\AbstractDriver {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 */
	protected $registry;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend
	 */
	protected $cache;

	/**
	 * @var Dropbox_OAuth_PHP
	 */
	protected $oAuth;

	/**
	 * @var Dropbox_API
	 */
	protected $dropbox;

	protected $settings = array();





	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->capabilities = \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_BROWSABLE
			+ \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_PUBLIC
			+ \TYPO3\CMS\Core\Resource\ResourceStorage::CAPABILITY_WRITABLE;
		$this->cache = $GLOBALS['typo3CacheManager']->getCache('tx_faldropbox_cache');
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->registry = $this->objectManager->get('TYPO3\\CMS\\Core\\Registry');
		$this->settings = $this->registry->get('fal_dropbox', 'settings');
		$this->oAuth = $this->objectManager->get('Dropbox_OAuth_PEAR', $this->settings['appKey'], $this->settings['appSecret']);
		$this->oAuth->setToken($this->settings['accessToken']);
		$this->dropbox = $this->objectManager->get('Dropbox_API', $this->oAuth, 'sandbox');
	}

	/**
	 * Checks if a configuration is valid for this driver.
	 * Throws an exception if a configuration will not work.
	 *
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
	 * get file or folder informations from cache or directly from dropbox
	 *
	 * @param string $path Path to receive information from
	 * @param bool $list When set to true, this method returns information from all files in a directory. When set to false it will only return infromation from the specified directory.
	 * @param string $hash If a hash is supplied, this method simply returns true if nothing has changed since the last request. Good for caching.
	 * @param int $fileLimit Maximum number of file-information to receive
	 * @param string $root Use this to override the default root path (sandbox/dropbox)
	 * @return array file or folder informations
	 */
	public function getMetaData($path, $list = true, $hash = null, $fileLimit = null, $root = null) {
		$cacheKey = $this->getCacheIdentifierForPath($path);
		$info = $this->cache->get($cacheKey);
		if (empty($info)) {
			try{
				$info = $this->dropbox->getMetaData($path, $list, $hash, $fileLimit, $root);
			} catch(Exception $e) {
				$info = array();
			}
			$this->cache->set($cacheKey, $info);
		}
		return $info;
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
		$info = $this->getMetaData($path);
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

	/**
	 * Returns the cache identifier for a given path.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getCacheIdentifierForPath($path) {
		return sha1($this->storage->getUid() . ':' . trim($path, '/') . '/');
	}

	/**
	 * Flushes the cache for a given path inside this storage.
	 *
	 * @param $path
	 * @return void
	 */
	protected function removeCacheForPath($path) {
		$this->cache->remove($this->getCacheIdentifierForPath($path));
	}

	/*******************
	 * FILE FUNCTIONS
	 *******************/
	/**
	 * Returns the public URL to a file.
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function getPublicUrl($identifier) {
		if ($this->storage->isPublic()) {
			// as the storage is marked as public, we can simply use the public URL here.

			if(TYPO3_MODE == 'BE') {
				$result = $this->dropbox->media($identifier);
				return $result['url'];
			} else {
				$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
				$file = $factory->retrieveFileOrFolderObject($this->storage->getUid() . ':' . $identifier);

				$id = sha1($file->getStorage()->getUid() . ':' . $file->getIdentifier());
				$uploadPath = 'uploads/pics/fal-dropbox-' . $id . '.' . $file->getExtension();
				file_put_contents(PATH_site . $uploadPath, $this->dropbox->getFile($identifier));

				return $uploadPath;
			}
		}
	}

	/**
	 * Creates a hash for a file.
	 *
	 * @param string $fileIdentifier
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 */
	public function hash($fileIdentifier, $hashAlgorithm) {
		switch ($hashAlgorithm) {
			case 'sha1':
				return sha1($fileIdentifier);
				break;
		}
	}

	/**
	 * Creates a new (empty) file and returns the identifier.
	 *
	 * @param string $fileName
	 * @param string $parentFolderIdentifier
	 * @return string
	 */
	public function createFile($fileName, $parentFolderIdentifier) {
		// get full target path incl. filename
		$fileIdentifier = $parentFolderIdentifier . $fileName;

		// dropbox cannot create (touch) files. So we have to do this here.
		$emptyTempFilePath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('empty');
		$this->dropbox->putFile($fileIdentifier, $emptyTempFilePath);

		// delete cache entries for current folder
		$this->removeCacheForPath($parentFolderIdentifier);
		// delete cache entries for new file if exists
		$this->removeCacheForPath($fileIdentifier);

		unlink($emptyTempFilePath);

		return $this->getFile($fileIdentifier);
	}

	/**
	 * Returns the contents of a file. Beware that this requires to load the
	 * complete file into memory and also may require fetching the file from an
	 * external location. So this might be an expensive operation (both in terms
	 * of processing resources and money) for large files.
	 *
	 * @param string $fileIdentifier
	 * @return string The file contents
	 */
	public function getFileContents($fileIdentifier) {
		return $this->dropbox->getFile($fileIdentifier);
	}

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param string $fileIdentifier
	 * @param string $contents
	 * @return integer The number of bytes written to the file
	 */
	public function setFileContents($fileIdentifier, $contents) {
		$tempPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('dropboxPutFile');
		file_put_contents($tempPath, $contents);
		$this->dropbox->putFile($fileIdentifier, $tempPath);
		unlink($tempPath);

		// remove cache for folder
		$this->removeCacheForPath(dirname($fileIdentifier));
		// the file was overwritten, so we have to remove the cache entry for the modified file too
		$this->removeCacheForPath($fileIdentifier);
	}

	/**
	 * Adds a file from the local server hard disk to a given path in TYPO3s
	 * virtual file system. This assumes that the local file exists, so no
	 * further check is done here! After a successful the original file must
	 * not exist anymore.
	 *
	 * @param string $localFilePath (within PATH_site)
	 * @param string $targetFolderIdentifier
	 * @param string $newFileName optional, if not given original name is used
	 * @param boolean $removeOriginal if set the original file will be removed
	 *                                after successful operation
	 * @return string the identifier of the new file
	 */
	public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = TRUE) {
		$fileIdentifier = $targetFolderIdentifier . $newFileName;

		$this->dropbox->putFile($fileIdentifier, $localFilePath);
		unlink($localFilePath);

		// remove cache for folder
		$this->removeCacheForPath($targetFolderIdentifier);
		// maybe the file was overwritten, so it's better to remove the files cache too
		$this->removeCacheForPath($fileIdentifier);

		return $this->getFile($fileIdentifier);
	}

	/**
	 * Checks if a resource exists - does not care for the type (file or folder).
	 *
	 * @param $identifier
	 * @return boolean
	 */
	public function resourceExists($identifier) {
		if (empty($identifier)) {
			throw new \InvalidArgumentException('Resource path cannot be empty');
		}
		$info = $this->getMetaData($identifier);
		if ($info['is_deleted']) {
			return false;
		} else return true;
	}

	/**
	 * Checks if a file exists.
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function fileExists($identifier) {
		$info = $this->getMetaData($identifier, false);
		if($info['is_dir']) {
			return false;
		}
		if($info['is_deleted']) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if a file inside a folder exists
	 *
	 * @param string $fileName
	 * @param string $folderIdentifier
	 * @return boolean
	 */
	public function fileExistsInFolder($fileName, $folderIdentifier)  {
		$fileIdentifier = $folderIdentifier . $fileName;

		return $this->fileExists($fileIdentifier);
	}

	/**
	 * Returns a path to a local copy of a file for processing it. When changing the
	 * file, you have to take care of replacing the current version yourself!
	 *
	 * @param string $fileIdentifier
	 * @param bool $writable Set this to FALSE if you only need the file for read
	 *                       operations. This might speed up things, e.g. by using
	 *                       a cached local version. Never modify the file if you
	 *                       have set this flag!
	 * @return string The path to the file on the local disk
	 */
	public function getFileForLocalProcessing($fileIdentifier, $writable = TRUE) {
		return $this->copyFileToTemporaryPath($fileIdentifier);
	}

	/**
	 * Returns the permissions of a file as an array (keys r, w) of boolean flags
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return array
	 */
	public function getFilePermissions(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		return array('r' => TRUE, 'w' => TRUE);
	}

	/**
	 * Returns the permissions of a folder as an array (keys r, w) of boolean flags
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return array
	 */
	public function getFolderPermissions(\TYPO3\CMS\Core\Resource\Folder $folder) {
		return array('r' => TRUE, 'w' => TRUE);
	}

	/**
	 * Renames a file in this storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $newName The target path (including the file name!)
	 * @return string The identifier of the file after renaming
	 */
	public function renameFile($fileIdentifier, $newName) {
		$targetPath = dirname($fileIdentifier) . '/' . $newName;
		$this->dropbox->move($fileIdentifier, $targetPath);
		// remove cache for folder
		$this->removeCacheForPath(dirname($fileIdentifier));
		// remove cache for this file
		$this->removeCacheForPath($fileIdentifier);
	}

	/**
	 * Replaces a file with file in local file system.
	 *
	 * @param string $fileIdentifier
	 * @param string $localFilePath
	 * @return boolean TRUE if the operation succeeded
	 */
	public function replaceFile($fileIdentifier, $localFilePath) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
		$this->removeCacheForPath(dirname($fileIdentifier));
	}

	/**
	 * Returns information about a file.
	 *
	 * @param string $fileIdentifier
	 * @param array $propertiesToExtract Array of properties which are be extracted
	 *                                   If empty all will be extracted
	 * @return array
	 */
	public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = array()) {
		$info = $this->getMetaData($fileIdentifier, false);
		$fileInfo = array(
			'mtime' => time(),
			'ctime' => time(),
			'mimetype' => $info['mime_type'],
			'name' => basename($fileIdentifier),
			'size' => $info['bytes'],
			'identifier' => $fileIdentifier,
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
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return string The temporary path
	 */
	public function copyFileToTemporaryPath(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		$id = sha1($file->getStorage()->getUid() . ':' . $file->getIdentifier());
		$temporaryPath = PATH_site . 'typo3temp/fal-dropbox-' . $id . '.' . $file->getExtension();
		$content = $this->dropbox->getFile($file->getIdentifier());
		file_put_contents($temporaryPath, $content);

		return $temporaryPath;
	}

	/**
	 * Moves a file *within* the current storage.
	 * Note that this is only about an inner-storage move action,
	 * where a file is just moved to another folder in the same storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFileName
	 *
	 * @return string
	 */
	public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName) {
		$targetPath = $targetFolderIdentifier . $newFileName;
		$this->dropbox->move($fileIdentifier, $targetPath);
		$this->removeCacheForPath(dirname($fileIdentifier));
		$this->removeCacheForPath(dirname($targetPath));
		return $targetFolderIdentifier . $newFileName;
	}

	/**
	 * Copies a file *within* the current storage.
	 * Note that this is only about an inner storage copy action,
	 * where a file is just copied to another folder in the same storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $fileName
	 * @return string the Identifier of the new file
	 */
	public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName) {
		$targetPath = $targetFolderIdentifier . $fileName;
		$this->dropbox->copy($fileIdentifier, $targetPath);
		$this->removeCacheForPath(dirname($targetPath));
		return $this->getFile($targetPath);
	}

	/**
	 * Folder equivalent to moveFileWithinStorage().
	 *
	 * @param string $sourceFolderIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFolderName
	 *
	 * @return array All files which are affected, map of old => new file identifiers
	 */
	public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Folder equivalent to copyFileWithinStorage().
	 *
	 * @param string $sourceFolderIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFolderName
	 *
	 * @return boolean
	 */
	public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Removes a file from the filesystem. This does not check if the file is
	 * still used or if it is a bad idea to delete it for some other reason
	 * this has to be taken care of in the upper layers (e.g. the Storage)!
	 *
	 * @param string $fileIdentifier
	 * @return boolean TRUE if deleting the file succeeded
	 */
	public function deleteFile($fileIdentifier) {
		$status = $this->dropbox->delete($fileIdentifier);
		if ($status['is_deleted']) {
			$this->removeCacheForPath(dirname($fileIdentifier));
			return true;
		} else return false;
	}

	/**
	 * Removes a folder in filesystem.
	 *
	 * @param string $folderIdentifier
	 * @param boolean $deleteRecursively
	 * @return boolean
	 */
	public function deleteFolder($folderIdentifier, $deleteRecursively = FALSE) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
		$this->removeCacheForPath(dirname($folderIdentifier));
	}

	/**
	 * Adds a file at the specified location. This should only be used internally.
	 *
	 * @param string $localFilePath
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $targetFileName
	 * @return string The new identifier of the file
	 */
	// TODO check if this is still necessary if we move more logic to the storage
	public function addFileRaw($localFilePath, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $targetFileName) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Deletes a file without access and usage checks.
	 * This should only be used internally.
	 *
	 * This accepts an identifier instead of an object because we might want to
	 * delete files that have no object associated with (or we don't want to
	 * create an object for) them - e.g. when moving a file to another storage.
	 *
	 * @param string $identifier
	 * @return boolean TRUE if removing the file succeeded
	 */
	public function deleteFileRaw($identifier) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/*******************
	 * FOLDER FUNCTIONS
	 *******************/
	/**
	 * Returns the root level folder of the storage.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getRootLevelFolder() {
		return $this->getFolder('/');
	}

	/**
	 * Returns the default folder new files should be put into.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getDefaultFolder() {
//		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Creates a folder.
	 *
	 * @param string $newFolderName
	 * @param string $parentFolderIdentifier
	 * @param boolean $recursive
	 * @return string the Identifier of the new folder
	 */
	public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = FALSE) {
		$this->dropbox->createFolder($newFolderName);
		$this->removeCacheForPath($parentFolderIdentifier);

		/** @var $factory \TYPO3\CMS\Core\Resource\ResourceFactory */
		$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\ResourceFactory');
		$folderPath = $parentFolderIdentifier . $newFolderName . '/';
		return $factory->createFolderObject($this->storage, $folderPath, $newFolderName);
	}

	/**
	 * Checks if a folder exists
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function folderExists($identifier) {
		$info = $this->getMetaData($identifier);
		if($info['is_dir']) {
			return true;
		} else return false;
	}

	/**
	 * Checks if a folder inside a folder exists.
	 *
	 * @param string $folderName
	 * @param string $folderIdentifier
	 * @return boolean
	 */
	public function folderExistsInFolder($folderName, $folderIdentifier) {
		$folderIdentifier = $folderIdentifier . $folderName . '/';
		return $this->resourceExists($folderIdentifier);
	}

	/**
	 * Renames a folder in this storage.
	 *
	 * @param string $folderIdentifier
	 * @param string $newName
	 * @return array A map of old to new file identifiers of all affected resources
	 */
	public function renameFolder($folderIdentifier, $newName) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Checks if a given identifier is within a container, e.g. if
	 * a file or folder is within another folder.
	 * This can e.g. be used to check for web-mounts.
	 *
	 * Hint: this also needs to return TRUE if the given identifier
	 * matches the container identifier to allow access to the root
	 * folder of a filemount.
	 *
	 * @param string $folderIdentifier
	 * @param string $identifier identifier to be checked against $folderIdentifier
	 * @return boolean TRUE if $content is within or matches $folderIdentifier
	 */
	public function isWithin($folderIdentifier, $identifier) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param string $folderIdentifier
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty($folderIdentifier) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Makes sure the path given as parameter is valid
	 *
	 * @param string $filePath The file path (most times filePath)
	 * @return string
	 */
	protected function canonicalizeAndCheckFilePath($filePath) {

	}

	/**
	 * Makes sure the identifier given as parameter is valid
	 *
	 * @param string $fileIdentifier The file Identifier
	 * @return string
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
	 */
	protected function canonicalizeAndCheckFileIdentifier($fileIdentifier) {

	}

	/**
	 * Makes sure the identifier given as parameter is valid
	 *
	 * @param string $folderIdentifier The folder identifier
	 * @return string
	 */
	protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier) {

	}

	/**
	 * Merges the capabilites merged by the user at the storage
	 * configuration into the actual capabilities of the driver
	 * and returns the result.
	 *
	 * @param integer $capabilities
	 *
	 * @return integer
	 */
	public function mergeConfigurationCapabilities($capabilities) {

	}

	/**
	 * Returns the identifier of the folder the file resides in
	 *
	 * @param string $fileIdentifier
	 *
	 * @return string
	 */
	public function getParentFolderIdentifierOfIdentifier($fileIdentifier) {

	}

	/**
	 * Returns the permissions of a file/folder as an array
	 * (keys r, w) of boolean flags
	 *
	 * @param string $identifier
	 * @return array
	 */
	public function getPermissions($identifier) {

	}

	/**
	 * Directly output the contents of the file to the output
	 * buffer. Should not take care of header files or flushing
	 * buffer before. Will be taken care of by the Storage.
	 *
	 * @param string $identifier
	 * @return void
	 */
	public function dumpFileContents($identifier) {

	}

	/**
	 * Returns information about a file.
	 *
	 * @param string $folderIdentifier
	 * @return array
	 */
	public function getFolderInfoByIdentifier($folderIdentifier) {

	}

	/**
	 * Returns a list of files inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param boolean $recursive
	 * @param array $filenameFilterCallbacks callbacks for filtering the items
	 *
	 * @return array of FileIdentifiers
	 */
	public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $filenameFilterCallbacks = array()) {

	}
	/**
	 * Returns a list of folders inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param boolean $recursive
	 * @param array $folderNameFilterCallbacks callbacks for filtering the items
	 *
	 * @return array of Folder Identifier
	 */
	public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $folderNameFilterCallbacks = array()) {
		
	}


}
?>
