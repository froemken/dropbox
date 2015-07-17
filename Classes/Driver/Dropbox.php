<?php
namespace SFroemken\FalDropbox\Driver;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Stefan froemken <froemken@gmail.com>
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
use SFroemken\FalDropbox\Dropbox\WriteMode;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @author Stefan Froemken <froemken@gmail.com>
 * @package fal_dropbox
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Dropbox extends AbstractDriver {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected $cacheManager = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
	 */
	protected $cache = NULL;

	/**
	 * @var \SFroemken\FalDropbox\Dropbox\Client
	 */
	protected $dropboxClient = NULL;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * processes the configuration, should be overridden by subclasses
	 *
	 * @return void
	 */
	public function processConfiguration() {
		// no need to configure something.
	}

	/**
	 * Sets the storage uid the driver belongs to
	 *
	 * @param int $storageUid
	 * @return void
	 */
	public function setStorageUid($storageUid) {
		$this->storageUid = (int)$storageUid;
	}

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->cacheManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$this->cache = $this->cacheManager->getCache('fal_dropbox');
		if (!empty($this->configuration['accessToken'])) {
			$this->dropboxClient = $this->objectManager->get(
				'SFroemken\\FalDropbox\\Dropbox\\Client',
				$this->configuration['accessToken'],
				'TYPO3 CMS'
			);
		} else {
			$this->dropboxClient = NULL;
		}
	}

	/**
	 * Returns the capabilities of this driver.
	 *
	 * @return int
	 * @see Storage::CAPABILITY_* constants
	 */
	public function getCapabilities() {
		// if PUBLIC is available, each file will initiate a request to Dropbox-Api to retrieve a public share link
		// this is extremely slow.

		// return ResourceStorage::CAPABILITY_BROWSABLE + ResourceStorage::CAPABILITY_PUBLIC + ResourceStorage::CAPABILITY_WRITABLE;
		return ResourceStorage::CAPABILITY_BROWSABLE + ResourceStorage::CAPABILITY_WRITABLE;
	}

	/**
	 * Merges the capabilities merged by the user at the storage
	 * configuration into the actual capabilities of the driver
	 * and returns the result.
	 *
	 * @param int $capabilities
	 * @return int
	 */
	public function mergeConfigurationCapabilities($capabilities) {

	}

	/**
	 * Returns TRUE if this driver has the given capability.
	 *
	 * @param int $capability A capability, as defined in a CAPABILITY_* constant
	 * @return bool
	 */
	public function hasCapability($capability) {

	}

	/**
	 * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
	 * is a configurable setting, but the setting does not change the way the
	 * underlying file system treats the identifiers; the setting should
	 * therefore always reflect the file system and not try to change its
	 * behaviour
	 *
	 * @return bool
	 */
	public function isCaseSensitiveFileSystem() {
		if (isset($this->configuration['caseSensitive'])) {
			return (bool)$this->configuration['caseSensitive'];
		}
		return TRUE;
	}

	/**
	 * Hashes a file identifier, taking the case sensitivity of the file system
	 * into account. This helps mitigating problems with case-insensitive
	 * databases.
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function hashIdentifier($identifier) {
		$identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
		return sha1($identifier);
	}

	/**
	 * Returns the identifier of the root level folder of the storage.
	 *
	 * @return string
	 */
	public function getRootLevelFolder() {
		return '/';
	}

	/**
	 * Returns the default folder new files should be put into.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Folder
	 */
	public function getDefaultFolder() {
		DebugUtility::debug(__FUNCTION__, 'Method');
	}

	/**
	 * Returns the identifier of the folder the file resides in
	 *
	 * @param string $fileIdentifier
	 * @return string
	 */
	public function getParentFolderIdentifierOfIdentifier($fileIdentifier) {
		$fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
		return PathUtility::dirname($fileIdentifier) . '/';
	}

	/**
	 * Returns the public URL to a file.
	 * Either fully qualified URL or relative to PATH_site (rawurlencoded).
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function getPublicUrl($identifier) {
		return $this->dropboxClient->createShareableLink($identifier);
	}

	/**
	 * Creates a folder, within a parent folder.
	 * If no parent folder is given, a root level folder will be created
	 *
	 * @param string $newFolderName
	 * @param string $parentFolderIdentifier
	 * @param bool $recursive
	 * @return string the Identifier of the new folder
	 */
	public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = FALSE) {
		$newFolderName = trim($newFolderName, '/');
		$parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
		$this->dropboxClient->createFolder($parentFolderIdentifier . $newFolderName);
		$newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
		$this->cache->flush();
		return $newIdentifier;
	}

	/**
	 * Renames a folder in this storage.
	 *
	 * @param string $folderIdentifier
	 * @param string $newName
	 * @return array A map of old to new file identifiers of all affected resources
	 */
	public function renameFolder($folderIdentifier, $newName) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
		$newName = $this->sanitizeFileName($newName);

		$targetIdentifier = PathUtility::dirname($folderIdentifier) . '/' . $newName;
		$targetIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetIdentifier);

		// dropbox don't like slashes at the end of identifier
		$this->dropboxClient->move(rtrim($folderIdentifier, '/'), rtrim($targetIdentifier, '/'));
		$this->cache->flush();
		return array();
	}

	/**
	 * Removes a folder in filesystem.
	 *
	 * @param string $folderIdentifier
	 * @param bool $deleteRecursively
	 * @return bool
	 */
	public function deleteFolder($folderIdentifier, $deleteRecursively = FALSE) {
		$identifier = $folderIdentifier === '/' ?: rtrim($folderIdentifier, '/');
		$status = $this->dropboxClient->delete($identifier);
		if ($status['is_deleted']) {
			$this->cache->flush();
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Checks if a file exists.
	 *
	 * @param string $fileIdentifier
	 * @return bool
	 */
	public function fileExists($fileIdentifier) {
		$info = $this->getMetaData($fileIdentifier);
		if (!empty($info) && !$info['is_dir'] && !$info['is_deleted']) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Checks if a folder exists.
	 *
	 * @param string $folderIdentifier
	 * @return bool
	 */
	public function folderExists($folderIdentifier) {
		$info = $this->getMetaData($folderIdentifier);
		if (!empty($info) && $info['is_dir'] && !$info['is_deleted']) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param string $folderIdentifier
	 * @return bool TRUE if there are no files and folders within $folder
	 */
	public function isFolderEmpty($folderIdentifier) {
		DebugUtility::debug(__FUNCTION__, 'Method');
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
	 * @param bool $removeOriginal if set the original file will be removed
	 *                                after successful operation
	 * @return string the identifier of the new file
	 */
	public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = TRUE) {
		$localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
		$newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;

		$handle = fopen($localFilePath, 'rb');
		$this->dropboxClient->uploadFile($newFileIdentifier, WriteMode::add(), $handle);
		// $handle was fclosed by uploadFile()

		if ($removeOriginal) {
			unlink($localFilePath);
		}
		$this->cache->flush();
		return $newFileIdentifier;
	}

	/**
	 * Creates a new (empty) file and returns the identifier.
	 *
	 * @param string $fileName
	 * @param string $parentFolderIdentifier
	 * @return string
	 */
	public function createFile($fileName, $parentFolderIdentifier) {
		$parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
		$fileIdentifier =  $this->canonicalizeAndCheckFileIdentifier(
			$parentFolderIdentifier . $this->sanitizeFileName(ltrim($fileName, '/'))
		);

		// Dropbox can not create files. So we have to create an empty file locally and upload it to Dropbox
		$localFilePath = GeneralUtility::tempnam('fal_dropbox');
		$handle = fopen($localFilePath, 'rb');
		$this->dropboxClient->uploadFile($fileIdentifier, WriteMode::force(), $handle);
		// $handle was fclosed by uploadFile()
		unlink($localFilePath);

		$this->cache->flush();

		return $fileIdentifier;
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
		$fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
		$targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $fileName);

		// dropbox don't like slashes at the end of identifier
		$this->dropboxClient->copy($fileIdentifier, $targetFileIdentifier);
		$this->cache->flush();
		return $targetFileIdentifier;
	}

	/**
	 * Renames a file in this storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $newName The target path (including the file name!)
	 * @return string The identifier of the file after renaming
	 */
	public function renameFile($fileIdentifier, $newName) {
		$fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
		$newName = $this->sanitizeFileName($newName);

		$targetIdentifier = PathUtility::dirname($fileIdentifier) . '/' . $newName;
		$targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);

		$this->dropboxClient->move($fileIdentifier, $targetIdentifier);
		$this->cache->flush();
		return $targetIdentifier;
	}

	/**
	 * Replaces a file with file in local file system.
	 *
	 * @param string $fileIdentifier
	 * @param string $localFilePath
	 * @return bool TRUE if the operation succeeded
	 */
	public function replaceFile($fileIdentifier, $localFilePath) {
		DebugUtility::debug(__FUNCTION__, 'Method');
		$this->cache->flush();
	}

	/**
	 * Removes a file from the filesystem. This does not check if the file is
	 * still used or if it is a bad idea to delete it for some other reason
	 * this has to be taken care of in the upper layers (e.g. the Storage)!
	 *
	 * @param string $fileIdentifier
	 * @return bool TRUE if deleting the file succeeded
	 */
	public function deleteFile($fileIdentifier) {
		$status = $this->dropboxClient->delete($fileIdentifier);
		if ($status['is_deleted']) {
			$this->cache->flush();
			return TRUE;
		} else {
			return FALSE;
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
	 * Moves a file *within* the current storage.
	 * Note that this is only about an inner-storage move action,
	 * where a file is just moved to another folder in the same storage.
	 *
	 * @param string $fileIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFileName
	 * @return string
	 */
	public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName) {
		$fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
		$targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $newFileName);

		// dropbox don't like slashes at the end of identifier
		$this->dropboxClient->move($fileIdentifier, $targetFileIdentifier);
		$this->cache->flush();
		return $targetFileIdentifier;
	}

	/**
	 * Folder equivalent to moveFileWithinStorage().
	 *
	 * @param string $sourceFolderIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFolderName
	 * @return array All files which are affected, map of old => new file identifiers
	 */
	public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
		$sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
		$targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);

		// dropbox don't like slashes at the end of identifier
		$this->dropboxClient->move(rtrim($sourceFolderIdentifier, '/'), rtrim($targetFolderIdentifier, '/'));
		$this->cache->flush();
		return array();
	}

	/**
	 * Folder equivalent to copyFileWithinStorage().
	 *
	 * @param string $sourceFolderIdentifier
	 * @param string $targetFolderIdentifier
	 * @param string $newFolderName
	 * @return bool
	 */
	public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName) {
		$sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
		$targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);

		// dropbox don't like slashes at the end of identifier
		$this->dropboxClient->copy(rtrim($sourceFolderIdentifier, '/'), rtrim($targetFolderIdentifier, '/'));
		$this->cache->flush();
		return array();
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
		// Dropbox can't deliver the file content. So we have to download the file first
		$temporaryPath = GeneralUtility::tempnam('fal_dropbox');
		$handle = fopen($temporaryPath, 'w+b');
		$this->dropboxClient->getFile($fileIdentifier, $handle);
		fclose($handle);
		$content = file_get_contents($temporaryPath);
		unlink($temporaryPath);
		return $content;
	}

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param string $fileIdentifier
	 * @param string $contents
	 * @return int The number of bytes written to the file
	 */
	public function setFileContents($fileIdentifier, $contents) {
		$localFilePath = GeneralUtility::tempnam('fal_dropbox');
		$bytes = file_put_contents($localFilePath, $contents);
		$handle = fopen($localFilePath, 'rb');
		$this->dropboxClient->uploadFile($fileIdentifier, WriteMode::force(), $handle);
		// $handle was fclosed by uploadFile()

		unlink($localFilePath);
		$this->cache->flush();
		return $bytes;
	}

	/**
	 * Checks if a file inside a folder exists
	 *
	 * @param string $fileName
	 * @param string $folderIdentifier
	 * @return bool
	 */
	public function fileExistsInFolder($fileName, $folderIdentifier) {
		$fileIdentifier = $folderIdentifier . $fileName;
		return $this->fileExists($fileIdentifier);
	}

	/**
	 * Checks if a folder inside a folder exists.
	 *
	 * @param string $folderName
	 * @param string $folderIdentifier
	 * @return bool
	 */
	public function folderExistsInFolder($folderName, $folderIdentifier) {
		$identifier = $folderIdentifier . '/' . $folderName;
		$identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
		return $this->folderExists($identifier);
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
	 * Returns the permissions of a file/folder as an array
	 * (keys r, w) of boolean flags
	 *
	 * @param string $identifier
	 * @return array
	 */
	public function getPermissions($identifier) {
		// we are authenticated as a valid dropbox user
		// so all files are readable and writeable
		return array(
			'r' => TRUE,
			'w' => TRUE
		);
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
		$handle = fopen('php://output', 'w');
		$this->dropboxClient->getFile($identifier, $handle);
		fclose($handle);
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
	 * @return bool TRUE if $content is within or matches $folderIdentifier
	 */
	public function isWithin($folderIdentifier, $identifier) {
		$folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
		$entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
		if ($folderIdentifier === $entryIdentifier) {
			return TRUE;
		}
		// File identifier canonicalization will not modify a single slash so
		// we must not append another slash in that case.
		if ($folderIdentifier !== '/') {
			$folderIdentifier .= '/';
		}
		return GeneralUtility::isFirstPartOfStr($entryIdentifier, $folderIdentifier);
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
		$dirPath = PathUtility::dirname($fileIdentifier);
		$dirPath = $this->canonicalizeAndCheckFolderIdentifier($dirPath);
		if (empty($propertiesToExtract)) {
			$propertiesToExtract = array(
				'size', 'atime', 'atime', 'mtime', 'ctime', 'mimetype', 'name',
				'identifier', 'identifier_hash', 'storage', 'folder_hash'
			);
		}
		$fileInformation = array();
		foreach ($propertiesToExtract as $property) {
			$fileInformation[$property] = $this->getSpecificFileInformation($fileIdentifier, $dirPath, $property);
		}
		return $fileInformation;
	}

	/**
	 * Extracts a specific FileInformation from the FileSystems.
	 *
	 * @param string $fileIdentifier
	 * @param string $containerPath
	 * @param string $property
	 *
	 * @return bool|int|string
	 * @throws \InvalidArgumentException
	 */
	public function getSpecificFileInformation($fileIdentifier, $containerPath, $property) {
		$identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));
		$info = $this->getMetaData($fileIdentifier);
		$dateTime = \DateTime::createFromFormat('D, d M Y H:i:s O', $info['modified']);
		switch ($property) {
			case 'size':
				return $info['bytes'];
			case 'atime':
				return $dateTime->format('U');
			case 'mtime':
				return $dateTime->format('U');
			case 'ctime':
				return $dateTime->format('U');
			case 'name':
				return PathUtility::basename($fileIdentifier);
			case 'mimetype':
				return $info['mime_type'];
			case 'identifier':
				return $identifier;
			case 'storage':
				return $this->storageUid;
			case 'identifier_hash':
				return $this->hashIdentifier($identifier);
			case 'folder_hash':
				return $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($identifier));
			default:
				throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property));
		}
	}

	/**
	 * Returns information about a file.
	 *
	 * @param string $folderIdentifier
	 * @return array
	 */
	public function getFolderInfoByIdentifier($folderIdentifier) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

		return array(
			'identifier' => $folderIdentifier,
			'name' => PathUtility::basename($folderIdentifier),
			'storage' => $this->storageUid
		);
	}

	/**
	 * Returns the identifier of a file inside the folder
	 *
	 * @param string $fileName
	 * @param string $folderIdentifier
	 * @return string file identifier
	 */
	public function getFileInFolder($fileName, $folderIdentifier) {

	}

	/**
	 * Returns a list of files inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param int $start
	 * @param int $numberOfItems
	 * @param bool $recursive
	 * @param array $filenameFilterCallbacks callbacks for filtering the items
	 * @param string $sort Property name used to sort the items.
	 *                     Among them may be: '' (empty, no sorting), name,
	 *                     fileext, size, tstamp and rw.
	 *                     If a driver does not support the given property, it
	 *                     should fall back to "name".
	 * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
	 * @return array of FileIdentifiers
	 */
	public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $filenameFilterCallbacks = array(), $sort = '', $sortRev = FALSE) {
		$files = array();
		$info = $this->getMetaData($folderIdentifier);
		if (!empty($info['contents'])) {
			foreach ($info['contents'] as $entry) {
				if ($entry['is_dir'] === FALSE) {
					$files[] = $entry['path'];
				}
			}
		}
		return $files;
	}

	/**
	 * Returns the identifier of a folder inside the folder
	 *
	 * @param string $folderName The name of the target folder
	 * @param string $folderIdentifier
	 * @return string folder identifier
	 */
	public function getFolderInFolder($folderName, $folderIdentifier) {
		$folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
		return $folderIdentifier;
	}

	/**
	 * Returns a list of folders inside the specified path
	 *
	 * @param string $folderIdentifier
	 * @param int $start
	 * @param int $numberOfItems
	 * @param bool $recursive
	 * @param array $folderNameFilterCallbacks callbacks for filtering the items
	 * @param string $sort Property name used to sort the items.
	 *                     Among them may be: '' (empty, no sorting), name,
	 *                     fileext, size, tstamp and rw.
	 *                     If a driver does not support the given property, it
	 *                     should fall back to "name".
	 * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
	 * @return array of Folder Identifier
	 */
	public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = FALSE, array $folderNameFilterCallbacks = array(), $sort = '', $sortRev = FALSE) {
		$folders = array();
		$info = $this->getMetaData($folderIdentifier);
		if (!empty($info['contents'])) {
			foreach ($info['contents'] as $entry) {
				if ($entry['is_dir'] && $entry['path'] !== '/_processed') {
					$folders[] = $entry['path'] . '/';
				}
			}
		}
		return $folders;
	}

	/**
	 * Returns the number of files inside the specified path
	 *
	 * @param string  $folderIdentifier
	 * @param bool $recursive
	 * @param array   $filenameFilterCallbacks callbacks for filtering the items
	 * @return integer Number of files in folder
	 */
	public function countFilesInFolder($folderIdentifier, $recursive = FALSE, array $filenameFilterCallbacks = array()) {
		$amountOfFiles = 0;
		$info = $this->getMetaData($folderIdentifier);
		if (!empty($info['contents'])) {
			foreach ($info['contents'] as $entry) {
				if ($entry['is_dir'] === FALSE) {
					$amountOfFiles++;
				}
			}
		}
		return $amountOfFiles;
	}

	/**
	 * Returns the number of folders inside the specified path
	 *
	 * @param string  $folderIdentifier
	 * @param bool $recursive
	 * @param array   $folderNameFilterCallbacks callbacks for filtering the items
	 * @return integer Number of folders in folder
	 */
	public function countFoldersInFolder($folderIdentifier, $recursive = FALSE, array $folderNameFilterCallbacks = array()) {
		$amountOfFiles = 0;
		$info = $this->getMetaData($folderIdentifier);
		if (!empty($info['contents'])) {
			foreach ($info['contents'] as $entry) {
				if ($entry['is_dir'] === TRUE) {
					$amountOfFiles++;
				}
			}
		}
		return $amountOfFiles;
	}

	/**
	 * Makes sure the Path given as parameter is valid
	 *
	 * @param string $filePath The file path (including the file name!)
	 * @return string
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
	 */
	protected function canonicalizeAndCheckFilePath($filePath) {
		$filePath = PathUtility::getCanonicalPath($filePath);

		// filePath must be valid
		// Special case is required by vfsStream in Unit Test context
		if (!GeneralUtility::validPathStr($filePath)) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
		}
		return $filePath;
	}

	/**
	 * Makes sure the identifier given as parameter is valid
	 *
	 * @param string $fileIdentifier The file Identifier
	 * @return string
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
	 */
	protected function canonicalizeAndCheckFileIdentifier($fileIdentifier) {
		if ($fileIdentifier !== '') {
			$fileIdentifier = $this->canonicalizeAndCheckFilePath($fileIdentifier);
			$fileIdentifier = '/' . ltrim($fileIdentifier, '/');
			if (!$this->isCaseSensitiveFileSystem()) {
				$fileIdentifier = strtolower($fileIdentifier);
			}
		}
		return $fileIdentifier;
	}

	/**
	 * Makes sure the identifier given as parameter is valid
	 *
	 * @param string $folderIdentifier The folder identifier
	 * @return string
	 */
	protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier) {
		if ($folderIdentifier === '/') {
			$canonicalizedIdentifier = $folderIdentifier;
		} else {
			$canonicalizedIdentifier = rtrim($this->canonicalizeAndCheckFileIdentifier($folderIdentifier), '/') . '/';
		}
		return $canonicalizedIdentifier;
	}
























	/**
	 * get file or folder information from cache or directly from dropbox
	 *
	 * @param string $path Path to receive information from
	 * @return array file or folder informations
	 */
	public function getMetaData($path) {
		$path = $path === '/' ? '/' : rtrim($path, '/');

		if (trim($path, '/') === '_processed_') {
			return array(
				'path' => '/_processed_',
				'is_dir' => TRUE
			);
		}

		$cacheKey = $this->getCacheIdentifierForPath($path);

		try {
			if ($this->cache->has($cacheKey)) {
				$info = $this->cache->get($cacheKey);
			} else {
				$info = $this->dropboxClient->getMetadataWithChildren($path);
				$this->cache->set($cacheKey, $info);
				// create cache entries for sub resources
				if (!empty($info['contents'])) {
					foreach ($info['contents'] as $key => $resource) {
						if ($resource['path'] === '/_processed_') {
							unset($info['contents'][$key]);
						} else {
							$this->cacheResource($resource);
						}
					}
				}
			}
		} catch(\Exception $e) {
			// if something crashes return an empty array
			$info = array();
		}
		return $info;
	}

	/**
	 * cache resource
	 *
	 * @param $resource
	 * @return void
	 */
	protected function cacheResource($resource) {
		if (!empty($resource)) {
			$cacheKey = $this->getCacheIdentifierForPath($resource['path']);
			if (!$this->cache->has($cacheKey)) {
				if (!$resource['is_dir']) {
					$this->cache->set($cacheKey, $resource);
				}
			}
		}
	}

	/**
	 * Returns the cache identifier for a given path.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getCacheIdentifierForPath($path) {
		return sha1($this->storageUid . ':' . trim($path, '/'));
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
		$identifier = $identifier === '/' ? $identifier : rtrim($identifier, '/');
		$info = $this->getMetaData($identifier);
		if (count($info)) {
			if ($info['is_deleted']) {
				return FALSE;
			} else return TRUE;
		} else return FALSE;
	}

	/**
	 * Returns the permissions of a file as an array (keys r, w) of boolean flags
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @return array
	 */
	public function getFilePermissions(\TYPO3\CMS\Core\Resource\FileInterface $file) {
		return array(
			'r' => TRUE,
			'w' => TRUE
		);
	}

	/**
	 * Returns the permissions of a folder as an array (keys r, w) of boolean flags
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return array
	 */
	public function getFolderPermissions(\TYPO3\CMS\Core\Resource\Folder $folder) {
		return array(
			'r' => TRUE,
			'w' => TRUE
		);
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
	 * Copies a file to a temporary path and returns that path.
	 *
	 * @param string $fileIdentifier
	 * @return string The temporary path
	 * @throws \RuntimeException
	 */
	protected function copyFileToTemporaryPath($fileIdentifier) {
		// We have to download the file first
		$temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
		$handle = fopen($temporaryPath, 'w+b');
		$this->dropboxClient->getFile($fileIdentifier, $handle);
		fclose($handle);
		return $temporaryPath;
	}

}