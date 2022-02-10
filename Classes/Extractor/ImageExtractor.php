<?php

declare(strict_types=1);

/*
 * This file is part of the package sfroemken/fal_dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace SFroemken\FalDropbox\Extractor;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;

/**
 * Special Image Extractor to extract width and height from Dropbox
 * as these information were not delivered by Dropbox API
 */
class ImageExtractor implements ExtractorInterface
{
    /**
     * Returns an array of supported file types
     *
     * @return array
     */
    public function getFileTypeRestrictions(): array
    {
        return [File::FILETYPE_IMAGE];
    }

    /**
     * Get all supported DriverClasses
     * empty array indicates no restrictions
     *
     * @return array
     */
    public function getDriverRestrictions(): array
    {
        return ['fal_dropbox'];
    }

    /**
     * Returns the data priority of the extraction Service
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Returns the execution priority of the extraction Service
     *
     * @return int
     */
    public function getExecutionPriority(): int
    {
        return 50;
    }

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param File $file
     * @return bool
     */
    public function canProcess(File $file): bool
    {
        return in_array($file->getExtension(), ['jpg', 'jpeg', 'bmp', 'png', 'gif'], true);
    }

    /**
     * The actual processing TASK
     * Should return an array with database properties for sys_file_metadata to write
     *
     * @param File $file
     * @param array $previousExtractedData optional, contains the array of already extracted data
     * @return array
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        // Currently Dropbox does not transfer width/height
        $localPath = $file->getForLocalProcessing();
        list($width, $height) = @getimagesize($localPath);
        // Remove file to prevent exceeding hdd quota
        unlink($localPath);
        return [
            'width' => $width,
            'height' => $height
        ];
    }
}
