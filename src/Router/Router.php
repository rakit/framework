<?php namespace Rakit\Framework\Router;

use Closure;
use Rakit\Framework\App;
use Rakit\Framework\MacroableTrait;

class Router {

    use MacroableTrait;

    /**
     * List registered routes
     * @var array
     */
    protected $routes = array();

    /**
     * List registered groups
     * @var array
     */
    protected $groups = array();

    /**
     * Default route parameter regex
     * @var string
     */
    protected $default_param_regex = '[a-zA-Z0-9_.-]+';

    /**
     * Case sensitive route
     * @var boolean
     */
    protected $case_sensitive = true;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Get application
     *
     * @return Rakit\Framework\App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Set default parameter regex
     *
     * @param   string $regex
     */
    public function setDefaultParamRegex($regex)
    {
        $this->default_param_regex = $regex;
    }

    /**
     * Toggle enable/disable case sensitive
     *
     * @param   bool $bool
     * @return  void
     */
    public function caseSentitive($bool)
    {
        $this->case_sensitive = $bool;
    }

    /**
     * Get registered routes
     *
     * @return  array
     */
    public function getRoutes()
    {
        $routes = $this->routes;
        $groups = $this->groups;
        foreach($groups as $group) {
            $routes = array_merge($routes, $group->getRoutes());
        }

        return $routes;
    }

    /**
     * Find routes by given path
     *
     * @param   string $path_search
     * @return  array
     */
    public function findRoutes($path_search)
    {
        $routes = array();
        $path_search = preg_replace("/[^a-zA-Z0-9]/", '\\\$0', $path_search);

        $routes = $this->getRoutes();

        foreach($routes as $route) {
            if (preg_match("/^".$path_search."/", $route->getPath())) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    /**
     * Find a route by name
     *
     * @param   string $name
     * @return  null|Route
     */
    public function findRouteByName($name)
    {
        $routes = $this->getRoutes();

        foreach($routes as $route) {
            if ($route->getName() == $name) return $route;
        }

        return null;
    }

    /**
     * Register a route
     *
     * @param   string|array $methods
     * @param   string $path
     * @return  Route
     */
    public function register($methods, $path, $controller)
    {
        $route = new Route($methods, $path, $controller);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Register GET route
     *
     * @param   string $path
     * @return  Route
     */
    public function get($path, $controller)
    {
        return $this->register('GET', $path, $controller);
    }

    /**
     * Register POST route
     *
     * @param   string $path
     * @return  Route
     */
    public function post($path, $controller)
    {
        return $this->register('GET', $path, $controller);
    }

    /**
     * Register PUT route
     *
     * @param   string $path
     * @return  Route
     */
    public function put($path, $controller)
    {
        return $this->register('PUT', $path, $controller);
    }

    /**
     * Register PATCH route
     *
     * @param   string $path
     * @return  Route
     */
    public function patch($path, $controller)
    {
        return $this->register('PATCH', $path, $controller);
    }

    /**
     * Register DELETE route
     *
     * @param   string $path
     * @return  Route
     */
    public function delete($path, $controller)
    {
        return $this->register('DELETE', $path, $controller);
    }

    /**
     * Grouping routes with Closure
     *
     * @param   string $path
     * @param   Closure $grouper
     * @return  RouteMap
     */
    public function group($path, Closure $grouper)
    {
        $group = new RouteGroup($path, $grouper);
        $this->groups[] = $group;
        return $group;
    }

    /**
     * Find route by given path and method
     *
     * @param   string $path
     * @param   string $method
     * @return  null|Route
     */
    public function findMatch($path, $method = null)
    {
        $path = $this->resolvePath($path);

        $routes = $this->getRoutes();

        foreach($routes as $route) {
            $regex = $this->routePathToRegex($route);
            $method_allowed = is_string($method)? in_array($method, $route->getAllowedMethods()) : true;


            if (@preg_match($regex, $path, $match) AND $method_allowed) {
                $route_params = $this->getDeclaredPathParams($route);
                $route->params = array();

                foreach($route_params as $param) {
                    if (isset($match[$param])) {
                        $route->params[$param] = $match[$param];
                    }
                }

                return $route;
            }
        }

        return null;
    }

    /**
     * Tranfrom route path into Regex
     *
     * @param   Route $route
     * @return  string regex
     */
    protected function routePathToRegex(Route $route)
    {
        $path = $this->resolvePath($route->getPath());
        $conditions = $route->getConditions();
        $params = $this->getDeclaredPathParams($route);

        $regex = $path;
        // transform /foo(/:bar(/:baz)) into foo(/:bar(/:baz)?)?
        $regex = preg_replace('/\)/i', '$0?', $regex);

        // transform /foo/:bar/:baz into /foo/(?<bar>)/(?<baz>)
        $regex = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]+)/', "(?<$1>)", $regex);

        // transform /foo/bar into \/foo\/bar
        $regex = str_replace('/', '\/', $regex);

        // transform /foo/(?<baz>)/(?<baz>) into /foo/(?<bar>{$condition})/(?<baz>{$condition})
        foreach($params as $param) {
            if (array_key_exists($param, $conditions)) {
                $regex = str_replace('(?<'.$param.'>)', '(?<'.$param.'>'.$conditions[$param].')', $regex);
            } else {
                $regex = str_replace('(?<'.$param.'>)', '(?<'.$param.'>'.$this->default_param_regex.')', $regex);
            }
        }

        return $this->case_sensitive? '/^'.$regex.'$/i' : '/^'.$regex.'$/';
    }

    /**
     * Getting declared parameters by given route object
     *
     * @param   Route $route
     * @return  array
     */
    protected function getDeclaredPathParams(Route $route)
    {
        $path = $route->getPath();
        preg_match_all('/\:([a-zA-Z_][a-zA-Z0-9_]+)/', $path, $match);
        return $match[1];
    }

    /**
     * Resolving a path
     *
     * @param   string $path
     * @return  string
     */
    protected function resolvePath($path)
    {
        return '/'.trim($path, '/');
    }

}
