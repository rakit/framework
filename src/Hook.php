<?php namespace Rakit\Framework;

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
            $hooks[$id] = [
                'action' => $action, 
                'limit' => $limit, 
                'path' => $path
            ];
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
            $this->applyHook($hook);
        }

        return $results;
    }

    protected function applyHook($hook)
    {
        $path = $hook['path'];
        $limit = $hook['limit'];
        $action = $hook['action'];

        $results[] = $this->run($action, $params);

        if(is_int($limit)) {
            $limit -= 1;

            if($limit < 1) {
                $this->hooks->remove($path);
            } else {
                $this->hooks->set($path.'.limit', $limit);
            }
        }
    }

    public function resolveCallable($callable, $params)
    {
        return $this->app->resolveCallable($callable, $params);
    }

}