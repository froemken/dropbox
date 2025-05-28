<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Extractor;

use StefanFroemken\Dropbox\Client\DropboxClient;
use StefanFroemken\Dropbox\Client\DropboxClientFactory;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;

/**
 * Special Image Extractor to extract width and height from Dropbox
 * as this information was not delivered by Dropbox API
 */
class ImageExtractor implements ExtractorInterface
{
    private DropboxClientFactory $dropboxClientFactory;

    public function __construct(DropboxClientFactory $dropboxClientFactory)
    {
        $this->dropboxClientFactory = $dropboxClientFactory;
    }

    /**
     * Returns an array of supported file types
     */
    public function getFileTypeRestrictions(): array
    {
        return [FileType::IMAGE];
    }

    /**
     * Get all supported DriverClasses
     * empty array indicates no restrictions
     */
    public function getDriverRestrictions(): array
    {
        return ['dropbox'];
    }

    /**
     * Returns the data priority of the extraction Service
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Returns the execution priority of the extraction Service
     */
    public function getExecutionPriority(): int
    {
        return 50;
    }

    /**
     * Checks if the given file can be processed by this Extractor
     */
    public function canProcess(File $file): bool
    {
        return in_array($file->getExtension(), ['jpg', 'jpeg', 'bmp', 'png', 'gif'], true);
    }

    /**
     * The actual processing TASK
     * Should return an array with database properties for sys_file_metadata to write
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        return $this->getDimensions(
            $this->getDropboxClient($file)->getImageMetadata($file->getIdentifier())
        );
    }

    private function getDimensions(array $metaData): array
    {
        try {
            return ArrayUtility::getValueByPath($metaData, 'media_info/metadata/dimensions');
        } catch (\RuntimeException | MissingArrayPathException $exception) {
            return [
                'width' => 0,
                'height' => 0,
            ];
        }
    }

    private function getDropboxClient(File $file): DropboxClient
    {
        return $this->dropboxClientFactory->createByResourceStorage(
            $file->getStorage()
        );
    }
}
