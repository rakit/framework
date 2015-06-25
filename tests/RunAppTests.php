<?php

use Rakit\Framework\App;
use Rakit\Framework\Exceptions\HttpNotFoundException;
use Rakit\Framework\Http\Request;
use Rakit\Framework\Http\Response;
use Rakit\Framework\Router\Route;

class RunAppTests extends PHPUnit_Framework_TestCase {

    protected $app;

    public function setUp() {
        $this->app = new App('test');
    }

    public function tearDown() {
        $this->app = null;
    }
    
    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testHelloWorld()
    {
        $this->app->get("/", function() {
            return "Hello World!";
        });

        $this->assertEquals("Hello World!", $this->runAndGetOutput("GET", "/"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testNotFound()
    {
        $app = $this->app;

        $this->app->exception(function(HttpNotFoundException $e, App $app) {
            return $app->response->setStatus(404)->html("Not Found!");
        });

        $this->app->get("/", function() {
            return "Hello World!";
        });

        $this->assertEquals("Not Found!", $this->runAndGetOutput("GET", "/unregistered-route"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMethodGet()
    {
        $this->app->get("/foo", function() {
            return "get";
        });

        $this->assertEquals("get", $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMethodPost()
    {
        $this->app->post("/foo", function() {
            return "post";
        });

        $this->assertEquals("post", $this->runAndGetOutput("POST", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMethodPut()
    {
        $this->app->put("/foo", function() {
            return "put";
        });

        $this->assertEquals("put", $this->runAndGetOutput("PUT", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMethodPatch()
    {
        $this->app->patch("/foo", function() {
            return "patch";
        });

        $this->assertEquals("patch", $this->runAndGetOutput("PATCH", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMethodDelete()
    {
        $this->app->delete("/foo", function() {
            return "delete";
        });

        $this->assertEquals("delete", $this->runAndGetOutput("DELETE", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testRouteParam()
    {
        $this->app->exception(function(HttpNotFoundException $e, App $app) {
            return $app->response->setStatus(404)->html("Not Found!");
        });

        $this->app->get("/hello/:name/:age", function($name, $age) {
            return $name."-".$age;
        });

        $this->assertEquals("foo-12", $this->runAndGetOutput("GET", "/hello/foo/12"));
        $this->assertEquals("bar-24", $this->runAndGetOutput("GET", "/hello/bar/24"));
        $this->assertEquals("Not Found!", $this->runAndGetOutput("GET", "/hello/bar"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testOptionalRouteParam()
    {
        $this->app->get("/hello/:name(/:age)", function($name, $age = 1) {
            return $name."-".$age;
        });

        $this->assertEquals("foo-12", $this->runAndGetOutput("GET", "/hello/foo/12"));
        $this->assertEquals("bar-1", $this->runAndGetOutput("GET", "/hello/bar"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testRouteParamCondition()
    {
        $this->app->exception(function(HttpNotFoundException $e, App $app) {
            return $app->response->setStatus(404)->html("Not Found!");
        });

        $this->app->get("/hello/:name/:age", function($name, $age = 1) {
            return $name."-".$age;
        })->where('age', '\d+');

        $this->assertEquals("foo-12", $this->runAndGetOutput("GET", "/hello/foo/12"));
        $this->assertEquals("Not Found!", $this->runAndGetOutput("GET", "/hello/bar/baz"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMiddlewareBefore()
    {
        $this->app->middleware('foobar', function($req, $res, $next) {
            $req->foobar = "foobar";
            return $next();
        });

        $this->app->get("/foo", function(Request $request) {
            return $request->foobar;
        })->middleware('foobar');

        $this->assertEquals("foobar", $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMiddlewareAfter()
    {
        $this->app->middleware('uppercase', function($req, $res, $next) {
            $next();
            return strtoupper($res->body);
        });

        $this->app->get("/foo", function(Request $request) {
            return "foo";
        })->uppercase();

        $this->assertEquals("FOO", $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMiddlewareBeforeAndAfter()
    {
        $this->app->middleware('uppercase', function($req, $res, $next) {
            $req->foobar = "foobar";
            
            $next();
            
            return strtoupper($res->body);
        });

        $this->app->get("/foo", function(Request $request) {
            return $request->foobar."bazQux";
        })->uppercase();

        $this->assertEquals("FOOBARBAZQUX", $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMiddlewareParam()
    {
        $this->app->middleware('setStr', function($req, $res, $next, $str) {
            $req->str = $str;
            return $next();
        });

        $this->app->get("/foo", function(Request $request) {
            return $request->str;
        })->setStr('foobar');

        $this->assertEquals("foobar", $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMultipleMiddleware()
    {
        $this->app->middleware('setStr', function($req, $res, $next, $str) {
            $req->str = $str;
            return $next();
        });

        $this->app->middleware('uppercase', function($req, $res, $next) {
            $next();
            return strtoupper($res->body);
        });

        $this->app->middleware('jsonify', function($req, $res, $next) {
            $next();
            return $res->json(['body' => $res->body]);
        });

        $this->app->get("/foo", function(Request $request) {
            return $request->str."bazQux";
        })->setStr('foobar')->jsonify()->uppercase();

        $this->assertEquals('{"body":"FOOBARBAZQUX"}', $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testIgnoringController()
    {
        $this->app->middleware('no-controller', function($req, $res, $next) {
            return "controller ignored";
        });

        $this->app->get("/foo", function(Request $request) {
            return "foobar";
        })->middleware('no-controller');

        $this->assertEquals('controller ignored', $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testRouteGroup()
    {
        $this->app->group('/group', function($group) {

            $group->get('/hello', function() {
                return "IM IN GROUP";
            });

        });

        $this->assertEquals("IM IN GROUP", $this->runAndGetOutput("GET", "/group/hello"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testRouteGroupParamCondition()
    {
        $this->app->group('/u/:username', function($group) {

            $group->get('/profile', function($username, Request $request) {
                return $username.' profile';
            });

        })->where('username', '[a-zA-Z_]+');

        $this->assertEquals("foobar profile", $this->runAndGetOutput("GET", "/u/foobar/profile"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testRouteGroupMiddleware()
    {
        $this->app->middleware('setStr', function($req, $res, $next, $str) {
            $req->str = $str;
            return $next();
        });

        $this->app->group('/group', function($group) {

            $group->get('/hello', function(Request $req) {
                return $req->str;
            });

        })->setStr('foobar');

        $this->assertEquals("foobar", $this->runAndGetOutput("GET", "/group/hello"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testResponseView()
    {
        $this->app->config['view.path'] = __DIR__.'/resources/views';

        $this->app->get("/hello", function(Response $response) {
            return $response->view('hello.php');
        });

        $this->assertEquals('<h1>Hello World!</h1>', $this->runAndGetOutput("GET", "/hello"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testPassDataIntoView()
    {
        $this->app->config['view.path'] = __DIR__.'/resources/views';

        $this->app->get("/hello", function(Response $response) {
            return $response->view('hello-name.php', [
                'name' => 'John'
            ]);
        });

        $this->assertEquals('<h1>Hello John!</h1>', $this->runAndGetOutput("GET", "/hello"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testException()
    {
        $this->app->get("/error", function(Response $response) {
            throw new \Exception("Error!", 1);
        });

        $this->assertEquals('Error!', $this->runAndGetOutput("GET", "/error"));
    }

    protected function runAndGetOutput($method, $path)
    {
        ob_start();
        $this->app->run($method, $path);
        $this->app->response->reset();
        return ob_get_clean();
    }

}