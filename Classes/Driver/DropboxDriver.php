<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Driver;

use Spatie\Dropbox\Client;
use Spatie\Dropbox\Exceptions\BadRequest;
use StefanFroemken\Dropbox\Service\AutoRefreshingDropboxTokenService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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

    protected ?Client $dropboxClient = null;

    protected FlashMessageService $flashMessageService;

    protected array $settings = [];

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
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('dropbox');

        if (!empty($this->configuration['refreshToken']) && !empty($this->configuration['appKey'])) {
            $this->dropboxClient = new Client(
                GeneralUtility::makeInstance(
                    AutoRefreshingDropboxTokenService::class,
                    $this->configuration['refreshToken'],
                    $this->configuration['appKey']
                )
            );
        } else {
            $this->dropboxClient = null;
        }

        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
    }

    public function getCapabilities(): int
    {
        // If PUBLIC is available, each file will initiate a request to Dropbox-Api to retrieve a public share link
        // this is extremely slow.

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
        $this->dropboxClient->createFolder($parentFolderIdentifier . $newFolderName);
        $newIdentifier = $parentFolderIdentifier . $newFolderName . '/';
        $this->cache->flush();

        return $newIdentifier;
    }

    public function renameFolder($folderIdentifier, $newName): array
    {
        $folderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($folderIdentifier);
        $newName = $this->sanitizeFileName($newName);

        $targetIdentifier = PathUtility::dirname($folderIdentifier) . '/' . $newName;
        $targetIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetIdentifier);

        // dropbox don't like slashes at the end of identifier
        $this->dropboxClient->move(rtrim($folderIdentifier, '/'), rtrim($targetIdentifier, '/'));

        $this->cache->flush();

        return [];
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false): bool
    {
        try {
            $this->dropboxClient->delete($folderIdentifier === '/' ?: rtrim($folderIdentifier, '/'));
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

        $info = $this->getMetaData($parentFolderIdentifier);

        if (isset($info['files'])) {
            foreach ($info['files'] as $files) {
                if ($files['path_display'] === '/' . trim($fileIdentifier, '/')) {
                    return true;
                }
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

        $info = $this->getMetaData($parentFolderIdentifier);
        if (isset($info['folders'])) {
            foreach ($info['folders'] as $folder) {
                if ($folder['path_display'] === '/' . trim($folderIdentifier, '/')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isFolderEmpty($folderIdentifier): bool
    {
        $info = $this->getMetaData($folderIdentifier);

        return !isset($info['folders']) && !isset($info['files']);
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): string
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;

        $this->dropboxClient->upload(
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

        $this->dropboxClient->upload(
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
        $this->dropboxClient->copy($fileIdentifier, $targetFileIdentifier);
        $this->cache->flush();

        return $targetFileIdentifier;
    }

    public function renameFile($fileIdentifier, $newName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $newName = $this->sanitizeFileName($newName);

        $targetIdentifier = PathUtility::dirname($fileIdentifier) . '/' . $newName;
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);

        $this->dropboxClient->move($fileIdentifier, $targetIdentifier);

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
            $this->dropboxClient->delete($fileIdentifier);
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
        $this->dropboxClient->move($fileIdentifier, $targetFileIdentifier);

        $this->cache->flush();

        return $targetFileIdentifier;
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): array
    {
        $sourceFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($sourceFolderIdentifier);
        $targetFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier . '/' . $newFolderName);

        // dropbox don't like slashes at the end of identifier
        $this->dropboxClient->move(rtrim($sourceFolderIdentifier, '/'), rtrim($targetFolderIdentifier, '/'));

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
        $this->dropboxClient->copy(rtrim($sourceFolderIdentifier, '/'), rtrim($targetFolderIdentifier, '/'));

        $this->cache->flush();

        return true;
    }

    public function getFileContents($fileIdentifier): string
    {
        return stream_get_contents($this->dropboxClient->download($fileIdentifier));
    }

    public function setFileContents($fileIdentifier, $contents): int
    {
        $response = $this->dropboxClient->upload(
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
        fwrite($handle, stream_get_contents($this->dropboxClient->download($identifier)));
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
        $identifier = $this->canonicalizeAndCheckFileIdentifier($containerPath . PathUtility::basename($fileIdentifier));
        $info = $this->getMetaData($fileIdentifier);
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $info['client_modified']);
        switch ($property) {
            case 'size':
                return (string)$info['size'];
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
        $info = $this->getMetaData($folderIdentifier);

        if (isset($info['files']) && is_array($info['files'])) {
            foreach ($info['files'] as $folder) {
                $files[] = $folder['path_display'];
            }
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
        $info = $this->getMetaData($folderIdentifier);

        if (isset($info['folders']) && is_array($info['folders'])) {
            foreach ($info['folders'] as $folder) {
                $folders[] = $folder['path_display'] . '/';
            }
        }

        return $folders;
    }

    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []): int
    {
        $info = $this->getMetaData($folderIdentifier);

        return count($info['files'] ?? []);
    }

    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        $info = $this->getMetaData($folderIdentifier);

        return count($info['folders'] ?? []);
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

    public function getMetaData(string $path): array
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
                    $info = $this->dropboxClient->getMetadata($path);
                } else {
                    $info['.tag'] = 'folder';
                }

                if ($info['.tag'] === 'folder') {
                    foreach ($this->dropboxClient->listFolder($path)['entries'] ?? [] as $entry) {
                        if ($entry['.tag'] === 'file') {
                            $info['files'][] = $entry;
                            $this->cacheMetaData($entry);
                        } else {
                            $info['folders'][] = $entry;
                        }
                    }
                }

                $this->cache->set($cacheKey, $info);
            }
        } catch (\Exception $exception) {
            // if something crashes return an empty array
            $info = [];
        }

        return $info;
    }

    protected function cacheMetaData(array $metaData): void
    {
        // Do not cache meta data on invalid mata data
        if (!array_key_exists('path_display', $metaData)) {
            return;
        }

        $cacheIdentifier = $this->getCacheIdentifierForPath((string)$metaData['path_display']);
        if (!$this->cache->has($cacheIdentifier)) {
            $this->cache->set($cacheIdentifier, $metaData);
        }
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
        return $this->getMetaData($identifier) !== [];
    }

    /*
     * Copies a file to a temporary path and returns that path.
     */
    protected function copyFileToTemporaryPath(string $fileIdentifier): string
    {
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        try {
            file_put_contents($temporaryPath, stream_get_contents($this->dropboxClient->download($fileIdentifier)));
        } catch (BadRequest $badRequest) {
            $this->addFlashMessage(
                'The file meta extraction has been interrupted, because file has been removed in the meanwhile.',
                'File Meta Extraction aborted',
                AbstractMessage::INFO
            );

            return '';
        }

        return $temporaryPath;
    }

    public function addFlashMessage(string $message, string $title = '', int $severity = AbstractMessage::OK): void
    {
        // We activate storeInSession, so that messages can be displayed when click on Save&Close button.
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
            true
        );

        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    protected function getFlashMessageQueue(): FlashMessageQueue
    {
        return $this->flashMessageService->getMessageQueueByIdentifier();
    }
}
