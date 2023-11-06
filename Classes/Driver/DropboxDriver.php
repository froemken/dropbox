<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Driver;

use Spatie\Dropbox\Exceptions\BadRequest;
use StefanFroemken\Dropbox\Client\DropboxClient;
use StefanFroemken\Dropbox\Client\DropboxClientFactory;
use StefanFroemken\Dropbox\Domain\Factory\PathInfoFactory;
use StefanFroemken\Dropbox\Domain\Model\FilePathInfo;
use StefanFroemken\Dropbox\Domain\Model\FolderPathInfo;
use StefanFroemken\Dropbox\Domain\Model\InvalidPathInfo;
use StefanFroemken\Dropbox\Domain\Model\PathInfoInterface;
use StefanFroemken\Dropbox\Helper\FlashMessageHelper;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class which contains all methods to arrange files and folders
 */
class DropboxDriver extends AbstractDriver
{
    protected FrontendInterface $cache;

    protected DropboxClient $dropboxClient;

    /**
     * A list of all supported hash algorithms, written all lower case.
     *
     * @var array
     */
    protected $supportedHashAlgorithms = ['sha1', 'md5'];

    public function processConfiguration(): void
    {
        // no need to configure something.
    }

    public function initialize(): void
    {
        $this->cache = $this->getCacheManager()->getCache('dropbox');
        $this->dropboxClient = $this->getDropboxClientFactory()->createByConfiguration($this->configuration);
    }

    public function getCapabilities(): int
    {
        // Do not allow PUBLIC here, as each file will initiate a request to Dropbox-Api to retrieve a public share
        // link which is extremely slow.

        return ResourceStorageInterface::CAPABILITY_BROWSABLE + ResourceStorageInterface::CAPABILITY_WRITABLE;
    }

    public function mergeConfigurationCapabilities($capabilities): int
    {
        $this->capabilities &= $capabilities;

        return $this->capabilities;
    }

    public function getRootLevelFolder(): string
    {
        return '/';
    }

    public function getDefaultFolder(): string
    {
        $identifier = '/user_upload/';
        $createFolder = !$this->folderExists($identifier);
        if ($createFolder === true) {
            $identifier = $this->createFolder('user_upload');
        }

        return $identifier;
    }

    public function getParentFolderIdentifierOfIdentifier($fileIdentifier): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);

        return rtrim(GeneralUtility::fixWindowsFilePath(PathUtility::dirname($fileIdentifier)), '/') . '/';
    }

    /**
     * This driver is marked as non-public, so this will never be called:
     */
    public function getPublicUrl($identifier): string
    {
        return '';
    }

    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): string
    {
        $newFolderName = trim($newFolderName, '/');
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $this->dropboxClient->getClient()->createFolder($parentFolderIdentifier . $newFolderName);
        $newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
        $this->cache->flush();

        return $newIdentifier;
    }

    public function renameFolder($folderIdentifier, $newName): array
    {
        throw new \Exception(
            'Renaming is not implemented in EXT:dropbox as every file and folder has to be retrieved ' .
            'recursively, checked for existance, updated in sys_file and requests new thumbnails. That operation ' .
            'would need more time than configured in your PHP settings. Sorry.',
            1699301573
        );
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false): bool
    {
        try {
            $this->dropboxClient->getClient()->delete(
                $folderIdentifier === '/' ?: rtrim($folderIdentifier, '/')
            );
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function fileExists($fileIdentifier): bool
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
            PathUtility::dirname($fileIdentifier)
        );

        $pathInfo = $this->getPathInfo($parentFolderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return false;
        }

        $this->initializeFolder($pathInfo);

        foreach ($pathInfo->getFiles() as $filePathInfo) {
            if ($filePathInfo->getPath() === '/' . trim($fileIdentifier, '/')) {
                return true;
            }
        }

        return false;
    }

    public function folderExists($folderIdentifier): bool
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier(
            PathUtility::dirname($folderIdentifier)
        );

        // Root folder always exist
        if ($folderIdentifier === '/') {
            return true;
        }

        $pathInfo = $this->getPathInfo($parentFolderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return false;
        }

        $this->initializeFolder($pathInfo);

        foreach ($pathInfo->getFolders() as $folderPathInfo) {
            if ($folderPathInfo->getPath() === '/' . trim($folderIdentifier, '/')) {
                return true;
            }
        }

        return false;
    }

    public function isFolderEmpty($folderIdentifier): bool
    {
        $pathInfo = $this->getPathInfo($folderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return false;
        }

        $this->initializeFolder($pathInfo);

        return $pathInfo->isEmpty();
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): string
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;

        $this->dropboxClient->getClient()->upload(
            $newFileIdentifier,
            file_get_contents($localFilePath),
            'overwrite'
        );

        if ($removeOriginal) {
            unlink($localFilePath);
        }

        $this->cache->flush();

        return $newFileIdentifier;
    }

    public function createFile($fileName, $parentFolderIdentifier): string
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $fileIdentifier =  $this->canonicalizeAndCheckFileIdentifier(
            $parentFolderIdentifier . $this->sanitizeFileName(ltrim($fileName, '/'))
        );

        $this->dropboxClient->getClient()->upload(
            $fileIdentifier,
            ''
        );

        $this->cache->flush();

        return $fileIdentifier;
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $fileName);

        // dropbox don't like slashes at the end of identifier
        $this->dropboxClient->getClient()->copy($fileIdentifier, $targetFileIdentifier);
        $this->cache->flush();

        return $targetFileIdentifier;
    }

    public function renameFile($fileIdentifier, $newName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $newName = $this->sanitizeFileName($newName);

        $targetIdentifier = PathUtility::dirname($fileIdentifier) . '/' . $newName;
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);

        $this->dropboxClient->getClient()->move($fileIdentifier, $targetIdentifier);

        $this->cache->flush();

        return $targetIdentifier;
    }

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
        }

        return false;
    }

    public function deleteFile($fileIdentifier): bool
    {
        try {
            $this->dropboxClient->getClient()->delete($fileIdentifier);
            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    public function hash($fileIdentifier, $hashAlgorithm): string
    {
        if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms, true)) {
            throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
        }

        switch ($hashAlgorithm) {
            case 'sha1':
                $hash = sha1($fileIdentifier);
                break;
            case 'md5':
                $hash = md5($fileIdentifier);
                break;
            default:
                throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1329644451);
        }

        return $hash;
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $newFileName);

        // dropbox don't like slashes at the end of identifier
        $this->dropboxClient->getClient()->move($fileIdentifier, $targetFileIdentifier);

        $this->cache->flush();

        return $targetFileIdentifier;
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): array
    {
        $sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);

        // dropbox don't like slashes at the end of identifier
        $this->dropboxClient->getClient()->move(
            rtrim($sourceFolderIdentifier, '/'),
            rtrim($targetFolderIdentifier, '/')
        );

        $this->cache->flush();

        return [];
    }

    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): bool
    {
        $sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);

        if ($sourceFolderIdentifier === $targetFolderIdentifier) {
            return false;
        }

        // dropbox don't like slashes at the end of identifier
        $this->dropboxClient->getClient()->copy(
            rtrim($sourceFolderIdentifier, '/'),
            rtrim($targetFolderIdentifier, '/')
        );

        $this->cache->flush();

        return true;
    }

    public function getFileContents($fileIdentifier): string
    {
        return stream_get_contents($this->dropboxClient->getClient()->download($fileIdentifier));
    }

    public function setFileContents($fileIdentifier, $contents): int
    {
        $response = $this->dropboxClient->getClient()->upload(
            $fileIdentifier,
            $contents,
            'overwrite'
        );

        $this->cache->flush();

        return (int)$response['size'];
    }

    public function fileExistsInFolder($fileName, $folderIdentifier): bool
    {
        $fileIdentifier = $folderIdentifier . $fileName;

        return $this->fileExists($fileIdentifier);
    }

    public function folderExistsInFolder($folderName, $folderIdentifier): bool
    {
        $identifier = $this->canonicalizeAndCheckFolderIdentifier(
            $folderIdentifier . '/' . $folderName
        );

        return $this->folderExists($identifier);
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true): string
    {
        return $this->copyFileToTemporaryPath($fileIdentifier);
    }

    public function getPermissions($identifier): array
    {
        // We are authenticated as a valid dropbox user
        // so all files are readable and writeable
        return [
            'r' => true,
            'w' => true,
        ];
    }

    public function dumpFileContents($identifier): void
    {
        $handle = fopen('php://output', 'wb');
        fwrite($handle, stream_get_contents($this->dropboxClient->getClient()->download($identifier)));
        fclose($handle);
    }

    public function isWithin($folderIdentifier, $identifier): bool
    {
        $folderIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderIdentifier);
        $entryIdentifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        if ($folderIdentifier === $entryIdentifier) {
            return true;
        }

        // File identifier canonicalization will not modify a single slash, so
        // we must not append another slash in that case.
        if ($folderIdentifier !== '/') {
            $folderIdentifier .= '/';
        }

        return \str_starts_with($entryIdentifier, $folderIdentifier);
    }

    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = []): array
    {
        $dirPath = PathUtility::dirname($fileIdentifier);
        $dirPath = $this->canonicalizeAndCheckFolderIdentifier($dirPath);
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size', 'atime', 'atime', 'mtime', 'ctime', 'mimetype', 'name',
                'identifier', 'identifier_hash', 'storage', 'folder_hash',
            ];
        }

        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificFileInformation($fileIdentifier, $dirPath, $property);
        }

        return $fileInformation;
    }

    public function getSpecificFileInformation($fileIdentifier, $containerPath, $property): string
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier(
            $containerPath . PathUtility::basename($fileIdentifier)
        );
        $pathInfo = $this->getPathInfo($fileIdentifier);
        if (!$pathInfo instanceof FilePathInfo) {
            return '';
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $pathInfo->getClientModified());
        switch ($property) {
            case 'size':
                return $pathInfo->getSize();
            case 'mtime':
            case 'ctime':
            case 'atime':
                return $dateTime->format('U');
            case 'name':
                return PathUtility::basename($fileIdentifier);
            case 'mimetype':
                $parts = GeneralUtility::split_fileref($fileIdentifier);
                if (in_array($parts['fileext'], ['jpg', 'jpeg', 'bmp', 'svg', 'ico', 'pdf', 'png', 'tiff'])) {
                    return 'image/' . $parts['fileext'];
                }
                return 'text/' . $parts['fileext'];
            case 'identifier':
                return $identifier;
            case 'storage':
                return (string)$this->storageUid;
            case 'identifier_hash':
                return $this->hashIdentifier($identifier);
            case 'folder_hash':
                return $this->hashIdentifier($this->getParentFolderIdentifierOfIdentifier($identifier));
            default:
                throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property));
        }
    }

    public function getFolderInfoByIdentifier($folderIdentifier): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);

        return [
            'identifier' => $folderIdentifier,
            'name' => PathUtility::basename($folderIdentifier),
            'storage' => $this->storageUid,
            // Costs too much time to retrieve the real values for mtime and ctime
            // They MUST have a value. Else Fluid redering breaks.
            'mtime' => time(),
            'ctime' => time(),
        ];
    }

    public function getFileInFolder($fileName, $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
    }

    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        $files = [];
        $pathInfo = $this->getPathInfo($folderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return $files;
        }

        $this->initializeFolder($pathInfo);

        foreach ($pathInfo->getFiles() as $filePathInfo) {
            $files[] = $filePathInfo->getPath();
        }

        return $files;
    }

    public function getFolderInFolder($folderName, $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier . '/' . $folderName);
    }

    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        $folders = [];
        $pathInfo = $this->getPathInfo($folderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return $folders;
        }

        $this->initializeFolder($pathInfo);

        foreach ($pathInfo->getFolders() as $folderPathInfo) {
            $folders[] = $folderPathInfo->getPath() . '/';
        }

        return $folders;
    }

    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []): int
    {
        $pathInfo = $this->getPathInfo($folderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return 0;
        }

        $this->initializeFolder($pathInfo);

        return $pathInfo->getFiles()->count();
    }

    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        $pathInfo = $this->getPathInfo($folderIdentifier);
        if (!$pathInfo instanceof FolderPathInfo) {
            return 0;
        }

        $this->initializeFolder($pathInfo);

        return $pathInfo->getFolders()->count();
    }

    /**
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

    protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier): string
    {
        if ($folderIdentifier === '/') {
            return $folderIdentifier;
        }

        return rtrim($this->canonicalizeAndCheckFileIdentifier($folderIdentifier), '/') . '/';
    }

    public function getPathInfo(string $path): PathInfoInterface
    {
        $pathInfoFactory = $this->getPathInfoFactory();
        $path = $path === '/' ? '/' : rtrim($path, '/');

        // Early return, if pathIfo was found in cache
        $cacheKey = $this->getCacheIdentifierForPath($path);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        try {
            // getMetadata on root (/) will return in BadRequest.
            // We have to build up root folder on our own
            if ($path === '/') {
                $pathInfo = $pathInfoFactory->createPathInfoForRootFolder();
            } else {
                $parameters = [
                    'path' => $path,
                    'include_media_info' => true,
                ];
                $pathInfo = $pathInfoFactory->createPathInfo(
                    $this->dropboxClient->getClient()->rpcEndpointRequest('files/get_metadata', $parameters)
                );
            }

            $this->cache->set($cacheKey, $pathInfo);
        } catch (\Exception $exception) {
            $pathInfo = new InvalidPathInfo();
        }

        return $pathInfo;
    }

    protected function initializeFolder(FolderPathInfo $folderPathInfo): void
    {
        if ($folderPathInfo->isInitialized()) {
            return;
        }

        $listFolderResponse = $this->dropboxClient->getClient()->listFolder(
            $folderPathInfo->getPath()
        );

        foreach ($listFolderResponse['entries'] ?? [] as $metaData) {
            $entry = $this->getPathInfoFactory()->createPathInfo($metaData);
            $folderPathInfo->addEntry($entry);
            // Add cache entry for each contained file or uninitialized folder (without containing files/folders).
            // This cache will speed up simple ifExists calls.
            $this->cachePathInfo($entry);
        }

        // Update cache entry for folder with all its files and folders
        $this->cachePathInfo($folderPathInfo);
    }

    protected function cachePathInfo(PathInfoInterface $pathInfo): void
    {
        // Do not cache info for invalid path
        if ($pathInfo instanceof InvalidPathInfo) {
            return;
        }

        // Do not cache info for empty path
        if ($pathInfo->getPath() === '') {
            return;
        }

        // Update cache, regardless if already set or not.
        $this->cache->set(
            $this->getCacheIdentifierForPath($pathInfo->getPath()),
            $pathInfo
        );
    }

    protected function getCacheIdentifierForPath(string $path): string
    {
        return sha1($this->storageUid . ':' . trim($path, '/'));
    }

    /**
     * Checks if a resource exists - does not care for the type (file or folder).
     */
    public function resourceExists(string $identifier): bool
    {
        if ($identifier === '') {
            throw new \InvalidArgumentException('Resource path cannot be empty');
        }

        $identifier = $identifier === '/' ? $identifier : rtrim($identifier, '/');
        $pathInfo = $this->getPathInfo($identifier);

        return $pathInfo instanceof FilePathInfo || $pathInfo instanceof FolderPathInfo;
    }

    /*
     * Copies a file to a temporary path and returns that path.
     */
    protected function copyFileToTemporaryPath(string $fileIdentifier): string
    {
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);

        try {
            file_put_contents(
                $temporaryPath,
                stream_get_contents(
                    $this->dropboxClient->getClient()->download($fileIdentifier)
                )
            );
        } catch (BadRequest $badRequest) {
            $this->getFlashMessageHelper()->addFlashMessage(
                'The file meta extraction has been interrupted, because file has been removed in the meanwhile.',
                'File Meta Extraction aborted',
                AbstractMessage::INFO
            );

            return '';
        }

        return $temporaryPath;
    }

    /**
     * DropboxDriver was called with constructor arguments. So, no DI possible.
     * We have to instantiate the CacheManager on our own.
     */
    private function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * DropboxDriver was called with constructor arguments. So, no DI possible.
     * We have to instantiate the FlashMessageHelper on our own.
     */
    private function getFlashMessageHelper(): FlashMessageHelper
    {
        return GeneralUtility::makeInstance(FlashMessageHelper::class);
    }

    /**
     * DropboxDriver was called with constructor arguments. So, no DI possible.
     * We have to instantiate the DropboxClientFactory on our own.
     */
    private function getDropboxClientFactory(): DropboxClientFactory
    {
        return GeneralUtility::makeInstance(DropboxClientFactory::class);
    }

    /**
     * DropboxDriver was called with constructor arguments. So, no DI possible.
     * We have to instantiate the PathInfoFactory on our own.
     */
    private function getPathInfoFactory(): PathInfoFactory
    {
        // Prevent calling GU::makeINstance multiple times
        // Change, if DI can be used for this class
        static $pathInfoFactory = null;

        if ($pathInfoFactory === null) {
            $pathInfoFactory = GeneralUtility::makeInstance(PathInfoFactory::class);
        }

        return $pathInfoFactory;
    }
}
