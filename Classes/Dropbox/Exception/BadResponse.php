<?php
namespace SFroemken\FalDropbox\Dropbox\Exception;

/**
 * When this SDK can't understand the response from the server.  This could be due to a bug in this
 * SDK or a buggy response from the Dropbox server.
 */
class BadResponse extends ProtocolError
{
    /**
     * @internal
     */
    function __construct($message)
    {
        parent::__construct($message);
    }
}
