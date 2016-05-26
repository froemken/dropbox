<?php
namespace SFroemken\FalDropbox\Dropbox\WebAuthException;

/**
 * Thrown if Dropbox returns some other error about the authorization request.
 */
class Provider extends \Exception
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
