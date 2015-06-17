<?php namespace Rakit\Framework\Router;

use Rakit\Framework\MacroableTrait;

class Route {

    use MacroableTrait;

    /**
     * Route path
     * @var string
     */
    protected $path;

    /**
     * Route name
     * @var string
     */
    protected $name;

    /**
     * Route allowed methods
     * @var array
     */
    protected $allowed_methods = array();

    /**
     * Route parameter regexes
     * @var array
     */
    protected $conditions = array();

    /**
     * Route middlewares
     * @var array
     */
    protected $middlewares = array();

    /**
     * Constructor 
     *
     * @param   string
     * @return  void
     */
    public function __construct($allowed_methods, $path, $action)
    {
        $args = func_get_args();
        $allowed_methods = array_shift($args);
        $path = array_shift($args);
        $action = array_pop($args);
        $middlewares = $args;

        $this->allowed_methods = (array) $allowed_methods;
        $this->action = $action;
        $this->middlewares = $middlewares;
        $this->path = $path;
    }

    /**
     * Get route name 
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route path 
     *
     * @return  string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get route allowed methods 
     *
     * @return  array
     */
    public function getAllowedMethods()
    {
        return $this->allowed_methods;
    }

    /**
     * Get route parameter conditions 
     *
     * @return  array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set route name 
     *
     * @param   string $name
     * @return  self
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set allowed methods
     *
     * @param   string
     * @return  self
     */
    public function allowMethod($method)
    {
        $methods = (array) $method;

        foreach($methods as $i => $method) {
            $methods[$i] = $this->resolveMethodName($method);
        }

        $this->allowed_methods = array_merge($this->allowed_methods, $methods);

        return $this;
    }

    /**
     * Set parameter condition(regex) 
     *
     * @param   string $param
     * @param   string $regex
     * @return  self
     */
    public function where($param, $regex)
    {
        if(is_array($param)) {
            $this->conditions = $param;
            return;
        }

        $this->conditions[$param] = $condition;

        return $this;
    }

    /**
     * Set route middleware 
     *
     * @param   string|array $middlewares
     * @return  void
     */
    public function middleware($middlewares, $append = 'append')
    {
        $middlewares = (array) $middlewares;
        if('prepend' == $append) {
            $this->middlewares = array_merge($middlewares, $this->middlewares);
        } else {
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        }
    }

    /**
     * Check if route is GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isAllowing("GET");   
    }

    /**
     * Check if route is POST
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isAllowing("POST");   
    }

    /**
     * Check if route is PUT
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isAllowing("PUT");   
    }

    /**
     * Check if route is PATCH
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->isAllowing("PATCH");   
    }

    /**
     * Check if route is DELETE
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isAllowing("DELETE");   
    }

    /**
     * Check if route allowing given method
     *
     * @return bool
     */
    public function isAllowing($method)
    {
        $method = $this->resolveMethodName($method);
        return in_array($method, $this->getAllowedMethods());
    }

    protected function resolveMethodName($method)
    {
        return strtoupper($method);
    }

}
