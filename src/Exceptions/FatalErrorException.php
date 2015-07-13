<?php namespace Rakit\Framework\Exceptions;

use ErrorException;

class FatalErrorException extends ErrorException {

    protected $message = "Fatal error";

    protected $line = 100;

    protected $code = 500;

} 