<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\Dropbox\Helper;

use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains methods to create FlashMessages.
 */
class FlashMessageHelper
{
    protected FlashMessageService $flashMessageService;

    public function __construct(FlashMessageService $flashMessageService)
    {
        $this->flashMessageService = $flashMessageService;
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

    /**
     * @return FlashMessage[]
     */
    public function getAllFlashMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessageQueue()->getAllMessagesAndFlush();
        }

        return $this->getFlashMessageQueue()->getAllMessages();
    }

    public function hasMessages(): bool
    {
        return !empty($this->getAllFlashMessages(false));
    }

    /**
     * @param int $severity Must be one of the constants in AbstractMessage class
     * @return FlashMessage[]
     */
    protected function getFlashMessagesBySeverity(int $severity): array
    {
        return $this->getFlashMessageQueue()->getAllMessages($severity);
    }

    /**
     * @param int $severity Must be one of the constants in AbstractMessage class
     * @return FlashMessage[]
     */
    public function getFlashMessagesBySeverityAndFlush(int $severity): array
    {
        return $this->getFlashMessageQueue()->getAllMessagesAndFlush($severity);
    }

    public function hasErrorMessages(): bool
    {
        return !empty($this->getErrorMessages(false));
    }

    /**
     * @return AbstractMessage[]
     */
    public function getErrorMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(AbstractMessage::ERROR);
        }

        return $this->getFlashMessagesBySeverity(AbstractMessage::ERROR);
    }

    public function hasWarningMessages(): bool
    {
        return !empty($this->getWarningMessages(false));
    }

    /**
     * @return AbstractMessage[]
     */
    public function getWarningMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(AbstractMessage::WARNING);
        }

        return $this->getFlashMessagesBySeverity(AbstractMessage::WARNING);
    }

    public function hasOkMessages(): bool
    {
        return !empty($this->getOkMessages(false));
    }

    /**
     * @return AbstractMessage[]
     */
    public function getOkMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(AbstractMessage::OK);
        }

        return $this->getFlashMessagesBySeverity(AbstractMessage::OK);
    }

    public function hasInfoMessages(): bool
    {
        return !empty($this->getInfoMessages(false));
    }

    /**
     * @return AbstractMessage[]
     */
    public function getInfoMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(AbstractMessage::INFO);
        }

        return $this->getFlashMessagesBySeverity(AbstractMessage::INFO);
    }

    public function hasNoticeMessages(): bool
    {
        return !empty($this->getNoticeMessages(false));
    }

    /**
     * @return AbstractMessage[]
     */
    public function getNoticeMessages(bool $flush = true): array
    {
        if ($flush) {
            return $this->getFlashMessagesBySeverityAndFlush(AbstractMessage::NOTICE);
        }

        return $this->getFlashMessagesBySeverity(AbstractMessage::NOTICE);
    }

    protected function getFlashMessageQueue(): FlashMessageQueue
    {
        return $this->flashMessageService->getMessageQueueByIdentifier();
    }
}
