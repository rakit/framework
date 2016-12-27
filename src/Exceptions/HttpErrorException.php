<?php 

namespace Rakit\Framework\Exceptions;

use ErrorException;

class HttpErrorException extends ErrorException {

    protected $code = 500;

    protected $message = "Error 500! Internal Server Error";

}