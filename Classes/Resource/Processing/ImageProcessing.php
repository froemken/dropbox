<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Resource\Processing;

use Psr\Http\Message\ServerRequestInterface;
use StefanFroemken\Dropbox\Client\DropboxClient;
use StefanFroemken\Dropbox\Client\DropboxClientFactory;
use StefanFroemken\Dropbox\Client\DropboxThumbnailSizes;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Dropbox Image Preview Processor
 */
class ImageProcessing implements ProcessorInterface
{
    private DropboxClientFactory $dropboxClientFactory;

    private DropboxThumbnailSizes $dropboxThumbnailSizes;

    private array $defaultConfiguration = [
        'width' => 64,
        'height' => 64,
    ];

    public function __construct(
        DropboxClientFactory $dropboxClientFactory,
        DropboxThumbnailSizes $dropboxThumbnailSizes
    ) {
        $this->dropboxClientFactory = $dropboxClientFactory;
        $this->dropboxThumbnailSizes = $dropboxThumbnailSizes;
    }

    public function canProcessTask(TaskInterface $task): bool
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $task->getSourceFile()->getStorage()->getDriverType() === 'dropbox'
            && $task->getType() === 'Image'
            && $task->getName() === 'Preview';
    }

    public function processTask(TaskInterface $task): void
    {
        $configuration = $this->preProcessConfiguration($task->getConfiguration());
        $dropboClient = $this->getDropboxClient($task->getSourceFile());

        $content = $dropboClient->getClient()->getThumbnail(
            $task->getSourceFile()->getIdentifier(),
            'jpeg',
            $this->dropboxThumbnailSizes->getThumbnailSize(
                (int)$configuration['width'],
                (int)$configuration['height']
            )
        );

        $temporaryFilePath = $this->getTemporaryFilePath($task);
        file_put_contents($temporaryFilePath, $content);

        $task->setExecuted(true);
        $imageDimensions = $this->getGraphicalFunctionsObject()->getImageDimensions($temporaryFilePath);
        $task->getTargetFile()->setName($task->getTargetFileName());
        $task->getTargetFile()->updateProperties([
            'width' => $imageDimensions[0] ?? 0,
            'height' => $imageDimensions[1] ?? 0,
            'size' => filesize($temporaryFilePath),
            'checksum' => $task->getConfigurationChecksum(),
        ]);
        $task->getTargetFile()->updateWithLocalFile($temporaryFilePath);
    }

    public function preProcessConfiguration(array $configuration): array
    {
        $configuration = array_replace($this->defaultConfiguration, $configuration);
        $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
        $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);

        return array_filter(
            $configuration,
            static function ($value, $name): bool {
                return !empty($value) && in_array($name, ['width', 'height'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Returns the path to a temporary file for processing
     */
    private function getTemporaryFilePath(TaskInterface $task): string
    {
        return GeneralUtility::tempnam('preview_', '.' . $task->getTargetFileExtension());
    }

    protected function getGraphicalFunctionsObject(): GraphicalFunctions
    {
        return GeneralUtility::makeInstance(GraphicalFunctions::class);
    }

    private function getDropboxClient(File $file): DropboxClient
    {
        return $this->dropboxClientFactory->createByResourceStorage(
            $file->getStorage()
        );
    }
}
