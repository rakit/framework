<?php

use Rakit\Framework\App;

class HookTests extends PHPUnit_Framework_TestCase {

    protected $app, $hook;

    public function setUp() {
        $this->app = new App('');
        $this->hook = $this->app->hook;
    }

    public function tearDown() {
        $this->app = null;
        $this->hook = null;
    }

    public function testApply()
    {
        $hook = $this->hook;

        $hook->on('test', function() {
            echo "foo";
        });

        $hook->on('test', function() {
            echo "bar";
        });

        $this->assertEquals("foobar", $this->getOutput(
            function() use ($hook) {
                $hook->apply("test");
            }
        ));
    }

    public function testApplyOnceEvent()
    {
        $hook = $this->hook;

        $hook->once('test', function() {
            echo "foo";
        });

        $this->assertEquals("foo", $this->getOutput(
            function() use ($hook) {
                $hook->apply("test");
            }
        ));

        $this->assertEmpty($this->getOutput(
            function() use ($hook) {
                $hook->apply("test");
            }
        ));
    }


    public function testApplyWithDotNotation()
    {
        $hook = $this->hook;

        $hook->on('test.foo', function() {
            echo "foo";
        });

        $hook->on('test.bar.baz', function() {
            echo "barbaz";
        });

        $hook->on('test.qux', function() {
            echo "qux";
        });

        $this->assertEquals("foobarbazqux", $this->getOutput(
            function() use ($hook) {
                $hook->apply("test");
            }
        ));
    }

    protected function getOutput(\Closure $callback)
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }

}