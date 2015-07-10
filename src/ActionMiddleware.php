<?php namespace Rakit\Framework;

class ActionMiddleware extends Action {

    protected $type;

    public function __construct(App $app, $index, $action)
    {
        parent::__construct($app, 'middleware', $index, $action);
    }

    /**
     * Get resolved middleware
     *
     * @return callable
     */
    public function getCallable()
    {
        $next = $this->getNext();
        $action = $this->getDefinedAction();

        $params = [$this->app->request, $this->app->response, $next];
        $callable = $this->app->resolveMiddleware($action, $params);

        return $callable;
    }

}