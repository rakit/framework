<?php namespace Rakit\Framework;

use ArrayAccess;
use Rakit\Framework\Http\Request;
use Rakit\Framework\Http\Response;
use Rakit\Framework\Router\Route;
use Rakit\Framework\Router\Router;

class App implements ArrayAccess {

    use MacroableTrait;

    const VERSION = '0.0.1';

    protected static $instances = [];

    protected static $default_instance = 'default';

    public $container;

    protected $name;

    protected $booted = false;

    protected $middlewares = array();

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
        $default_configs = [];
        $configs = array_merge($default_configs, $configs);

        $this->container = new Container;
        $this->container['app'] = $this;
        $this->config = new Configurator($configs);
        $this->router = new Router($this); 
        $this->hook = new Hook($this);
        $this->request = new Request($this);
        $this->response = new Response($this);

        static::$instances[$name] = $this;

        if(count(static::$instances) == 1) {
            static::setDefaultInstance($name);
        }

        $this->registerDefaultMacros();
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
        $this->middleware[$name] = $callable;
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
     * Register DELETE route
     * 
     * @param   string $uri
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function group($prefix, $action)
    {
        return call_user_func_array([$this->router, 'group'], func_get_args());
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
            $this->notFound();
            $this->response->send();
            return;
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

        foreach($actions as $i => $action) {
            $index = $i+1;
            $type = $i == $index_controller? 'controller' : 'middleware';
            $this->registerAction($index, $action, $type);
        };
    }

    /**
     * Register an action into container
     *
     * @param   int $index
     * @param   callable $action
     * @param   string $type
     * @return  void
     */
    protected function registerAction($index, $action, $type)
    {
        $callable = $type == 'middleware'? $this->resolveMiddleware($action) : $this->resolveController($action);
        $curr_key = 'app.action.'.($index);
        $next_key = 'app.action.'.($index+1);
        
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

            $app->response->body .= $dump_string;
            if(is_string($result) OR is_numeric($result)) {
                $app->response->body .= $result;
            }

            if (
                ($type == 'middleware' AND is_null($result) AND $next)
                OR 
                ($type == 'controller' AND $next)
            ) {
                if(empty($result) OR is_string($result) OR is_numeric($result)) {
                    $app->response->body .= $dump_string.$result;
                }

                $result = $next();
            }

            return $result;
        };
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
     * Resolving middleware action
     */
    protected function resolveMiddleware($middleware_action)
    {
        if(is_string($middleware_action)) {
            $explode_params = explode(':', $middleware_action);
                
            $middleware_name = $explode_params[0];
            $params = isset($explode_params[1])? explode(',', $explode_params[1]) : [];

            // if middleware is registered, get middleware
            if(array_key_exists($middleware_name, $this->middlewares)) {
                // Get middleware. so now, callable should be string Foo@bar, Closure, or function name
                $callable = $this->middlewares[$middleware_name];
            } else {
                // othwewise, middleware_name should be Foo@bar or Foo
                $callable = $middleware_name;
            }

            return $this->resolveCallable($callable, $params);
        } else {
            return $this->resolveCallable($middleware_action);
        }
    }

    protected function resolveController($controller_action)
    {
        return $this->resolveCallable($controller_action);
    }

    /**
     * Register default macros
     */
    protected function registerDefaultMacros()
    {
        static::macro('resolveCallable', function($unresolved_callable, array $params = array()) {
            if(is_string($unresolved_callable)) {
                // in case "Foo@bar:baz,qux", baz and qux should be parameters, separate it!
                $explode_params = explode(':', $unresolved_callable);
                
                $unresolved_callable = $explode_params[0];
                if(isset($explode_params[1])) {
                    $params = array_merge($params, explode(',', $explode_params[1]));  
                } 

                // now $unresolved_callable should be "Foo@bar" or "foo",
                // if there is '@' character, transform it to array class callable
                $explode_method = explode('@', $unresolved_callable);
                if(isset($explode_method[1])) {
                    $callable = [$explode_method[0], $explode_method[1]];
                } else {
                    // otherwise, just leave it as string, maybe that was function name
                    $callable = $explode_method[0];
                }
            } else {
                $callable = $unresolved_callable;
            }

            $app = $this;
            
            // last.. wrap callable in Closure
            return function() use ($app, $callable, $params) {
                return $app->container->call($callable, $params);                    
            };
        });

        static::macro('baseUrl', function($uri) {
            $uri = '/'.trim($uri, '/');
            $base_url = trim($this->config->get('app.base_url', 'http://localhost:8000'), '/');

            return $base_url.$uri;
        });

        static::macro('indexUrl', function($uri) {
            $uri = trim($uri, '/');
            $index_file = trim($this->config->get('app.index_file', ''), '/');
            return $this->baseUrl($index_file.'/'.$uri);  
        });
        
        static::macro('routeUrl', function($route_name, array $params = array()) {
            if($route_name instanceof Route) {
                $route = $route_name;
            } else {
                $route = $app->router->findRouteByName($route_name);        
                if(! $route) {
                    throw new \Exception("Trying to get url from unregistered route named '{$route_name}'");
                }
            }

            $path = $route->getPath();
            $path = str_replace(['(',')'], '', $path);
            foreach($params as $param => $value) {
                $path = preg_replace('/:'.$param.'\??/', $value, $path);
            }

            $path = preg_replace('/\/?\:[a-zA-Z0-9._-]+/','', $path);

            return $this->indexUrl($path);
        });
        
        static::macro('redirect', function($defined_url) {
            if(preg_match('http(s)?\:\/\/', $defined_url)) {
                $url = $defined_url;
            } elseif($this->router->findRouteByName($defined_url)) {
                $url = $this->routeUrl($defined_url);
            } else {
                $url = $this->indexUrl($defined_url);
            }

            $this->hook->apply('response.redirect', [$url, $defined_url]);

            header("Location: ".$url);
            exit();
        });
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