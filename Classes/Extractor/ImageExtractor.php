<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Extractor;

use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;

/**
 * Special Image Extractor to extract width and height from Dropbox
 * as this information was not delivered by Dropbox API
 */
class ImageExtractor implements ExtractorInterface
{
    /**
     * Returns an array of supported file types
     */
    public function getFileTypeRestrictions(): array
    {
        return [AbstractFile::FILETYPE_IMAGE];
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
        // Currently, Dropbox does not transfer width/height
        $localPath = $file->getForLocalProcessing();
        if ($localPath === '') {
            return [];
        }

        [$width, $height] = @getimagesize($localPath);

        // Remove file to prevent exceeding hdd quota
        unlink($localPath);

        return [
            'width' => $width,
            'height' => $height
        ];
    }
}
