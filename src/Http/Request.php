<?php namespace Rakit\Framework\Http;

use Rakit\Framework\Bag;

class Request {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    public $params = array();

    public function __construct(App $app, Route $route = null)
    {
        if($route) $this->defineRoute($route);
        $this->app = $app;
    }

    public function defineRoute(Route $route)
    {
        if($this->route) return;

        $this->params = $route->params;
        $this->route = $route;
    }

    public function isMethod($method)
    {
        return strtoupper($this->method()) == strtoupper($method);
    }

    public function isMethodGet()
    {
        return $this->isMethod(static::METHOD_GET);
    }

    public function isMethodPost()
    {
        return $this->isMethod(static::METHOD_POST);
    }

    public function isMethodPut()
    {
        return $this->isMethod(static::METHOD_PUT);
    }

    public function isMethodPatch()
    {
        return $this->isMethod(static::METHOD_PATCH);
    }

    public function isMethodDelete()
    {
        return $this->isMethod(static::METHOD_DELETE);
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isHttps()
    {
        if( isset($_SERVER['HTTPS'] ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function isHttp()
    {
        return !$this->isHttps();
    }

    public function cookie($key, $default = null)
    {
        return $this->app->cookie->get($key, $default);
    }

    public function isAjax()
    {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }

    public function isJson()
    {
        return $this->json() !== NULL;
    }

    public function json()
    {
        $raw_body = $this->body();
        $json = json_decode($raw_body, true);

        if(is_array($json)) {
            $data = new Bag($json);
        } else {
            $data = NULL;
        }

        return $data;
    }

    public function body()
    {
        return file_get_contents("php://input");
    }

    public function param($key, $default = null)
    {
        $params = $this->params;
        return (array_key_exists($key, $params))? $params[$key] : $default;
    }

    public function params()
    {
        return $this->params;
    }

}