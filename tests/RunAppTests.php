<?php

use Rakit\Framework\App;
use Rakit\Framework\Http\Request;
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

        $this->app->notFound(function() use ($app){
            $app->response->send("Not Found!");
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
        $this->app->get("/hello/:name/:age", function($name, $age) {
            return $name."-".$age;
        });

        $this->assertEquals("foo-12", $this->runAndGetOutput("GET", "/hello/foo/12"));
        $this->assertEquals("bar-24", $this->runAndGetOutput("GET", "/hello/bar/24"));
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
    public function testMiddlewareBefore()
    {
        $this->app->middleware('foobar', function($req, $res, $next) {
            $req->foobar = "foobar";
            return $next();
        });

        $this->app->get("/foo", ['foobar'], function(Request $request) {
            return $request->foobar;
        });

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

        $this->app->get("/foo", ['uppercase'], function(Request $request) {
            return "foo";
        });

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

        $this->app->get("/foo", ['uppercase'], function(Request $request) {
            return $request->foobar."bazQux";
        });

        $this->assertEquals("FOOBARBAZQUX", $this->runAndGetOutput("GET", "/foo"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMultipleMiddleware()
    {
        $this->app->middleware('foobar', function($req, $res, $next) {
            $req->foobar = "foobar";
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

        $this->app->get("/foo", ['foobar', 'uppercase', 'jsonify'], function(Request $request) {
            return $request->foobar."bazQux";
        });

        $this->assertEquals('{"BODY":"FOOBARBAZQUX"}', $this->runAndGetOutput("GET", "/foo"));
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

        $this->app->get("/foo", ['no-controller'], function(Request $request) {
            return "foobar";
        });

        $this->assertEquals('controller ignored', $this->runAndGetOutput("GET", "/foo"));
    }

    protected function runAndGetOutput($method, $path)
    {
        ob_start();
        $this->app->run($method, $path);
        $this->app->response->reset();
        return ob_get_clean();
    }

}