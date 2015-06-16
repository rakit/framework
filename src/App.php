<?php namespace App\Framework;

use ArrayAccess;

class App implements ArrayAccess {

    const VERSION = '0.0.1';

    protected static $instances = [];

    protected static $default_instance = 'default';

    protected $container;

    protected $name;

    protected $booted = false;

    /**
     * Constructor
     * 
     * @param   string $name
     * @param   array $configs
     * @return  void
     */
    public function __construct($name, array $configs = array())
    {
        $this->name = $name;
        $configs = array_merge($default_configs, $configs);

        $this->container = new Container;
        $this->container['app'] = $this;
        $this->config = new Configurator($configs);
        $this->router = new Router($this); 
        $this->request = new Request($this);
        $this->response = new Response($this);

        static::$instances[$name] = $this;

        if(count(static::$instances) == 1) {
            static::setDefaultInstance($name);
        }
    }

    /**
     * Register a middleware
     * 
     * @param   string $name
     * @param   mixed $callable
     * @return  void
     */
    public function middleware($name, $callable)
    {
         
    }

    /**
     * Register GET route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function get($uri, $action)
    {
        return $this->route('GET', $uri, $action);
    }

    /**
     * Register POST route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function post($uri, $action)
    {
        return $this->route('POST', $uri, $action);
    }

    /**
     * Register PUT route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function put($uri, $action)
    {
        return $this->route('PUT', $uri, $action);
    }

    /**
     * Register PATCH route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function patch($uri, $action)
    {
        return $this->route('PATCH', $uri, $action);
    }

    /**
     * Register DELETE route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function delete($uri, $action)
    {
        return $this->route('DELETE', $uri, $action);
    }

    /**
     * Registering a route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function route($methods, $uri, $action)
    {
        return $this->router->register($methods, $uri, $action);
    }

    /**
     * Booting app
     * 
     * @return  boolean
     */
    public function boot()
    {
        if($this->booted) return false;

        $providers = $this->config->get('providers');
        foreach($providers as $provider) {
            $this[$provider] = $this->container->make($provider);
            $this->container->call([$this[$provider], "boot"]);
        }

        return $this->booted = true;
    }

    /**
     * Run application
     * 
     * @param   string $path
     * @param   string $method
     * @return  void
     */
    public function run($path = null, $method = null)
    {
        $this->boot();

        $path = $path ?: $this->request->path();
        $method = $method ?: $this->request->server['request_method'];
        $matched_route = $this->router->findMatch($path, $method);

        if(!$matched_route) {
            return $this->notFound();
        }

        $this->request->setRoute($matched_route);
        $middlewares = $matched_route->getMiddlewares();
        $action = $matched_route->getAction();
        
        $this->makeActions($middlewares, $action);
        $result = $this->runActions();

        if(is_string($result)) {
            $this->response->html($result)->send();
        } elseif(is_array($result)) {
            $this->response->json($result)->send();
        } else {
            $this->response->send();
        }
    }

    /**
     * Set/get notFound handler
     * 
     * @param   mixed $callable
     * @return  void
     */
    public function notFound($callable = null)
    {
        if($callable) {
            $this['notFoundHandler'] = $callable;
        } else {
            return $this['notFoundHandler']($this);
        }
    }

    /**
     * Set/get error handler
     * 
     * @param   mixed $callable
     * @return  void
     */
    public function error($callable)
    {
        
    }

    /**
     * Set default instance name
     *
     * @param   string $name
     */
    public static function setDefaultInstance($name)
    {
        static::$default_instance = $name;
    }

    /**
     * Getting an application instance
     *
     * @param   string $name
     */
    public static function getInstance($name = null)
    {
        if(!$name) $name = static::$default_instance;
        return static::$instances[$name];
    }

    /**
     * Make/build app actions
     * 
     * @param   array $middlewares
     * @param   mixed $controller
     * @return  void
     */
    protected function makeActions(array $middlewares, $controller)
    {
        $app = $this;
        $actions = array_merge($middlewares, [$controller]);
        $index_controller = count($actions)-1;
        foreach($actions as $i => $act) {
            $index = $i+1;
            $type = $i == $index_controller? 'controller' : 'middleware';
            $callable = $type == 'middleware'? $this->resolveMiddleware($action) : $this->resolveCallable($action);
            $curr_key = 'app.action.'.($index);
            $next_key = 'app.action.'.($index+1);
            
            // register action into container
            $app[$curr_key] = function() use ($app, $type, $callable, $next_key) {
                $next = $app[$next_key];

                // if type of action is controller, parameter should be route params
                if($type == 'controller') 
                {
                    $matched_route = $app->request->route();
                    $params = $matched_route->params;
                } 
                else // parameter middleware should be Request, Response, $next 
                {
                    $params = [$app->request, $app->response, $next];
                }

                ob_start();
                // it should be null|array|string|Response
                $result = $app->container->call($callable, $params);
                $dump_string = ob_get_clean();

                if (
                    ($type == 'middleware' AND is_null($result) AND $next)
                    OR 
                    ($type == 'controller' AND $next)
                ) {
                    if(empty($result) OR is_string($result)) {
                        $app->response->appendBody($dump_string.$result);
                    }

                    $result = $next();
                }

                return $result;
            }
        }
    }

    /**
     * Run actions
     * 
     * @return  void
     */
    protected function runActions()
    {
        $action = $this->container['app.action.1'];
        return $action? $action() : null;
    }

    /**
     * ---------------------------------------------------------------
     * Setter and getter
     * ---------------------------------------------------------------
     */
    public function __set($key, $value)
    {
        $this->container->register($key, $value);
    }

    public function __get($key)
    {
        return $this->container->get($key);
    }

    /**
     * ---------------------------------------------------------------
     * ArrayAccess interface methods
     * ---------------------------------------------------------------
     */
    public function offsetSet($key, $value) {
        return $this->container->register($key, $value);
    }

    public function offsetExists($key) {
        return $this->container->has($key);
    }

    public function offsetUnset($key) {
        return $this->container->remove($key);
    }

    public function offsetGet($key) {
        return $this->container->get($key);
    }

}