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
     * Run action
     *
     * @return Rakit\Framework\Http\Response
     */
    public function run()
    {
        $app = $this->getApp();
        $callable = $this->getCallable();
        $returned = $app->container->call($callable);

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