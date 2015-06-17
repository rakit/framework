<?php namespace Rakit\Framework\Router;

use Rakit\Framework\MacroableTrait;

class RouteMap {

    use MacroableTrait;

    /**
     * Registered routes
     * @var array
     */
    protected $routes = array();

    /**
     * Constructor
     * 
     * @param   array $routes
     * @return  void
     */
    public function __construct(array $routes)
    {
        foreach($routes as $route) {
            $this->registerRoute($route);
        }
    }

    /**
     * Get registered routes
     * 
     * @return  void
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Register a route
     * 
     * @param   Route $route
     * @return  void
     */
    public function registerRoute(Route $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Adding allowed method to registered routes
     * 
     * @param   string $method
     * @return  self
     */
    public function allowMethod($method)
    {
        foreach($this->routes as $route) {
            $route->allowMethod($method);
        }

        return $this;
    }

    /**
     * Set path condition into registered routes
     * 
     * @param   string $param
     * @param   string $regex
     * @return  self
     */
    public function where($param, $regex)
    {
        foreach($this->routes as $route) {
            $route->where($param, $regex);
        }

        return $this;
    }

    /**
     * Register middleware(s) into registered routes
     *
     */
    public function middleware($middlewares, $append = 'prepend')
    {
        $middlewares = (array) $middlewares;
        foreach($this->routes as $route) {
            $route->middleware($middlewares, $append);
        }
    }

}