<?php

use Rakit\Framework\App;
use Rakit\Framework\Exceptions\HttpNotFoundException;
use Rakit\Framework\Http\Request;
use Rakit\Framework\Http\Response;
use Rakit\Framework\Router\Route;

class RunAppTests extends PHPUnit_Framework_TestCase {

    protected $app;

    public function setUp() {
        $this->app = new App('test', [
            'app' => [
                'debug' => false
            ]
        ]);

        // handle 404
        $this->app->on(404, function($response) {
            return $response->setStatus(404)->html("Not Found!");
        });
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

        $this->assertResponse("GET", "/", "Hello World!", 200);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testNotFound()
    {
        $app = $this->app;

        $this->assertResponse("GET", "/unregistered-route", 'Not Found!', 404);
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

        $this->assertResponse("GET", "/foo", 'get');
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

        $this->assertResponse("POST", "/foo", 'post');
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

        $this->assertResponse("PUT", "/foo", 'put');
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

        $this->assertResponse("PATCH", "/foo", 'patch');
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

        $this->assertResponse("DELETE", "/foo", 'delete');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMethodHead()
    {
        $this->app->get("/foo", function() {
            return "head";
        });

        // in HEAD request, we don't send response body
        $this->assertResponse("HEAD", "/foo", '');
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

        $this->assertResponse("GET", "/hello/foo/12", 'foo-12');
        $this->assertResponse("GET", "/hello/bar/24", 'bar-24');
        $this->assertResponse("GET", "/hello/bar", 'Not Found!', 404);
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

        $this->assertResponse("GET", "/hello/bar", 'bar-1');
        $this->assertResponse("GET", "/hello/foo/12", 'foo-12');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testRouteParamCondition()
    {
        $this->app->get("/hello/:name/:age", function($name, $age = 1) {
            return $name."-".$age;
        })->where('age', '\d+');

        $this->assertResponse("GET", "/hello/foo/12", 'foo-12');
        $this->assertResponse("GET", "/hello/bar/baz", 'Not Found!', 404);
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

        $this->assertResponse("GET", "/foo", 'foobar');
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

        $this->assertResponse("GET", "/foo", 'FOO');
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

        $this->assertResponse("GET", "/foo", 'FOOBARBAZQUX');
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

        $this->assertResponse("GET", "/foo", 'foobar');
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

        $this->assertResponse("GET", "/foo", '{"body":"FOOBARBAZQUX"}', 200, 'application/json');
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

        $this->assertResponse("GET", "/foo", 'controller ignored');
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

        $this->assertResponse("GET", "/group/hello", 'IM IN GROUP');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testResponseJson()
    {
        $this->app->get('/anything.json', function() {
            return [
                'message' => 'hello'
            ];
        });

        $this->assertResponse("GET", "/anything.json", '{"message":"hello"}', 200, 'application/json');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testMiddlewareKeepResponseToJson()
    {
        $this->app->middleware('uppercase', function($req, $res, $next) {
            $next();
            return strtoupper($res->body);
        });

        $this->app->get('/anything.json', function() {
            return [
                'message' => 'hello'
            ];
        })->uppercase();

        $this->assertResponse("GET", "/anything.json", '{"MESSAGE":"HELLO"}', 200, 'application/json');
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

        $this->assertResponse("GET", "/u/foobar/profile", 'foobar profile');
        $this->assertResponse("GET", "/u/foobar/123", 'Not Found!', 404);
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

        $this->assertResponse("GET", "/group/hello", 'foobar');
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

        $this->assertResponse("GET", "/hello", '<h1>Hello World!</h1>');
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

        $this->assertResponse("GET", "/hello", '<h1>Hello John!</h1>');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testException()
    {
        $this->app->get("/error", function(Response $response) {
            throw new \Exception;
        });

        $this->assertResponse("GET", "/error", 'Something went wrong', 500);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState enabled
     */
    public function testHandleException()
    {
        $this->app->handle('InvalidArgumentException', function($e, Response $response) {
            return $response->html("Invalid Argument", 501);
        });

        $this->app->get("/error", function(Response $response) {
            throw new \InvalidArgumentException;
        });

        $this->assertResponse("GET", "/error", 'Invalid Argument', 501);
    }

    protected function runAndGetResponse($method, $path)
    {
        $this->app->request->server['REQUEST_METHOD'] = $method;

        //buffer output, so output won't appear in terminal
        ob_start();
        $this->app->run($method, $path);
        $rendered = ob_get_clean();

        $response = clone $this->app->response;
        $this->app->response->reset();

        return [$response, $rendered];
    }

    protected function assertResponse($method, $path, $assert_body, $assert_status = 200, $assert_content_type = 'text/html')
    {
        $at = $method.' '.$path.' => '.$assert_body;
        list($response, $rendered) = $this->runAndGetResponse($method, $path);

        $this->assertEquals($rendered, $assert_body, $at);
        $this->assertEquals($response->getStatus(), $assert_status, $at);
        $this->assertEquals($response->getContentType(), $assert_content_type, $at);
    }

}