<?php namespace Rakit\Framework\Exceptions;

use ErrorException;

class FatalErrorException extends ErrorException {

    protected $message = "Fatal error";

    protected $code = 500;

} 