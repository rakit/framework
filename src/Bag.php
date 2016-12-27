<?php 

namespace Rakit\Framework;

use ArrayAccess;
use Rakit\Framework\Util\Arr;

class Bag implements ArrayAccess {

    protected $items = array();
    protected $namespaces = array();
     
    /**
     * Constructor
     *
     * @param   array $items
     * @return  void
     */
    public function __construct(array $items = array()) {
        $this->items = $items;
    }
     
    /**
     * Check existances of a key
     *
     * @param   string $key
     * @return  boolean
     */
    public function has($key) {
        $key = $this->resolveKey($key);
        return Arr::has($this->items, $key);
    }

    /**
     * Set item
     *
     * @param   string $key
     * @param   mixed $value
     * @return  self
     */
    public function set($key, $value) {
        $key = $this->resolveKey($key);

        if(is_array($key)) {
            $this->items = $key;
        } else {
            Arr::set($this->items, $key, $value);
        }
        
        return $this;
    }

    /**
     * Get item value by given key
     *
     * @param   string $key
     * @param   mixed $default
     * @return  mixed
     */
    public function get($key, $default = null) {
        $key = $this->resolveKey($key);

        return Arr::get($this->items, $key, $default);
    }
     
    /**
     * Remove item by given key
     *
     * @param   string $key
     * @return  self
     */
    public function remove($key) {
        $key = $this->resolveKey($key);

        Arr::remove($this->items, $key);
        return $this;
    }

    /**
     * Get items by given keys
     *
     * @param   array $keys
     * @param   mixed $default
     * @return  array
     */
    public function only($keys, $result_array = false)
    {
        $keys = (array) $keys;
        $bag = new static;
        foreach($keys as $key) {
            $key = $this->resolveKey($key);
            $bag[$key] = $this->get($key);
        }

        return $result_array? $bag->all() : $bag;
    }

    /**
     * Get all items, except given keys
     *
     * @param   array $keys
     * @return  array
     */
    public function except($keys, $result_array = false)
    {
        $keys = (array) $keys;
        $bag = new static;

        foreach($this->all(false) as $key => $value) {
            $key = $this->resolveKey($key);
            $bag->set($key, $value);
        }

        foreach($keys as $key) {
            $key = $this->resolveKey($key);
            $bag->remove($key);
        }

        return $result_array? $bag->all() : $bag;
    }

    /**
     * Get all items
     *
     * @param   bool $deep
     * @return  array
     */
    public function all($deep = true) {
        $items = $this->items;

        if($deep) {
            foreach($this->namespaces as $ns => $bag) {
                $items[$ns] = $bag->all(true);
            }
        }

        return $items;
    }

    /**
     * Get count item keys
     *
     * @param   bool $deep
     * @return  int
     */
    public function count($deep = false)
    {
        $items = $this->all($deep);
        return count($items);
    }

    /**
     * Get items size 
     *
     * @param   bool $deep
     * @return  int
     */
    public function size($deep = true)
    {
        $values = Arr::flatten($this->all($deep));
        return count($values);
    }

    public function resolveKey($key)
    {
        return $key;
    }

    /**
     * ---------------------------------------------------------------------------------------
     * ArrayAccess interface methods
     * ---------------------------------------------------------------------------------------
     */
    public function offsetSet($key, $value) {
        $this->set($key, $value);
    }

    public function offsetExists($key) {
        return $this->has($key);
    }

    public function offsetUnset($key) {
        $this->remove($key);
    }

    public function offsetGet($key) {
        return $this->get($key, null);
    }
     
    /**
     * ---------------------------------------------------------------------------------------
     * Namespace setter and getter
     * ---------------------------------------------------------------------------------------
     */
    public function __set($namespace, $value) {
        $bag = new Bag();
        $this->namespaces[$namespace] = $bag;
       
        if(is_array($value)) {
            $bag->set($value, null);
        }
    }

    public function __get($namespace) {
        if(isset($this->namespaces[$namespace])) {
            return $this->namespaces[$namespace];
        } else {
            throw new \Exception("Undefined config namespace {$namespace}");
        }
    }

}
