<?php namespace Rakit\Framework\Exceptions;

use Exception;

class HttpErrorException extends Exception {

    protected $code = 500;

    protected $message = "Error 500! Internal Server Error";

}