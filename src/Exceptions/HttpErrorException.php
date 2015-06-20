<?php namespace Rakit\Framework\Exceptions;

use Exception;

class HttpErrorException extends Exception {

    protected $http_code = 500;

    protected $message = "Error 500! Internal Server Error";

    public function getHttpCode()
    {
        return $this->http_code;
    }

}