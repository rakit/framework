<?php namespace Rakit\Framework\View;

interface ViewEngineInterface {

    public function render($file, array $data = array());

}