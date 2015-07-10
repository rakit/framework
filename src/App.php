<?php namespace Rakit\Framework;

use ArrayAccess;
use Closure;
use Exception;
use InvalidArgumentException;
use Rakit\Framework\Exceptions\HttpErrorException;
use Rakit\Framework\Exceptions\HttpNotFoundException;
use Rakit\Framework\Http\Request;
use Rakit\Framework\Http\Response;
use Rakit\Framework\Router\Route;
use Rakit\Framework\Router\Router;
use Rakit\Framework\View\View;

class App implements ArrayAccess {

    use MacroableTrait;

    const VERSION = '0.0.1';

    protected static $instances = [];

    protected static $default_instance = 'default';

    public $container;

    protected $name;

    protected $booted = false;

    protected $middlewares = array();

    protected $waiting_list_providers = array();

    protected $providers = array();

    protected $exception_handlers = array();

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
        $this['app'] = $this;
        $this['config'] = new Configurator($configs);
        $this['router'] = new Router($this);
        $this['hook'] = new Hook($this);
        $this['request'] = new Request($this);
        $this['response'] = new Response($this);

        static::$instances[$name] = $this;

        if(count(static::$instances) == 1) {
            static::setDefaultInstance($name);
        }

        $this->registerBaseHooks();
        $this->registerDefaultMacros();
        $this->registerBaseProviders();
    }

    /**
     * Register a Service Provider into waiting lists
     *
     * @param   string $class
     */
    public function provide($class)
    {
        $this->providers[$class] = $provider = $this->container->make($class);
        if(false === $provider instanceof Provider) {
            throw new InvalidArgumentException("Provider {$class} must be instance of Rakit\\Framework\\Provider", 1);
        }

        $provider->register();
    }

    /**
     * Register hook
     *
     * @param   string $event
     * @param   Closure $callable
     */
    public function on($event, Closure $callable)
    {
        return $this->hook->on($event, $callable);
    }

    /**
     * Register hook once
     *
     * @param   string $event
     * @param   Closure $callable
     */
    public function once($event, Closure $callable)
    {
        return $this->hook->once($event, $callable);
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
        $this->middlewares[$name] = $callable;
    }

    /**
     * Register GET route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function get($path, $action)
    {
        return $this->route('GET', $path, $action);
    }

    /**
     * Register POST route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function post($path, $action)
    {
        return $this->route('POST', $path, $action);
    }

    /**
     * Register PUT route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function put($path, $action)
    {
        return $this->route('PUT', $path, $action);
    }

    /**
     * Register PATCH route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function patch($path, $action)
    {
        return $this->route('PATCH', $path, $action);
    }

    /**
     * Register DELETE route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function delete($path, $action)
    {
        return $this->route('DELETE', $path, $action);
    }

    /**
     * Register Group route
     *
     * @param   string $path
     * @param   Closure $grouper
     * @return  Rakit\Framework\Routing\Route
     */
    public function group($prefix, Closure $grouper)
    {
        return $this->router->group($prefix, $grouper);
    }

    /**
     * Registering a route
     *
     * @param   string $path
     * @param   mixed $action
     * @return  Rakit\Framework\Routing\Route
     */
    public function route($methods, $path, $action)
    {
        return $this->router->register($methods, $path, $action);
    }

    /**
     * Booting app
     *
     * @return  boolean
     */
    public function boot()
    {
        if($this->booted) return false;

        $providers = $this->providers;
        foreach($providers as $provider) {
            $provider->boot();
        }

        // reset providers, we don't need them anymore
        $this->providers = [];

        return $this->booted = true;
    }

    /**
     * Run application
     *
     * @param   string $path
     * @param   string $method
     * @return  void
     */
    public function run($method = null, $path = null)
    {
        try {
            $this->boot();

            $path = $path ?: $this->request->path();
            $method = $method ?: $this->request->server['REQUEST_METHOD'];
            $matched_route = $this->router->findMatch($path, $method);

            if(!$matched_route) {
                throw new HttpNotFoundException();
            }

            $this->request->defineRoute($matched_route);
            $this->hook->apply(strtoupper($method), [$matched_route, $this]);

            $middlewares = $matched_route->getMiddlewares();
            $action = $matched_route->getAction();

            $actions = $this->makeActions($middlewares, $action);
            if(isset($actions[0])) {
                $actions[0]();
            }

            $this->response->send();

            return $this;
        } catch (Exception $e) {
            if($e instanceof HttpNotFoundException) {
                $this->response->setStatus(404);
            } else {
                $this->response->setStatus($e->getCode());
            }

            // because we register exception by exception() method,
            // we will manually catch exception class
            // first we need to get exception class
            $exception_class = get_class($e);

            // then we need parent classes too
            $exception_classes = array_values(class_parents($exception_class));
            array_unshift($exception_classes, $exception_class);

            // now $exception_classes should be ['CatchedException', 'CatchedExceptionParent', ..., 'Exception']
            // next, we need to get exception handler
            $handler = null;
            foreach($exception_classes as $xclass) {
                if(array_key_exists($xclass, $this->exception_handlers)) {
                    $handler = $this->exception_handlers[$xclass];
                    break;
                }
            }

            if(!$handler) {
                $handler = function(Exception $e, App $app) {
                    return $app->response->html($e->getMessage());
                };
            }

            $this->hook->apply('error', [$e]);
            $this->container->call($handler, [$e]);
            $this->response->send();

            return $this;
        }
    }

    /**
     * Handle specified exception
     */
    public function exception(Closure $fn)
    {
        $dependencies = Container::getCallableDependencies($fn);
        $exception_class = $dependencies[0];

        if(is_subclass_of($exception_class, 'Exception') OR $exception_class == 'Exception') {
            $this->exception_handlers[$exception_class] = $fn;
        } else {
            throw new InvalidArgumentException("Parameter 1 of exception handler must be instanceof Exception or Exception itself", 1);
        }
    }

    /**
     * Stop application
     *
     * @return void
     */
    public function stop()
    {
        $this->hook->apply("app.exit", [$this]);
        exit();
    }

    /**
     * Not Found
     */
    public function notFound()
    {
        throw new HttpNotFoundException();
    }

    /**
     * Abort app
     *
     * @param   int $status
     *
     * @return  void
     */
    public function abort($status, $message = null)
    {
        if($status == 404) {
            throw new HttpNotFoundException;
        } else {
            throw new HttpErrorException;
        }
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

        $actions = [];
        foreach($middlewares as $i => $action) {
            $actions[] = new ActionMiddleware($this, $i, $action);
        }

        $actions[] = new ActionController($this, count($middlewares), $controller);

        $this['actions'] = $actions;
        return $actions;
    }

    /**
     * Resolving middleware action
     */
    public function resolveMiddleware($middleware_action, array $params = array())
    {
        if(is_string($middleware_action)) {
            $explode_params = explode(':', $middleware_action);

            $middleware_name = $explode_params[0];
            if(isset($explode_params[1])) {
                $params = array_merge($params, explode(',', $explode_params[1]));
            }

            // if middleware is registered, get middleware
            if(array_key_exists($middleware_name, $this->middlewares)) {
                // Get middleware. so now, callable should be string Foo@bar, Closure, or function name
                $callable = $this->middlewares[$middleware_name];
                $resolved_callable = $this->resolveCallable($callable, $params);
            } else {
                // othwewise, middleware_name should be Foo@bar or Foo
                $callable = $middleware_name;
                $resolved_callable = $this->resolveCallable($callable, $params);
            }
        } else {
            $resolved_callable = $this->resolveCallable($middleware_action, $params);
        }

        if(!is_callable($resolved_callable)) {
            if(is_array($middleware_action)) {
                $invalid_middleware = 'Array';
            } else {
                $invalid_middleware = $middleware_action;
            }

            throw new \Exception('Middleware "'.$invalid_middleware.'" is not valid middleware or it is not registered');
        }

        return $resolved_callable;
    }

    public function resolveController($controller_action, array $params = array())
    {
        return $this->resolveCallable($controller_action, $params);
    }

    /**
     * Register base hooks
     */
    protected function registerBaseHooks()
    {

    }

    /**
     * Register base providers
     */
    public function registerBaseProviders()
    {
        $base_providers = [
            'Rakit\Framework\View\ViewServiceProvider',
        ];

        foreach($base_providers as $provider_class) {
            $this->provide($provider_class);
        }
    }

    /**
     * Register default macros
     */
    protected function registerDefaultMacros()
    {
        $this->macro('resolveCallable', function($unresolved_callable, array $params = array()) {
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
            return !is_callable($callable)? false : function() use ($app, $callable, $params) {
                return $app->container->call($callable, $params);
            };
        });

        $this->macro('baseUrl', function($path) {
            $path = '/'.trim($path, '/');
            $base_url = trim($this->config->get('app.base_url', 'http://localhost:8000'), '/');

            return $base_url.$path;
        });

        $this->macro('asset', function($path) {
            return $this->baseUrl($path);
        });

        $this->macro('indexUrl', function($path) {
            $path = trim($path, '/');
            $index_file = trim($this->config->get('app.index_file', ''), '/');
            return $this->baseUrl($index_file.'/'.$path);
        });

        $this->macro('routeUrl', function($route_name, array $params = array()) {
            if($route_name instanceof Route) {
                $route = $route_name;
            } else {
                $route = $this->router->findRouteByName($route_name);
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

        $this->macro('redirect', function($defined_url) {
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


        $this->macro('dd', function() {
            var_dump(func_get_args());
            exit();
        });

        $app = $this;
        $this->response->macro('redirect', function($defined_url) use ($app) {
            return $app->redirect($defined_url);
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
