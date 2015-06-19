<?php namespace Rakit\Framework;

abstract class Provider {

    public function __construct(App $app)
    {
        $this->app = $app;        
    }

    abstract public function register();

    abstract public function boot();

    public function when()
    {
        return [];
    }

}