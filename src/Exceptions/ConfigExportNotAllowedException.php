<?php

namespace Automaticko\ACL\Exceptions;

use Exception;

class ConfigExportNotAllowedException extends Exception
{
    public function __construct($message, $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
