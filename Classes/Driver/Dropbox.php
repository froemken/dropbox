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

require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fal_dropbox', 'Classes/Dropbox/autoload.php');

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
	 * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resource
	 * @param bool  $relativeToCurrentScript    Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl(\TYPO3\CMS\Core\Resource\ResourceInterface $resource, $relativeToCurrentScript = FALSE) {
		if ($this->storage->isPublic()) {
			// as the storage is marked as public, we can simply use the public URL here.
			if (is_object($resource)) {
				if(TYPO3_MODE == 'BE') {
					if (method_exists($resource, 'isProcessed') && $resource->isProcessed()) {
						$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
						$file = $factory->retrieveFileOrFolderObject($resource->getStorage()->getUid() . ':' . $resource->getIdentifier());
						$processingFolder = $this->storage->getProcessingFolder()->getIdentifier();
						$result = $this->dropbox->media($processingFolder . $file->getNameWithoutExtension() . '.' . $file->getExtension());
					} else {
						$result = $this->dropbox->media($resource->getIdentifier());
					}
					return $result['url'];
				} else {
					$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
					$file = $factory->retrieveFileOrFolderObject($resource->getStorage()->getUid() . ':' . $resource->getIdentifier());

					$id = sha1($file->getStorage()->getUid() . ':' . $file->getIdentifier());
					$uploadPath = 'uploads/pics/fal-dropbox-' . $id . '.' . $file->getExtension();
					file_put_contents(PATH_site . $uploadPath, $this->dropbox->getFile($resource->getIdentifier()));

					return $uploadPath;
				}
			} else {
				return '/';
			}
		}
	}

	/**
	 * Creates a (cryptographic) hash for a file.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 */
	public function hash(\TYPO3\CMS\Core\Resource\FileInterface $file, $hashAlgorithm) {
		switch ($hashAlgorithm) {
			case 'sha1':
				return sha1($file->getIdentifier());
				break;
		}
	}

	/**
	 * Creates a new file and returns the matching file object for it.
	 *
	 * @param string $fileName
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	public function createFile($fileName, \TYPO3\CMS\Core\Resource\Folder $parentFolder) {
		// get full target path incl. filename
		$fileIdentifier = $parentFolder->getIdentifier() . $fileName;

		// dropbox cannot create (touch) files. So we have to do this here.
		$emptyTempFilePath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('empty');
		$this->dropbox->putFile($fileIdentifier, $emptyTempFilePath);

		// delete cache entries for current folder
		$this->removeCacheForPath($parentFolder->getIdentifier());
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
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return string The file contents
	 */
	public function getFileContents(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		return $this->dropbox->getFile($file->getIdentifier());
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
		$tempPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('dropboxPutFile');
		file_put_contents($tempPath, $contents);
		$this->dropbox->putFile($file->getIdentifier(), $tempPath);
		unlink($tempPath);

		// remove cache for folder
		$this->removeCacheForPath(dirname($file->getIdentifier()));
		// the file was overwritten, so we have to remove the cache entry for the modified file too
		$this->removeCacheForPath($file->getIdentifier());
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
		unlink($localFilePath);

		// remove cache for folder
		$this->removeCacheForPath($targetFolder->getIdentifier());
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
	 * Checks if a file inside a storage folder exists.
	 *
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
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param bool $writable Set this to FALSE if you only need the file for read operations. This might speed up things, e.g. by using a cached local version. Never modify the file if you have set this flag!
	 * @return string The path to the file on the local disk
	 */
	// TODO decide if this should return a file handle object
	public function getFileForLocalProcessing(\TYPO3\CMS\Core\Resource\FileInterface $file, $writable = TRUE) {
		return $this->copyFileToTemporaryPath($file);
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
	 * Renames a file
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param string $newName
	 * @return string The new identifier of the file if the operation succeeds
	 * @throws RuntimeException if renaming the file failed
	 */
	public function renameFile(\TYPO3\CMS\Core\Resource\FileInterface $file, $newName) {
		$sourcePath = $file->getIdentifier();
		$targetPath = dirname($file->getIdentifier()) . '/' . $newName;
		$this->dropbox->move($sourcePath, $targetPath);
		// remove cache for folder
		$this->removeCacheForPath(dirname($file->getIdentifier()));
		// remove cache for this file
		$this->removeCacheForPath($file->getIdentifier());
	}

	/**
	 * Replaces the contents (and file-specific metadata) of a file object with a local file.
	 *
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $file
	 * @param string $localFilePath
	 * @return boolean
	 */
	public function replaceFile(\TYPO3\CMS\Core\Resource\AbstractFile $file, $localFilePath) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
		$this->removeCacheForPath(dirname($file->getIdentifier()));
	}

	/**
	 * Returns information about a file for a given file identifier.
	 *
	 * @param string $identifier The (relative) path to the file.
	 * @return array
	 */
	public function getFileInfoByIdentifier($identifier) {
		$info = $this->getMetaData($identifier, false);
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
	 * Note that this is only about an intra-storage move action, where a file is just
	 * moved to another folder in the same storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param \TYPO3\CMS\Core\Resource\Folder $targetFolder
	 * @param string $fileName
	 * @return string The new identifier of the file
	 */
	public function moveFileWithinStorage(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $targetFolder, $fileName) {
		$sourcePath = $file->getIdentifier();
		$targetPath = $targetFolder->getIdentifier() . $fileName;
		$this->dropbox->move($sourcePath, $targetPath);
		$this->removeCacheForPath(dirname($sourcePath));
		$this->removeCacheForPath(dirname($targetPath));
		return $targetFolder->getIdentifier() . $fileName;
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
		$sourcePath = $file->getIdentifier();
		$targetPath = $targetFolder->getIdentifier() . $fileName;
		$this->dropbox->copy($sourcePath, $targetPath);
		$this->removeCacheForPath(dirname($targetPath));
		return $this->getFile($targetPath);
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
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
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
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Removes a file from this storage. This does not check if the file is
	 * still used or if it is a bad idea to delete it for some other reason
	 * this has to be taken care of in the upper layers (e.g. the Storage)!
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return boolean TRUE if deleting the file succeeded
	 */
	public function deleteFile(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		$status = $this->dropbox->delete($file->getIdentifier());
		if ($status['is_deleted']) {
			$this->removeCacheForPath(dirname($file->getIdentifier()));
			return true;
		} else return false;
	}

	/**
	 * Removes a folder from this storage.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param boolean $deleteRecursively
	 * @return boolean
	 */
	public function deleteFolder(\TYPO3\CMS\Core\Resource\Folder $folder, $deleteRecursively = FALSE) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
		$this->removeCacheForPath(dirname($folder->getIdentifier()));
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
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
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
		$this->removeCacheForPath($parentFolder->getIdentifier());

		/** @var $factory \TYPO3\CMS\Core\Resource\ResourceFactory */
		$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\ResourceFactory');
		$folderPath = $parentFolder->getIdentifier() . $newFolderName . '/';
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
	 * Checks if a file inside a storage folder exists.
	 *
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
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Checks if a given object or identifier is within a container, e.g. if
	 * a file or folder is within another folder.
	 * This can e.g. be used to check for webmounts.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $container
	 * @param mixed $content An object or an identifier to check
	 * @return boolean TRUE if $content is within $container
	 */
	public function isWithin(\TYPO3\CMS\Core\Resource\Folder $container, $content) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty(\TYPO3\CMS\Core\Resource\Folder $folder) {
		TYPO3\CMS\Core\Utility\DebugUtility::debug(__FUNCTION__, 'Method');
	}
}
?>