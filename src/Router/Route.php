<?php 

namespace Rakit\Framework\Router;

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
     * Route allowed method
     * @var string
     */
    protected $allowed_method = '';

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
    public function __construct($allowed_method, $path, $action, array $middlewares = array(), array $conditions = array())
    {
        $this->allowed_method = $allowed_method;
        $this->path = $this->resolvePath($path);
        $this->action = $action;
        $this->middlewares = $middlewares;
        $this->conditions = $conditions;
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
    public function getMethod()
    {
        return $this->allowed_method;
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
     * Get registered middlewares
     *
     * @return  array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Get route action controller
     *
     * @return  mixed action
     */
    public function getAction()
    {
        return $this->action;
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
     * Set parameter condition(regex) 
     *
     * @param   string $param
     * @param   string $regex
     * @return  self
     */
    public function where($param, $regex)
    {
        $this->conditions[$param] = $regex;
        return $this;
    }

    /**
     * Set route middleware 
     *
     * @param   string|array $middlewares
     * @return  void
     */
    public function middleware($middlewares)
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);
        return $this;
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
        return $this->resolveMethodName($method) == $this->getMethod();
    }

    /**
     * Resolve method name
     *
     * @return string
     */
    protected function resolveMethodName($method)
    {
        return strtoupper($method);
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
