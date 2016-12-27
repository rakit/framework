<?php 

namespace Rakit\Framework;

class Configurator extends Bag {

    use MacroableTrait;

    /**
     * Load configs from directory
     *
     * @param   string $dir
     * @return  void
     */
    public function loadDir($dir)
    {
        $files = glob($dir.'/*.php');
        foreach($files as $file) {
            $this->loadFile($file);
        }
    }

    /**
     * Merge configs from configurations in a directory
     *
     * @param   string $dir
     * @return  void
     */
    public function mergeDir($dir)
    {
        $configs = new static;
        $configs->loadDir($dir);

        $this_configs = $this->all(false);
        $merge_configs = $configs->all(false);

        $merged_configs = array_replace_recursive($this_configs, $merge_configs);

        $this->items = $merged_configs;
    }

    /**
     * Load configs from a file
     *
     * @param   string $file
     * @return  void
     */
    public function loadFile($file)
    {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $this->set($filename, require($file));
    }

    /**
     * Set configuration namespace
     *
     * @param   string $namespace
     * @param   mixed $value
     * @return  void
     */
    public function __set($namespace, $value)
    {
        $bag = new static;
        $this->namespaces[$namespace] = $bag;
       
        if(is_array($value)) {
            $bag->set($value, null);
        } elseif(is_string($value)) {
            $bag->loadDir($value);
        }
    }

}