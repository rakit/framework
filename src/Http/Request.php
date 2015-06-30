<?php namespace Rakit\Framework\Http;

use Rakit\Framework\App;
use Rakit\Framework\Bag;
use Rakit\Framework\MacroableTrait;
use Rakit\Framework\Router\Route;

class Request {

    use MacroableTrait;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    protected $route;

    public $params = array();

    public function __construct(App $app, Route $route = null)
    {
        if($route) $this->defineRoute($route);
        $this->app = $app;

        $this->inputs = new Bag((array) $_POST + (array) $_GET);
        $this->files = new Bag((array) $_FILES);
        $this->server = new Bag($_SERVER);
    }

    public function path()
    {
        $path_info = $this->server->get('PATH_INFO');

        if(!$path_info) {
            $path_info = preg_replace('#^'.dirname($_SERVER['SCRIPT_NAME']).'#', '', $_SERVER['REQUEST_URI']);
            $path_info = strtok($path_info, '?');
        }

        return $path_info;
    }

    public function segment($index)
    {
        $paths = explode('/', $this->path());
        return isset($paths[$index])? $paths[$index] : null;
    }

    public function all()
    {
        $all_inputs = $this->inputs->all();
        $all_files = $this->files->all();
        foreach($all_files as $key => $value) {
            $all_files[$key] = $this->hasMultiFiles($key)? $this->multiFiles($key) : $this->file($key);
        }

        return array_merge($all_inputs, $all_files);
    }

    public function has($key)
    {
        return $this->inputs->has($key);
    }

    public function get($key, $default = null)
    {
        return $this->inputs->get($key, $default);
    }

    public function only(array $keys)
    {
        return $this->inputs->only($keys, true);
    }

    public function except(array $keys)
    {
        return $this->inputs->except($keys, true);
    }

    public function file($key)
    {
        $_file = $this->files[$key];
        return $file? $this->makeInputFile($_file) : NULL;
    }

    public function multiFiles($key)
    {
        if(!$this->hasMultiFiles($key)) return array();

        $input_files = array();

        $files = $this->files[$key];

        $names = $files["name"];
        $types = $files["type"];
        $temps = $files["tmp_name"];
        $errors = $files["error"];
        $sizes = $files["size"];

        foreach($temps as $i => $tmp) {
            if(empty($tmp) OR !is_uploaded_file($tmp)) continue;

            $_file = array(
                'name' => $names[$i],
                'type' => $types[$i],
                'tmp_name' => $tmp,
                'error' => $errors[$i],
                'size' => $sizes[$i]
            );

            $input_files[] = $this->makeInputFile($_file);
        }

        return $input_files;
    }

    public function hasFile($key)
    {
        $file = $this->files[$key];

        if(!$file) return FALSE;

        $tmp = $file["tmp_name"];

        if(!is_string($tmp)) return FALSE;

        return is_uploaded_file($tmp);
    }

    public function hasMultiFiles($key)
    {
        $files = $this->files[$key];

        if(!$files) return FALSE;

        $uploaded_files = $files["tmp_name"];
        if(!is_array($uploaded_files)) return FALSE;

        foreach($uploaded_files as $tmp_file) {
            if(!empty($tmp_file) AND is_uploaded_file($tmp_file)) return TRUE;
        }

        return FALSE;
    }

    protected function makeInputFile(array $_file)
    {
        return new UploadedFile($_file);
    }

    public function defineRoute(Route $route)
    {
        if($this->route) return;

        $this->params = $route->params;
        $this->route = $route;
    }

    public function route()
    {
        return $this->route;
    }

    public function isMethod($method)
    {
        return strtoupper($this->method()) == strtoupper($method);
    }

    public function isMethodGet()
    {
        return $this->isMethod(static::METHOD_GET);
    }

    public function isMethodPost()
    {
        return $this->isMethod(static::METHOD_POST);
    }

    public function isMethodPut()
    {
        return $this->isMethod(static::METHOD_PUT);
    }

    public function isMethodPatch()
    {
        return $this->isMethod(static::METHOD_PATCH);
    }

    public function isMethodDelete()
    {
        return $this->isMethod(static::METHOD_DELETE);
    }

    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isHttps()
    {
        if( isset($_SERVER['HTTPS'] ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function isHttp()
    {
        return !$this->isHttps();
    }

    public function isAjax()
    {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }

    public function isJson()
    {
        return $this->json() !== NULL;
    }

    public function json()
    {
        $raw_body = $this->body();
        $json = json_decode($raw_body, true);

        if(is_array($json)) {
            $data = new Bag($json);
        } else {
            $data = NULL;
        }

        return $data;
    }

    public function body()
    {
        return file_get_contents("php://input");
    }

    public function param($key, $default = null)
    {
        $params = $this->params;
        return (array_key_exists($key, $params))? $params[$key] : $default;
    }

    public function params()
    {
        return $this->params;
    }

}
