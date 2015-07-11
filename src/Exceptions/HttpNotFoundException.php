<?php namespace Rakit\Framework\Exceptions;

class HttpNotFoundException extends HttpErrorException {

    protected $code = 404;

    protected $message = "Error 404! page not found";

}