<?php

use Rakit\Framework\Bag;

class BagTests extends PHPUnit_Framework_TestCase {

    protected $bag;

    public function setUp() {
        $this->bag = new Bag();
    }

    public function tearDown() {
        $this->bag = null;
    }

    public function testSet()
    {   
        $this->bag->set("foo.bar", "foobar");
        
        $items = $this->bag->all();

        $this->assertEquals($items['foo']['bar'], "foobar");
    }

    public function testSetDotNotation()
    {
        $this->bag["foo.bar"] = "foobar";

        $items = $this->bag->all();

        $this->assertEquals($items['foo']['bar'], "foobar");
    }

    public function testGet()
    {   
        $this->bag->set("foo.bar", "foobar");

        $value = $this->bag->get("foo.bar");
        $undefined = $this->bag->get("undefined.key");
        $default_value = $this->bag->get("undefined.key", "default value");

        $this->assertEquals($value, "foobar");
        $this->assertEquals(NULL, $undefined);
        $this->assertEquals($default_value, "default value");
    }

    public function testGetDotNotation()
    {
        $this->bag->set("foo.bar", "foobar");

        $value = $this->bag["foo.bar"];
        $undefined = $this->bag["undefined.key"];

        $this->assertEquals($value, "foobar");
    }

    public function testHas()
    {
        $this->bag->set("foo.bar", "foobar");        

        $this->assertTrue($this->bag->has("foo.bar"));
        $this->assertFalse($this->bag->has("undefined.key"));
    }

    public function testExcept()
    {
        $this->bag->set('foo.A', 'A');
        $this->bag->set('foo.B', 'B');
        $this->bag->set('foo.C', 'C');
        $this->bag->set('bar.D', 'C');

        $new_bag = $this->bag->except(['foo.B']);

        $this->assertTrue(isset($new_bag['foo']));
        $this->assertTrue(isset($new_bag['bar']));
        $this->assertTrue(isset($new_bag['foo']['A']));
        $this->assertTrue(isset($new_bag['foo']['C']));
        $this->assertFalse(isset($new_bag['foo']['B']));
    }

    public function testOnly()
    {
        $this->bag->set('foo.A', 'A');
        $this->bag->set('foo.B', 'B');
        $this->bag->set('foo.C', 'C');

        $new_bag = $this->bag->only(['foo.A', 'foo.C']);
        
        $this->assertTrue(isset($new_bag['foo']));
        $this->assertTrue(isset($new_bag['foo']['A']));
        $this->assertTrue(isset($new_bag['foo']['C']));
        $this->assertFalse(isset($new_bag['foo']['B']));
    }

    public function testNamespace()
    {
        // add namespace store
        $this->bag->store = array(
            'store_name' => 'my store',
            'database' => array(
                'host' => 'localhost',
                'username' => 'db_user',
                'password' => 'db_password',
                'dbname' => 'db_store'
            )
        );

        // get simple value from namespace
        $store_name = $this->bag->store->get("store_name");
        $this->assertEquals($store_name, "my store");

        // get value using dot notation
        $db_host = $this->bag->store["database.host"];
        $this->assertEquals($db_host, "localhost");

        // set value using set
        $this->bag->store->set("database.host", "new_db_host");
        // set value using dot notation
        $this->bag->store->set("database.dbname", "new_store_db");

        // get all changed items store
        $items = $this->bag->store->all();

        $this->assertEquals(5, $this->bag->size());
        $this->assertEquals($items["database"]["host"], "new_db_host");
        $this->assertEquals($items["database"]["dbname"], "new_store_db");
    }

}