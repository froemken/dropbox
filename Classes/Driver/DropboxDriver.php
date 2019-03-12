<?php
declare(strict_types = 1);
namespace SFroemken\FalDropbox\Driver;

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

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use Kunnu\Dropbox\Models\FileMetadata;
use Kunnu\Dropbox\Models\FolderMetadata;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class which contains all methods to arrange files and folders
 */
class DropboxDriver extends AbstractDriver
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;
    
    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;
    
    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $cache;
    
    /**
     * @var Dropbox
     */
    protected $dropbox;
    
    /**
     * @var array
     */
    protected $settings = [];

    /**
     * A list of all supported hash algorithms, written all lower case.
     *
     * @var array
     */
    protected $supportedHashAlgorithms = ['sha1', 'md5'];

    /**
     * processes the configuration, should be overridden by subclasses
     */
    public function processConfiguration()
    {
        // no need to configure something.
    }
    
    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     */
    public function setStorageUid($storageUid)
    {
        $this->storageUid = (int)$storageUid;
    }
    
    /**
     * Initializes this object. This is called by the storage after the driver
     * has been attached.
     *
     * @throws NoSuchCacheException
     */
    public function initialize()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->cacheManager = $this->objectManager->get(CacheManager::class);
        $this->cache = $this->cacheManager->getCache('fal_dropbox');
        if (!empty($this->configuration['accessToken'])) {
            $this->dropbox = new Dropbox(
                new DropboxApp('', '', $this->configuration['accessToken'])
            );
        } else {
            $this->dropbox = null;
        }
    }
    
    /**
     * Returns the capabilities of this driver.
     *
     * @return int
     * @see Storage::CAPABILITY_* constants
     */
    public function getCapabilities(): int
    {
        // if PUBLIC is available, each file will initiate a request to Dropbox-Api to retrieve a public share link
        // this is extremely slow.
        
        // return ResourceStorage::CAPABILITY_BROWSABLE + ResourceStorage::CAPABILITY_PUBLIC + ResourceStorage::CAPABILITY_WRITABLE;
        return ResourceStorage::CAPABILITY_BROWSABLE + ResourceStorage::CAPABILITY_WRITABLE;
    }

    /**
     * Merges the capabilites merged by the user at the storage
     * configuration into the actual capabilities of the driver
     * and returns the result.
     *
     * @param int $capabilities
     * @return int
     */
    public function mergeConfigurationCapabilities($capabilities): int
    {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }
    
    /**
     * Returns the identifier of the root level folder of the storage.
     *
     * @return string
     */
    public function getRootLevelFolder(): string
    {
        return '/';
    }

    /**
     * Returns identifier of the default folder new files should be put into.
     *
     * @return string
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function getDefaultFolder(): string
    {
        $identifier = '/user_upload/';
        $createFolder = !$this->folderExists($identifier);
        if ($createFolder === true) {
            $identifier = $this->createFolder('user_upload');
        }
        return $identifier;
    }

    /**
     * Returns the identifier of the folder the file resides in
     *
     * @param string $fileIdentifier
     * @return mixed
     * @throws InvalidPathException
     */
    public function getParentFolderIdentifierOfIdentifier($fileIdentifier): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        return rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/';
    }
    
    /**
     * Returns the public URL to a file.
     * Either fully qualified URL or relative to PATH_site (rawurlencoded).
     *
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier): string
    {
        // This driver is marked as non public, so this will never be called:
        // return $this->dropbox->createShareableLink($identifier);
        return '';
    }
    
    /**
     * Creates a folder, within a parent folder.
     * If no parent folder is given, a root level folder will be created
     *
     * @param string $newFolderName
     * @param string $parentFolderIdentifier
     * @param bool $recursive
     * @return string the Identifier of the new folder
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): string
    {
        $newFolderName = trim($newFolderName, '/');
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $this->dropbox->createFolder($parentFolderIdentifier . $newFolderName);
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
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function renameFolder($folderIdentifier, $newName): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $newName = $this->sanitizeFileName($newName);
        
        $targetIdentifier = PathUtility::dirname($folderIdentifier) . '/' . $newName;
        $targetIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetIdentifier);
        
        // dropbox don't like slashes at the end of identifier
        $this->dropbox->move(rtrim($folderIdentifier, '/'), rtrim($targetIdentifier, '/'));
        $this->cache->flush();
        return [];
    }
    
    /**
     * Removes a folder in filesystem.
     *
     * @param string $folderIdentifier
     * @param bool $deleteRecursively
     * @return bool
     */
    public function deleteFolder($folderIdentifier, $deleteRecursively = false): bool
    {
        $identifier = $folderIdentifier === '/' ?: rtrim($folderIdentifier, '/');
        try {
            $this->dropbox->delete($identifier);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    
    /**
     * Checks if a file exists.
     *
     * @param string $fileIdentifier
     * @return bool
     * @throws InvalidPathException
     */
    public function fileExists($fileIdentifier): bool
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
            PathUtility::dirname($fileIdentifier)
        );
        $info = $this->getMetaData($parentFolderIdentifier);
        if (isset($info['files'])) {
            foreach($info['files'] as $files) {
                if ($files['path_display'] === '/' . trim($fileIdentifier, '/')) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Checks if a folder exists.
     *
     * @param string $folderIdentifier
     * @return bool
     * @throws InvalidPathException
     */
    public function folderExists($folderIdentifier): bool
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
            PathUtility::dirname($folderIdentifier)
        );
        
        // root folder will always exist
        if ($folderIdentifier === '/') {
            return true;
        }
        
        $info = $this->getMetaData($parentFolderIdentifier);
        if (isset($info['folders'])) {
            foreach($info['folders'] as $folder) {
                if ($folder['path_display'] === '/' . trim($folderIdentifier, '/')) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool true if there are no files and folders within $folder
     */
    public function isFolderEmpty($folderIdentifier): bool
    {
        $info = $this->getMetaData($folderIdentifier);
        return !isset($info['folders']) && !isset($info['files']);
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
     * @throws InvalidPathException
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): string
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;
        
        $this->dropbox->simpleUpload(
            $localFilePath,
            $newFileIdentifier,
            ['mode' => 'overwrite']
        );
        
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
     * @throws InvalidPathException
     */
    public function createFile($fileName, $parentFolderIdentifier): string
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $fileIdentifier =  $this->canonicalizeAndCheckFileIdentifier(
            $parentFolderIdentifier . $this->sanitizeFileName(ltrim($fileName, '/'))
        );
        
        // Dropbox can not create files. So we have to create an empty file locally and upload it to Dropbox
        $localFilePath = GeneralUtility::tempnam('fal_dropbox');
        $this->dropbox->simpleUpload($localFilePath, $fileIdentifier);
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
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $fileName);
        
        // dropbox don't like slashes at the end of identifier
        $this->dropbox->copy($fileIdentifier, $targetFileIdentifier);
        $this->cache->flush();
        return $targetFileIdentifier;
    }
    
    /**
     * Renames a file in this storage.
     *
     * @param string $fileIdentifier
     * @param string $newName The target path (including the file name!)
     * @return string The identifier of the file after renaming
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function renameFile($fileIdentifier, $newName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $newName = $this->sanitizeFileName($newName);
        
        $targetIdentifier = PathUtility::dirname($fileIdentifier) . '/' . $newName;
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);
        
        $this->dropbox->move($fileIdentifier, $targetIdentifier);
        $this->cache->flush();
        return $targetIdentifier;
    }
    
    /**
     * Replaces a file with file in local file system.
     *
     * @param string $fileIdentifier
     * @param string $localFilePath
     * @return bool true if the operation succeeded
     */
    public function replaceFile($fileIdentifier, $localFilePath): bool
    {
        try {
            if (is_uploaded_file($localFilePath)) {
                $this->setFileContents(
                    $fileIdentifier,
                    file_get_contents($localFilePath)
                );
            } else {
                $parts = GeneralUtility::split_fileref($localFilePath);
                $this->renameFile($fileIdentifier, $parts['info']);
            }
            $this->cache->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Removes a file from the filesystem. This does not check if the file is
     * still used or if it is a bad idea to delete it for some other reason
     * this has to be taken care of in the upper layers (e.g. the Storage)!
     *
     * @param string $fileIdentifier
     * @return bool true if deleting the file succeeded
     */
    public function deleteFile($fileIdentifier): bool
    {
        try {
            $this->dropbox->delete($fileIdentifier);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Creates a hash for a file.
     *
     * @param string $fileIdentifier
     * @param string $hashAlgorithm The hash algorithm to use
     * @return string
     */
    public function hash($fileIdentifier, $hashAlgorithm): string
    {
        if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms)) {
            throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
        }
        switch ($hashAlgorithm) {
            case 'sha1':
                // $hash = sha1_file($fileIdentifier);
                $hash = sha1($fileIdentifier);
                break;
            case 'md5':
                // $hash = md5_file($fileIdentifier);
                $hash = md5($fileIdentifier);
                break;
            default:
                throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1329644451);
        }
        return $hash;
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
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $newFileName);
        
        // dropbox don't like slashes at the end of identifier
        $this->dropbox->move($fileIdentifier, $targetFileIdentifier);
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
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): array
    {
        $sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        
        // dropbox don't like slashes at the end of identifier
        $this->dropbox->move(rtrim($sourceFolderIdentifier, '/'), rtrim($targetFolderIdentifier, '/'));
        $this->cache->flush();
        return [];
    }
    
    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceFolderIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFolderName
     * @return bool
     * @throws InvalidPathException
     * @throws DropboxClientException
     */
    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): bool
    {
        $sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);
        if ($sourceFolderIdentifier == $targetFolderIdentifier) {
            return false;
        } else {
            // dropbox don't like slashes at the end of identifier
            $this->dropbox->copy(rtrim($sourceFolderIdentifier, '/'), rtrim($targetFolderIdentifier, '/'));
            $this->cache->flush();
            return true;
        }
    }
    
    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     * @throws DropboxClientException
     */
    public function getFileContents($fileIdentifier): string
    {
        return $this->dropbox->download($fileIdentifier)->getContents();
    }
    
    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     */
    public function setFileContents($fileIdentifier, $contents): int
    {
        $localFilePath = GeneralUtility::tempnam('fal_dropbox');
        $bytes = file_put_contents($localFilePath, $contents);
        $this->dropbox->simpleUpload(
            $localFilePath,
            $fileIdentifier,
            ['mode' => 'overwrite']
        );
        
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
     * @throws InvalidPathException
     */
    public function fileExistsInFolder($fileName, $folderIdentifier): bool
    {
        $fileIdentifier = $folderIdentifier . $fileName;
        return $this->fileExists($fileIdentifier);
    }
    
    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     * @throws InvalidPathException
     */
    public function folderExistsInFolder($folderName, $folderIdentifier): bool
    {
        $identifier = $folderIdentifier . '/' . $folderName;
        $identifier = $this->canonicalizeAndCheckFolderIdentifier($identifier);
        return $this->folderExists($identifier);
    }
    
    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to false if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     * @throws DropboxClientException
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true): string
    {
        return $this->copyFileToTemporaryPath($fileIdentifier);
    }
    
    /**
     * Returns the permissions of a file/folder as an array
     * (keys r, w) of boolean flags
     *
     * @param string $identifier
     * @return array
     */
    public function getPermissions($identifier): array
    {
        // we are authenticated as a valid dropbox user
        // so all files are readable and writeable
        return [
            'r' => true,
            'w' => true
        ];
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @throws DropboxClientException
     */
    public function dumpFileContents($identifier)
    {
        $handle = fopen('php://output', 'w');
        fputs($handle, $this->dropbox->download($identifier)->getContents());
        $this->dropbox->download(
            $identifier,
            DropboxFile::createByStream(
                'php://output',
                $handle,
                DropboxFile::MODE_WRITE
            )
        );
        fclose($handle);
    }
    
    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return true if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool true if $content is within or matches $folderIdentifier
     * @throws InvalidPathException
     */
    public function isWithin($folderIdentifier, $identifier): bool
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
        $entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        if ($folderIdentifier === $entryIdentifier) {
            return true;
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
     * @throws InvalidPathException
     */
    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = []): array
    {
        $dirPath = PathUtility::dirname($fileIdentifier);
        $dirPath = $this->canonicalizeAndCheckFolderIdentifier($dirPath);
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size', 'atime', 'atime', 'mtime', 'ctime', 'mimetype', 'name',
                'identifier', 'identifier_hash', 'storage', 'folder_hash'
            ];
        }
        $fileInformation = [];
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
     * @return bool|int|string
     * @throws \InvalidArgumentException
     * @throws InvalidPathException
     */
    public function getSpecificFileInformation($fileIdentifier, $containerPath, $property)
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));
        $info = $this->getMetaData($fileIdentifier);
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $info['client_modified']);
        switch ($property) {
            case 'size':
                return $info['size'];
            case 'atime':
                return $dateTime->format('U');
            case 'mtime':
                return $dateTime->format('U');
            case 'ctime':
                return $dateTime->format('U');
            case 'name':
                return PathUtility::basename($fileIdentifier);
            case 'mimetype':
                $parts = GeneralUtility::split_fileref($fileIdentifier);
                if (in_array($parts['fileext'], ['jpg', 'jpeg', 'bmp', 'svg', 'ico', 'pdf', 'png', 'tiff'])) {
                    return 'image/' . $parts['fileext'];
                } else {
                    return 'text/' . $parts['fileext'];
                }
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
     * @throws InvalidPathException
     */
    public function getFolderInfoByIdentifier($folderIdentifier): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        
        return [
            'identifier' => $folderIdentifier,
            'name' => PathUtility::basename($folderIdentifier),
            'storage' => $this->storageUid
        ];
    }
    
    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     * @throws InvalidPathException
     */
    public function getFileInFolder($fileName, $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
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
     * @param bool $sortRev true to indicate reverse sorting (last to first)
     * @return array of FileIdentifiers
     */
    public function getFilesInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $filenameFilterCallbacks = [], $sort = '', $sortRev = false): array
    {
        $files = [];
        $info = $this->getMetaData($folderIdentifier);
        if (isset($info['files']) && is_array($info['files'])) {
            foreach ($info['files'] as $folder) {
                $files[] = $folder['path_display'];
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
     * @throws InvalidPathException
     */
    public function getFolderInFolder($folderName, $folderIdentifier): string
    {
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
     * @param bool $sortRev true to indicate reverse sorting (last to first)
     * @return array of Folder Identifier
     */
    public function getFoldersInFolder($folderIdentifier, $start = 0, $numberOfItems = 0, $recursive = false, array $folderNameFilterCallbacks = [], $sort = '', $sortRev = false): array
    {
        $folders = [];
        $info = $this->getMetaData($folderIdentifier);
        if (isset($info['folders']) && is_array($info['folders'])) {
            foreach ($info['folders'] as $folder) {
                $folders[] = $folder['path_display'] . '/';
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
     * @return int Number of files in folder
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []): int
    {
        $info = $this->getMetaData($folderIdentifier);
        return isset($info['files']) ? count($info['files']) : 0;
    }
    
    /**
     * Returns the number of folders inside the specified path
     *
     * @param string  $folderIdentifier
     * @param bool $recursive
     * @param array   $folderNameFilterCallbacks callbacks for filtering the items
     * @return int Number of folders in folder
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        $info = $this->getMetaData($folderIdentifier);
        return isset($info['folders']) ? count($info['folders']) : 0;
    }
    
    /**
     * Makes sure the Path given as parameter is valid
     *
     * @param string $filePath The file path (including the file name!)
     * @return string
     * @throws InvalidPathException
     */
    protected function canonicalizeAndCheckFilePath($filePath): string
    {
        $filePath = PathUtility::getCanonicalPath($filePath);
        
        // filePath must be valid
        // Special case is required by vfsStream in Unit Test context
        if (!GeneralUtility::validPathStr($filePath)) {
            throw new InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
        }
        return $filePath;
    }
    
    /**
     * Makes sure the identifier given as parameter is valid
     *
     * @param string $fileIdentifier The file Identifier
     * @return string
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     *
     */
    protected function canonicalizeAndCheckFileIdentifier($fileIdentifier): string
    {
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
     * @throws InvalidPathException
     */
    protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier): string
    {
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
     * @return array file or folder information
     */
    public function getMetaData($path): array
    {
        $path = $path === '/' ? '/' : rtrim($path, '/');
        
        try {
            $cacheKey = $this->getCacheIdentifierForPath($path);
            if ($this->cache->has($cacheKey)) {
                $info = $this->cache->get($cacheKey);
            } else {
                $info = [];
                
                // get info for current path
                if ($path !== '/') {
                    $info = $this->dropbox->getMetadata($path)->getData();
                } else {
                    $info['.tag'] = 'folder';
                }
                
                if ($info['.tag'] === 'folder') {
                    /** @var FileMetadata|FolderMetadata $item */
                    foreach ($this->dropbox->listFolder($path)->getItems() as $item) {
                        if ($item instanceof FileMetadata) {
                            $info['files'][] = $item->getData();
                            $this->cacheMetaData($item);
                        } else {
                            $info['folders'][] = $item->getData();
                        }
                    }
                }
                
                $this->cache->set($cacheKey, $info);
            }
        } catch(\Exception $e) {
            // if something crashes return an empty array
            $info = [];
        }
        return $info;
    }
    
    /**
     * cache resource
     *
     * @param FileMetadata $metaData
     */
    protected function cacheMetaData(FileMetadata $metaData)
    {
        $cacheIdentifier = $this->getCacheIdentifierForPath($metaData->getPathDisplay());
        if (!$this->cache->has($cacheIdentifier)) {
            $this->cache->set($cacheIdentifier, $metaData->getData());
        }
    }
    
    /**
     * Returns the cache identifier for a given path.
     *
     * @param string $path
     * @return string
     */
    protected function getCacheIdentifierForPath($path): string
    {
        return sha1($this->storageUid . ':' . trim($path, '/'));
    }
    
    /**
     * Checks if a resource exists - does not care for the type (file or folder).
     *
     * @param $identifier
     * @return bool
     */
    public function resourceExists($identifier): bool
    {
        if (empty($identifier)) {
            throw new \InvalidArgumentException('Resource path cannot be empty');
        }
        $identifier = $identifier === '/' ? $identifier : rtrim($identifier, '/');
        $info = $this->getMetaData($identifier);
        if (is_array($info) && count($info)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Returns information about a file for a given file object.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return array
     * @throws InvalidPathException
     */
    public function getFileInfo(\TYPO3\CMS\Core\Resource\FileInterface $file): array
    {
        return $this->getFileInfoByIdentifier($file->getIdentifier());
    }
    
    /**
     * Copies a file to a temporary path and returns that path.
     *
     * @param string $fileIdentifier
     * @return string The temporary path
     * @throws \RuntimeException
     * @throws DropboxClientException
     */
    protected function copyFileToTemporaryPath($fileIdentifier): string
    {
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        file_put_contents($temporaryPath, $this->dropbox->download($fileIdentifier)->getContents());
        return $temporaryPath;
    }
}
