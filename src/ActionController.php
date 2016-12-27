<?php 

namespace Rakit\Framework;

class ActionController extends Action {

    public function __construct(App $app, $index, $action)
    {
        parent::__construct($app, 'controller', $index, $action);
    }

    /**
     * Get resolved controller
     *
     * @return callable
     */
    public function getCallable()
    {
        $action = $this->getDefinedAction();
        $matched_route = $this->app->request->route();
        $params = $matched_route->params;

        $callable = $this->app->resolveController($action, $params);

        return $callable;
    }

}