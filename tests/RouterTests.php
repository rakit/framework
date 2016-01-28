<?php

use Rakit\Framework\App;
use Rakit\Framework\Router\Route;

class RouterTests extends PHPUnit_Framework_TestCase {

    protected $app;

    public function setUp() {
        $this->app = new App('router-test', ['app' => ['debug' => true]]);
        $this->router = $this->app->router;
    }

    public function tearDown() {
        $this->app = null;
    }

    public function testDispatchRouteGet()
    {
        $this->router->post('/route/:param', 'handler');
        $this->router->get('/route/:param', 'handler');
        $this->router->put('/route/:param', 'handler');

        $route = $this->router->dispatch('GET', '/route/value');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
        $this->assertEquals('GET', $route->getMethod());
    }

    public function testDispatchRoutePost()
    {
        $this->router->get('/route/:param', 'handler');
        $this->router->post('/route/:param', 'handler');
        $this->router->put('/route/:param', 'handler');

        $route = $this->router->dispatch('POST', '/route/value');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
        $this->assertEquals('POST', $route->getMethod());
    }

    public function testDispatchRoutePut()
    {
        $this->router->get('/route/:param', 'handler');
        $this->router->put('/route/:param', 'handler');
        $this->router->post('/route/:param', 'handler');

        $route = $this->router->dispatch('PUT', '/route/value');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
        $this->assertEquals('PUT', $route->getMethod());
    }

    public function testDispatchRoutePatch()
    {
        $this->router->get('/route/:param', 'handler');
        $this->router->patch('/route/:param', 'handler');
        $this->router->post('/route/:param', 'handler');

        $route = $this->router->dispatch('PATCH', '/route/value');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
        $this->assertEquals('PATCH', $route->getMethod());
    }

    public function testDispatchRouteDelete()
    {
        $this->router->get('/route/:param', 'handler');
        $this->router->delete('/route/:param', 'handler');
        $this->router->post('/route/:param', 'handler');

        $route = $this->router->dispatch('DELETE', '/route/value');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
        $this->assertEquals('DELETE', $route->getMethod());
    }

    public function testDispatchParameters()
    {
        $this->router->get('/route/:foo/:bar', 'handler');

        $route = $this->router->dispatch('GET', '/route/param1/param2');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);

        $params = $route->params;
        $this->assertEquals(2, count($params));
        $this->assertEquals($params['foo'], 'param1');
        $this->assertEquals($params['bar'], 'param2');
    }

    public function testDispatchOptionalParameter()
    {
        $this->router->get('/route/:foo(/:bar(/:baz))', 'handler');

        foreach(['/route/one' => 1, '/route/one/two' => 2, '/route/one/two/three' => 3] as $path => $expected_count) {
            $route = $this->router->dispatch('GET', $path);
            $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
            $this->assertEquals($expected_count, count($route->params));
        }
    }

    public function testDispatchCustomRegexParameter()
    {
        $this->router->get('/edit/:id', 'handler')->where('id', '\d{2}');

        $tests = [
            '/edit/foo'     => false,
            '/edit/12foo'   => false,
            '/edit/foo99'   => false,
            '/edit/1'       => false,
            '/edit/123'     => false,
            '/edit/11'      => true,
        ];

        foreach($tests as $path => $expected) {
            $route = $this->router->dispatch('GET', $path);
            $this->assertEquals($expected, !is_null($route));
        }
    }

    public function testRouteNaming()
    {
        $this->router->get('/login', 'handler')->name('form-login');
        $this->router->post('/login', 'handler')->name('post-login');

        $route = $this->router->findRouteByName('post-login');

        $this->assertInstanceOf('Rakit\Framework\Router\Route', $route);
        $this->assertEquals('POST', $route->getMethod());
    }

}