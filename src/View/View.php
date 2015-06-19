<?php namespace Rakit\Framework\View;

use Rakit\Framework\App;

class View {

    protected $app;

    protected $engine;

    protected $data = array();

    public function __construct(App $app, ViewEngineInterface $engine = null)
    {
        $this->app = $app;
        $engine = $engine ?: new BasicViewEngine($app->config['view.path']);
    }

    public function setEngine(ViewEngineInterface $engine)
    {
        $this->engine = $engine;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return isset($this->data[$key])? $this->data[$key] : $default;
    }

    public function render($file, array $data = array())
    {
        $data = array_merge($this->data, $data);
        $data['app'] = $this->app;

        return $this->engine->render($file, $data);
    }

    public function __call($method, $params)
    {
        return call_user_func_array([$this->engine, $method], $params);
    }

}