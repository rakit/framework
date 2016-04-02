<?php namespace Rakit\Framework;

abstract class Action {

    /**
     * @var int
     */
    protected $index;

    /**
     * @var Rakit\Framework\App
     */
    protected $app;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $action;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(App $app, $type, $index, $action)
    {
        $this->app = $app;
        $this->type = $type;
        $this->index = $index;
        $this->action = $action;
    }

    /**
     * Get application instance
     *
     * @return Rakit\Framework\App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Get action index
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get action type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get defined action
     *
     * @return mixed
     */
    public function getDefinedAction()
    {
        return $this->action;
    }

    /**
     * Abstract get resolved callable
     *
     * @return callable
     */
    abstract public function getCallable();

    /**
     * Get next action
     *
     * @return Rakit\Framework\Action | null
     */
    public function getNext()
    {
        $actions = $this->app['actions'];
        $next_index = $this->index+1;
        return array_key_exists($next_index, $actions)? $actions[$next_index] : null;
    }

    /**
     * Check if action is controller
     *
     * @return boolean
     */
    public function isController()
    {
        return $this->getType() == 'controller';   
    }

    /**
     * Check if action is middleware
     *
     * @return boolean
     */
    public function isMiddleware()
    {
        return $this->getType() == 'middleware';
    }

    /**
     * Check if defined action using string class method like ClassName@method
     *
     * @return boolean
     */
    public function useStringClassMethod()
    {
        $defined_action = (string) $this->getDefinedAction();
        return count(explode('@', $defined_action)) == 2;
    }

    /**
     * Run action
     *
     * @return Rakit\Framework\Http\Response
     */
    public function run()
    {
        $app = $this->getApp();
        $callable = $this->getCallable();
        
        if (!is_callable($callable)) {
            $defined_action = (string) $this->getDefinedAction();
            $reason = $defined_action." is not callable";
            if ($this->useStringClassMethod()) {
                list($class, $method) = explode('@', $defined_action, 2);
                if (!class_exists($class)) {
                    $reason = "Class {$class} doesn't exists";
                } elseif (!method_exists($class, $method)) {
                    $reason = "Method {$class}::{$method} doesn't exists";
                }
            }

            throw new \InvalidArgumentException("Cannot run action ".$this->getType()." '".$this->getDefinedAction()."'. ".$reason, 1);
        }

        $returned = call_user_func($callable);

        if(is_array($returned)) {
            $app->response->json($returned);
        } elseif(is_string($returned)) {
            $app->response->html($returned);
        }

        return $app->response->body;
    }

    /**
     * Invoke action
     *
     * @return Rakit\Framework\Http\Response
     */
    public function __invoke()
    {
        return $this->run();
    }

}