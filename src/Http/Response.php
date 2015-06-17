<?php namespace Rakit\Framework\Http;

use Rakit\Framework\App;
use Rakit\Framework\Bag;
use Rakit\Framework\MacroableTrait;
use Rakit\Framework\Router\Route;
use Rakit\Framework\Util\Arr;

class Response {

    use MacroableTrait;

    const CONTENT_TYPE_HTML = "text/html";
    const CONTENT_TYPE_JSON = "application/json";
    
    protected $http_status_messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',

        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',

        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',

        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',

        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    );

    public $body = "";

    public $dump_output = "";

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->headers = new ResponseHeaderBag;
        $this->reset();
    }

    /**
     * set http status code
     * 
     * @param int $status http status to set
     */
    public function setStatus($status)
    {
        if(!array_key_exists($status, $this->http_status_messages)) return $this;

        $this->status = (int) $status;
        return $this;
    }

    /**
     * set response content type
     * 
     * @param string $type response content type
     */
    public function setContentType($type)
    {
        $this->header["CONTENT_TYPE"] = $type;
        return $this;
    }

    /**
     * get setted response content type
     *
     * @return string response content type
     */
    public function getContentType()
    {
        return $this->header["CONTENT_TYPE"];
    }

    public function json(array $data, $status = null)
    {
        $json = json_encode($data);
        $this->setContentType(static::CONTENT_TYPE_JSON);
        $this->setStatus($status);
        $this->body = $json;

        return $this;
    }

    public function html($content, $status = null)
    {
        $this->setContentType(static::CONTENT_TYPE_HTML);
        $this->setStatus($status);
        $this->body = $content;

        return $this;
    }

    public function isJson()
    {
        return ($this->getContentType() == static::CONTENT_TYPE_JSON);
    }

    public function isHtml()
    {
        return ($this->getContentType() == static::CONTENT_TYPE_HTML);
    }

    public function reset()
    {
        return $this
            ->setContentType(static::CONTENT_TYPE_HTML)
            ->setStatus(200)
            ->clean();
    }

    public function clean()
    {
        $this->body = "";
        $this->dump_output = "";
        return $this;
    }

    public function send($output = null, $status = null)
    {
        if($output) {
            $this->body .= $output;
        }

        if($status) {
            $this->setStatus($status);
        }

        $this->writeHeaders();

        $this->app->hook->apply("response.before_send", [$this, $this->app]);

        echo $this->body;
        
        $this->app->hook->apply("response.after_send", [$this, $this->app]);
        exit();
    }

    protected function writeHeaders()
    {
        $headers = $this->headers->all(false);

        // http://stackoverflow.com/questions/6163970/set-response-status-code
        header("HTTP/1.1 ".$this->http_status_messages[$this->status], true, $this->status);

        foreach($headers as $key => $value) {
            $header = $this->normalizeHeaderKey($key).': '.$value;
            header($header);
        }
    }

    // http://en.wikipedia.org/wiki/List_of_HTTP_header_fields#Response_fields
    protected function normalizeHeaderKey($key)
    {
        return $this->headers->unresolveKey($key);
    }


}