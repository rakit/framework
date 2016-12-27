<?php namespace Rakit\Framework\Router;

use Closure;
use Rakit\Framework\App;
use Rakit\Framework\MacroableTrait;

class Router {

    use MacroableTrait;

    /**
     * Kumpulan route yang di daftarkan
     * @var array
     */
    protected $routes = [];

    /**
     * Cache name
     * @var string|callable
     */
    protected $cache_file = null;

    /**
     * List registered groups
     * @var array
     */
    protected $groups = array();

    /**
     * Max parameter pada sebuah route
     * digunakan juga untuk acuan dummy group pada method toRegex()
     * @var int
     */
    protected $max_params = 10;
    
    /**
     * Banyak route maksimum di dalam sebuah regex
     * @var int
     */
    protected $max_routes_in_regex = 16;

    /**
     * Default route parameter regex
     * @var string
     */
    protected $default_param_regex = '[^/]+';

    /**
     * Case sensitive route
     * @var boolean
     */
    protected $case_sensitive = true;

    /**
     * Get registered routes
     *
     * @return  array
     */
    public function getRoutes()
    {
        $routes = $this->routes;
        foreach($this->groups as $group) {
            $routes = array_merge($routes, $group->getRoutes());
        }

        return $routes;
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
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Register GET Route
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  Route 
     */
    public function get($path, $handler)
    {
        return $this->add('GET', $path, $handler);
    }

    /**
     * Register POST Route
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  Route 
     */
    public function post($path, $handler)
    {
        return $this->add('POST', $path, $handler);
    }

    /**
     * Register PUT Route
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  Route 
     */
    public function put($path, $handler)
    {
        return $this->add('PUT', $path, $handler);
    }

    /**
     * Register PATCH Route
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  Route 
     */
    public function patch($path, $handler)
    {
        return $this->add('PATCH', $path, $handler);
    }

    /**
     * Register DELETE Route
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  Route 
     */
    public function delete($path, $handler)
    {
        return $this->add('DELETE', $path, $handler);
    }

    /**
     * Grouping routes with Closure
     *
     * @param   string $path
     * @param   Closure $grouper
     * @return  RouteGroup
     */
    public function group($path, Closure $grouper)
    {
        $group = new RouteGroup($path, $grouper);
        $this->groups[] = $group;
        return $group;
    }

    /**
     * Grouping some routes in different methods
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  RouteGroup
     */
    public function map(array $methods, $path, $handler)
    {
        $group = new RouteGroup($path, function($group) use ($methods, $handler) {
            foreach($methods as $method) {
                $group->add($method, '/', $handler);
            }
        });

        $this->groups[] = $group;
        return $group;
    }

    /**
     * Register GET, POST, PUT, PATCH & DELETE routes with same path & handler
     *
     * @param   string $path
     * @param   Closure|string $handler
     * @return  RouteGroup 
     */
    public function any($path, $handler)
    {
        return $this->map(['GET','POST','PUT','PATCH','DELETE'], $path, $handler);
    }

    /**
     * Enable cache routes by giving cache file name
     *
     * @param string|callable $cache_file
     * @return null
     */
    public function cache($cache_file)
    {
        if (!is_string($cache_file) AND !is_callable($cache_file)) {
            throw new \InvalidArgumentException("Cache name must be string or callable", 1);
        }

        $this->cache_file = $cache_file;
    }

    /**
     * Find route by given path and method
     *
     * @param   string $path
     * @param   string $method
     * @return  null|Route
     */
    public function dispatch($method, $path)
    {
        $pattern = $this->makePattern($method, $path);
        $all_routes = $this->getRoutes();

        $cache_file = $this->cache_file;
        $chunk_routes = array_chunk($all_routes, $this->max_routes_in_regex);

        if ($cache_file AND is_callable($cache_file)) {
            $cache_file = call_user_func_array($cache_file, [$all_routes, $this]);
        }

        if ($cache_file AND file_exists($cache_file) AND $cached_routes = $this->getCachedRoutes($cache_file)) {
            $route_regexes = $cached_routes;
        } else {
            $route_regexes = [];

            foreach($chunk_routes as $i => $routes) {
                $regex = [];
                foreach($routes as $i => $route) {
                    $regex[] = $this->toRegex($route, $i);
                }
                $regex = "~^(?|".implode("|", $regex).")$~x";
                $route_regexes[] = $regex;
            }

            if ($cache_file) {
                $this->cacheRoutes($cache_file, $route_regexes);
            }
        }

        foreach($route_regexes as $i => $regex) {
            $routes = $chunk_routes[$i];

            if(!preg_match($regex, $pattern, $matches)) {
                continue;
            }

            $index = (count($matches) - 1 - $this->max_params);

            $matched_route = $routes[$index];
            $matched_route->params = [];
            $path = $matched_route->getPath();

            $params = $this->getDeclaredPathParams($matched_route);
            foreach($params as $i => $param) {
                // find param index in $matches
                // the problem is if using optional parameter like:
                // /foo/:bar(/:baz)
                // the regex should look like this:
                // /foo/(<bar>)(/(<baz>))?
                // so the index of :baz is not $i, 
                // so the trick is to add $i with count char '(' before that :param in route path
                // for example if route path like this:
                // /foo/:bar(/:baz(/:qux)), the regex: /foo/(<bar>)(/(<baz>)(/(<qux>))?)?
                // so index for :qux is 3+2, where 2 is count '(' before :qux
                $pos = strpos($path, ':'.$param);
                $count_open_bracket = substr_count($path, '(', 0, $pos);
                $value = $matches[$i+1+$count_open_bracket];

                if($value) {
                    $matched_route->params[$param] = $value;
                }
            }
            
            return $matched_route;
        }

        return null;
    }

    /**
     * Alias for dispatch
     */
    public function findMatch($path, $method)
    {
        return $this->dispatch($method, $path);
    }

    /**
     * Get route by given name
     *
     * @param   string $route_name
     * @return  null|Route
     */
    public function findRouteByName($name)
    {
        $routes = $this->getRoutes();
        foreach($routes as $route) {
            if($route->getName() == $name) return $route;
        }
        return null;
    }

    /**
     * Get cache route regex from cache file
     *
     * @param   string $cache_file
     * @return  array
     */
    protected function getCachedRoutes($cache_file)
    {
        $content = file_get_contents($cache_file);
        return unserialize($content);
    }

    /**
     * Save routes to cache file
     *
     * @param   string $cache_file
     * @return  array
     */
    protected function cacheRoutes($cache_file, $route_regexes)
    {
        $content = serialize($route_regexes);
        return file_put_contents($cache_file, $content);
    }

    /**
     * Tranfrom route path into Regex
     *
     * @param   Route $route
     * @return  string regex
     */
    protected function toRegex(Route $route, $index)
    {
        $method = $route->getMethod();
        $path = $route->getPath();
        $conditions = $route->getConditions();
        $params = $this->getDeclaredPathParams($route);

        $regex = $this->makePattern($method, $path);

        // transform /foo/:bar(/:baz) => /foo/:bar(/:baz)?
        $regex = str_replace(')', ')?', $regex);

        foreach($params as $param) {
            if (array_key_exists($param, $conditions)) {
                $regex = str_replace(':'.$param, '('.$conditions[$param].')', $regex);
            } else {
                $regex = str_replace(':'.$param, '('.$this->default_param_regex.')', $regex);
            }
        }

        $count_brackets = substr_count($regex, "(");
        $count_added_brackets = $this->max_params + ($index - $count_brackets);

        $regex .= str_repeat("()", $count_added_brackets);

        return $regex;
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
        preg_match_all('/\:([a-z_][a-z0-9_]+)/i', $path, $match);
        return $match[1];
    }

    public function makePattern($method, $path)
    {
        return $method.$path;
    }

}
