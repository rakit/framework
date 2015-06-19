<?php namespace Rakit\Framework\View;

use RuntimeException;

class BasicViewEngine implements ViewEngineInterface {

    protected $view_path;

    public function __construct($view_path)
    {
        $this->view_path = $view_path;
    }

    public function render($file, array $data = array())
    {
        $view_file = $this->view_path.'/'.$file;

        if(!file_exists($view_file)) {
            throw new RuntimeException("Cannot render view '{$view_file}', file not found", 1);
        }

        $render = function($__file, array $__data) {
            extract($__data);

            ob_start();
            include($__file);
            return ob_get_clean();
        }

        return $render($view_file, $data);
    }

}