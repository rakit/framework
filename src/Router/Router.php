<?php namespace Rakit\Framework\Router;

use Closure;
use Rakit\Framework\MacroableTrait;

class Router {

    use MacroableTrait;

    /**
     * List registered routes
     * @var array
     */
    protected $routes = array();

    /**
     * Current group paths
     * @var array
     */
    protected $group_paths = array();
    
    /**
     * List current groups
     * @var array
     */
    protected $curr_groups = array();

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
        return $this->routes;
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

        foreach($this->routes as $route) {
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
        foreach($this->routes as $route) {
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
        $args = func_get_args();

        if (count($args) > 3) {
            $middlewares = (array) $args[2];
            $controller = $args[3];
        } else {
            $middlewares = array();
        }

        if ( ! empty($this->group_paths)) {
            $prefix = implode("/", $this->group_paths);
            
            $path = preg_replace('/[\/]+/', '/', $prefix.$path);

            $route = new Route($methods, $path, $controller, $middlewares);

            foreach($this->curr_groups as $group) {
                $group->registerRoute($route);
            }
        } else {
            $route = new Route($methods, $path, $controller, $middlewares);
        }

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
        $args = array_merge(['GET'], func_get_args());
        return call_user_func_array([$this, 'register'], $args);
    }

    /**
     * Register POST route
     *
     * @param   string $path
     * @return  Route
     */
    public function post($path, $controller)
    {
        $args = array_merge(['POST'], func_get_args());
        return call_user_func_array([$this, 'register'], $args);
    }

    /**
     * Register PUT route
     *
     * @param   string $path
     * @return  Route
     */
    public function put($path, $controller)
    {
        $args = array_merge(['PUT'], func_get_args());
        return call_user_func_array([$this, 'register'], $args);
    }

    /**
     * Register PATCH route
     *
     * @param   string $path
     * @return  Route
     */
    public function patch($path, $controller)
    {
        $args = array_merge(['PATCH'], func_get_args());
        return call_user_func_array([$this, 'register'], $args);
    }

    /**
     * Register DELETE route
     *
     * @param   string $path
     * @return  Route
     */
    public function delete($path, $controller)
    {
        $args = array_merge(['DELETE'], func_get_args());
        return call_user_func_array([$this, 'register'], $args);
    }

    /**
     * Mapping routes
     *
     * @param   array $routes_def
     * @return  RouteMap
     */
    public function map(array $routes_def)
    {
        $routes = array();

        foreach($routes_def as $route_def) {
            if ($route_def instanceof Route) {
                $routes[] = $route_def;
            } elseif ($route_def instanceof RouteMap) {
                $routes = array_merge($routes, $route_def->getRoutes());
            } elseif (is_string($route_def)) {
                $route = $this->findRouteByName($route_def);
                if ( ! $route) throw new \Exception("Trying to map undeclared route named '{$route_def}'");

                $routes[] = $route;
            }
        }

        return new RouteMap($routes);
    }

    /**
     * Grouping routes with Closure
     *
     * @param   string $path
     * @param   Closure $grouper
     * @return  RouteMap
     */
    public function group($path, $grouper)
    {
        $args = func_get_args();
        $path = array_shift($args);
        $grouper = array_pop($args);
        $middlewares = $args;

        // router group mode...
        $this->group_paths[] = $this->resolvePath($path);
        $route_map = $this->curr_groups[] = $this->map([]);

        // call grouper
        call_user_func($grouper, $this);

        // disable router group mode
        array_pop($this->group_paths);
        array_pop($this->curr_groups);

        return $route_map;
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

        foreach($this->routes as $route) {
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