<?php namespace Rakit\Framework\Http;

use Rakit\Framework\Router\Route;
use Rakit\Framework\Util\Arr;

class Response {

    public function setStatus($status)
    {
        
        return $this;
    }

    public function getStatus()
    {
        
    }

    public function setBody()
    {
        
        return $this;
    }

    public function getBody()
    {
        
        
    }

    public function reset($reset_headers = false)
    {
        
    }

    public function json(array $data, $status = null, $reset = false)
    {
        

        return $this;
    }

    public function html($html, $status = null, $reset = false)
    {

        return $this;
    }

    public function send($message = null, $status = null, $reset = false)
    {
        
    }

    protected function writeHeaders()
    {
        
    }

}