<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Upgrade;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/*
 * Upgrade Wizard to rename Dropbox identifier "fal_dropbox" to "dropbox" in sys_file_storage
 */
class RenameExtensionKeyUpgrade implements UpgradeWizardInterface
{
    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf.php class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'dropboxRenameExtensionKey';
    }

    public function getTitle(): string
    {
        return '[dropbox] Rename FAL storage identifier to "dropbox"';
    }

    public function getDescription(): string
    {
        return '[dropbox] Rename FAL storage identifier to "dropbox"';
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        $amountOfMigratedRecords = (int)$queryBuilder
            ->count('*')
            ->execute()
            ->fetchColumn();

        return $amountOfMigratedRecords !== 0;
    }

    /**
     * Performs the accordant updates.
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        $statement = $queryBuilder
            ->select('sfs.uid')
            ->execute();

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_storage');
        while ($sysFileStorageRecord = $statement->fetch()) {
            $connection->update(
                'sys_file_storage',
                [
                    'driver' => 'dropbox',
                ],
                [
                    'uid' => (int)$sysFileStorageRecord['uid'],
                ]
            );
        }

        return true;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_storage');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->from('sys_file_storage', 'sfs')
            ->where(
                $queryBuilder->expr()->eq(
                    'driver',
                    $queryBuilder->createNamedParameter('fal_dropbox')
                )
            );
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
