<?php
namespace SFroemken\FalDropbox\Dropbox\WebAuthException;

/**
 * Thrown if the user chose not to grant your app access to their Dropbox account.
 */
class NotApproved extends \Exception
{
    /**
     * @param string $message
     *
     * @internal
     */
    function __construct($message)
    {
        parent::__construct($message);
    }
}
