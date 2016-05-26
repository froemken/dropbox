<?php
namespace SFroemken\FalDropbox\Dropbox\Exception;

/**
 * There was a network I/O error when making the request.
 */
final class NetworkIO extends \Exception
{
    /**
     * @internal
     */
    function __construct($message, $cause = null)
    {
        parent::__construct($message, $cause);
    }
}
