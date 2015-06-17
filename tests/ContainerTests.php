<?php

use Rakit\Framework\Container;
use Rakit\Framework\Router\Router;

/**
 * -------------------------------------------------------------------------
 * DUMMY CLASSES
 */
class Foo {

    public $foo = "foobar";

}

class DependFoo {

    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }

}

class HasMethodDependFoo {

    public function getFoo(Foo $foo)
    {
        return $foo->foo;
    }

}
/**
 * -------------------------------------------------------------------------
 *
 */

class ContainerTests extends PHPUnit_Framework_TestCase {

    protected $container;

    public function setUp() {
        $this->container = new Container();
    }

    public function tearDown() {
        $this->container = null;
    }

    public function testValueInjection()
    {
        $this->container['foo'] = "bar";

        $this->assertEquals("bar", $this->container['foo']);
    }

    public function testSingleton()
    {
        $this->container['foo'] = $this->container->singleton(function() {
            return new Foo;
        });

        $this->assertTrue($this->container['foo'] === $this->container['foo']);
    }

    public function testNotSingleton()
    {
        $this->container['foo'] = function() {
            return new Foo;
        };

        $this->assertTrue($this->container['foo'] !== $this->container['foo']);
    }

    public function testConstructorInjection()
    {
        $this->container['foo:Foo'] = $this->container->singleton(function() {
            return new Foo;
        });

        $this->container['dependFoo'] = $this->container->make('DependFoo');
        $this->assertTrue($this->container['foo'] === $this->container['dependFoo']->foo);
    }

    public function testMethodInjection()
    {
        $this->container['foo:Foo'] = $this->container->singleton(function() {
            return new Foo;
        });

        $this->assertEquals(
            $this->container['foo']->foo, 
            $this->container->call(['HasMethodDependFoo', 'getFoo'])
        );
    }

}