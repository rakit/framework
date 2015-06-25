<?php

use Rakit\Framework\App;
use Rakit\Framework\Router\Route;

class AppTests extends PHPUnit_Framework_TestCase {

    protected $app;

    public function setUp() {
        $this->app = new App('test');
    }

    public function tearDown() {
        $this->app = null;
    }

    public function testCheckDependencies()
    {
        $this->assertTrue($this->app->container->has('config'));
        $this->assertTrue($this->app->container->has('request'));
        $this->assertTrue($this->app->container->has('response'));
        $this->assertTrue($this->app->container->has('hook'));
        $this->assertTrue($this->app->container->has('view'));
    }

    public function testRegisterRoutes()
    {
        $action = function() {
            return "TEST!";
        };

        $routeGet = $this->app->get("/get", $action);
        $routePost = $this->app->post("/post", $action);
        $routePut = $this->app->put("/put", $action);
        $routePatch = $this->app->patch("/patch", $action);
        $routeDelete = $this->app->delete("/delete", $action);

        $routeGroup = $this->app->group("/group", function($group) use ($action) {
            $group->get("/one", $action);
        });

        $this->assertTrue($routeGet instanceof Route AND $routeGet->isGet());
        $this->assertTrue($routePost instanceof Route AND $routePost->isPost());
        $this->assertTrue($routePut instanceof Route AND $routePut->isPut());
        $this->assertTrue($routePatch instanceof Route AND $routePatch->isPatch());
        $this->assertTrue($routeDelete instanceof Route AND $routeDelete->isDelete());
        $this->assertTrue($this->app->router->findMatch('/group/one', 'GET') instanceof Route);
        $this->assertEquals(6, count($this->app->router->getRoutes()));
    }

}