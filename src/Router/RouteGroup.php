<?php namespace Rakit\Framework\Router;

use Closure;
use Rakit\Framework\MacroableTrait;

class RouteGroup {
    
    use MacroableTrait;

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
     * Register routes by many methods
     *
     * @param   array $methods
     * @param   string $path
     * @return  Route[]
     */
    public function map(array $methods, $path, $handler)
    {
        return $this->group($path, function($group) use ($methods, $handler) {
            foreach($methods as $method) {
                $group->add($method, '/', $handler);
            }
        });
    }

    /**
     * Register a Route
     *
     * @param   string $method
     * @param   string $path
     * @return  Route
     */
    public function add($method, $path, $handler)
    {
        $middlewares = $this->getMiddlewares();
        $conditions = $this->getConditions();
        $path = $this->getPath().$path;

        $route = new Route($method, $path, $handler, $middlewares, $conditions);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Register GET route
     *
     * @param string    $path
     * @return Route
     */
    public function get($path, $handler)
    {
        return $this->add('GET', $path, $handler);
    }

    /**
     * Register POST route
     *
     * @param string    $path
     * @return Route
     */
    public function post($path, $handler)
    {
        return $this->add('POST', $path, $handler);
    }

    /**
     * Register PUT route
     *
     * @param string    $path
     * @return Route
     */
    public function put($path, $handler)
    {
        return $this->add('PUT', $path, $handler);
    }

    /**
     * Register PATCH route
     *
     * @param string    $path
     * @return Route
     */
    public function patch($path, $handler)
    {
        return $this->add('PATCH', $path, $handler);
    }

    /**
     * Register DELETE route
     *
     * @param string    $path
     * @return Route
     */
    public function delete($path, $handler)
    {
        return $this->add('DELETE', $path, $handler);
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
