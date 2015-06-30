<?php namespace Rakit\Framework;

abstract class Controller {

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function __call($method, array $args)
    {
        return call_user_func_array([$this->app, $method], $args);
    }

    public function __get($key)
    {
        return $this->app->{$key};
    }

}
