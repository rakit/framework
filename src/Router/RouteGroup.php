<?php namespace Rakit\Framework\Router;

use Closure;

class RouteGroup {

    protected $path;

    protected $routes = array();

    protected $groups = array();

    protected $middlewares = array();

    protected $conditions = array();

    public function __construct($path, Closure $grouper, array $middlewares = array(), array $conditions = array())
    {
        $this->path = $this->resolvePath($path);
        $this->grouper = $grouper;
        $this->middlewares = $middlewares;
        $this->conditions = $conditions;
    }

    /**
     * Get path
     *
     * @return string   $path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get route collections
     *
     * @return array of Rakit\Framework\Router\Route
     */
    public function getRoutes()
    {
        // reset routes and groups
        $this->routes = array();
        $this->groups = array();
        // run grouper
        call_user_func($this->grouper, $this);

        $routes = $this->routes;
        $groups = $this->groups;
        foreach($groups as $group) {
            $routes = array_merge($routes, $group->getRoutes());
        }

        return $routes;
    }

    /**
     * Register a route
     *
     * @param string    array $methods
     * @param string    $path
     * @return Route
     */
    public function register($methods, $path, $controller)
    {
        $middlewares = $this->getMiddlewares();
        $conditions = $this->getConditions();
        $path = $this->getPath().$path;
        $route = new Route($methods, $path, $controller, $middlewares, $conditions);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Register GET route
     *
     * @param string    $path
     * @return Route
     */
    public function get($path, $controller)
    {
        return $this->register('GET', $path, $controller);
    }

    /**
     * Register POST route
     *
     * @param string    $path
     * @return Route
     */
    public function post($path, $controller)
    {
        return $this->register('GET', $path, $controller);
    }

    /**
     * Register PUT route
     *
     * @param string    $path
     * @return Route
     */
    public function put($path, $controller)
    {
        return $this->register('PUT', $path, $controller);
    }

    /**
     * Register PATCH route
     *
     * @param string    $path
     * @return Route
     */
    public function patch($path, $controller)
    {
        return $this->register('PATCH', $path, $controller);
    }

    /**
     * Register DELETE route
     *
     * @param string    $path
     * @return Route
     */
    public function delete($path, $controller)
    {
        return $this->register('DELETE', $path, $controller);
    }

    /**
     * Grouping routes with Closure
     *
     * @param string    $path
     * @param Closure   $grouper
     * @return RouteMap
     */
    public function group($path, Closure $grouper)
    {
        $path = $this->getPath().$path;
        $middlewares = $this->getMiddlewares();
        $conditions = $this->getConditions();
        $group = new RouteGroup($path, $grouper, $middlewares, $conditions);

        $this->groups[] = $group;

        return $group;
    }

    /**
     * Set parameter condition
     *
     * @param string    $param
     * @param string    $condition
     * @return void
     */
    public function where($param, $condition)
    {
        $this->conditions[$param] = $condition;
        return $this;
    }

    /**
     * Append middlewares
     *
     * @param mixed     $middlewares
     * @return void
     */
    public function middleware($middlewares)
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);
        return $this;
    }

    /**
     * Get parameter conditions
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Get middlewares
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Resolve path
     *
     * @return string
     */
    protected function resolvePath($path)
    {
        return '/'.trim($path, '/');
    }

    public function __call($method, $params)
    {
        $params = implode(',', $params);
        $middleware = $method.':'.$params;
        return $this->middleware($middleware);
    }

}
