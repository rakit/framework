<?php namespace Rakit\Framework\Exceptions;

class HttpNotFoundException extends HttpErrorException {

    protected $http_code = 404;

    protected $message = "Error 404! page not found";

}