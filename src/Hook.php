<?php namespace Rakit\Framework;

use Rakit\Framework\Util\Arr;

class Hook {

    protected $hooks;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->hooks = new Bag;
    }

    public function on($event, $actions, $limit = null)
    {
        $actions = (array) $actions;
        $hooks = $this->hooks->get($event, []);
        foreach($actions as $action) {
            $id = uniqid();
            $path = $event.'.'.$id;
            $hook = new \stdClass;
            $hook->action = $action;
            $hook->limit = $limit;
            $hook->path = $path;

            $hooks[$id] = $hook;
        }

        $this->hooks->set($event, $hooks);
        return $this;
    }

    public function once($event, $actions)
    {
        return $this->on($event, $actions, 1);
    }

    public function applyFirst($event)
    {
        $hooks = Arr::flatten($this->hooks->get($event, []));
        $first_hook = array_shift($hooks);
        return $first_hook? $this->applyHook($hook) : null;   
    }

    public function applyLast($event)
    {
        $hooks = Arr::flatten($this->hooks->get($event, []));
        $last_hook = array_pop($hooks);
        return $last_hook? $this->applyHook($hook) : null;   
    }

    public function apply($event, array $params = array())
    {
        $hooks = Arr::flatten($this->hooks->get($event, []));
        $results = [];

        foreach($hooks as $hook) {
            $results[] = $this->applyHook($hook, $params);
        }

        return $results;
    }

    protected function applyHook($hook, $params)
    {
        $path = $hook->path;
        $limit = $hook->limit;
        $action = $this->resolveCallable($hook->action, $params);

        $result = $this->app->container->call($action, $params);

        if(is_int($limit)) {
            $limit -= 1;

            if($limit < 1) {
                $this->hooks->remove($path);
            } else {
                $hook->limit = $limit;
            }
        }

        return $result;
    }

    public function resolveCallable($callable, $params)
    {
        return $this->app->resolveCallable($callable, $params);
    }

}