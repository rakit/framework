<?php 

namespace Rakit\Framework\Http;

use Rakit\Framework\MacroableTrait;

class UploadedFile {

    use MacroableTrait;

    public $name;

    public $extension;

    protected $tmp;

    protected $size;

    protected $error;

    protected $mimeType;

    protected $location;

    protected $originalName;

    public function __construct(array $_file)
    {
        $this->tmp = $_file['tmp_name'];
        $this->size = $_file['size'];
        $this->error = $_file['error'];
        $this->mimeType = $_file['type'];
        $this->location = $this->tmp;
        $this->originalName = $_file['name'];
        $this->name = pathinfo($_file['name'], PATHINFO_FILENAME);
        $this->extension = pathinfo($_file['name'], PATHINFO_EXTENSION);
    }

    public function getClientOriginalName()
    {
        return $this->originalName;
    }

    public function getFilename()
    {
        $ext = empty($this->extension)? "" : ".".$this->extension;
        return $this->name.$ext;
    }

    public function getTemporaryFile()
    {
        return $this->tmp;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function move($location, $filename = null)
    {
        if ($filename) {
            $pathinfo = pathinfo($filename);
            $this->extension = $pathinfo['extension'];
            $this->name = $pathinfo['filename'];
        }

        if(!is_uploaded_file($this->tmp)) return FALSE;

        $location = rtrim($location, "/");

        if(!is_dir($location)) {
            throw new \RuntimeException("Upload directory '{$location}' not found", 1);
        } else if(!is_writable($location)) {
            throw new \RuntimeException("Upload directory '{$location}' is not writable", 2);
        }
        
        $filepath = $location."/".$this->getFilename();

        move_uploaded_file($this->tmp, $filepath);   
        
        $has_moved = (false == is_uploaded_file($this->tmp));

        if($has_moved) {
            $this->location = $filepath;
        } else {
            throw new \RuntimeException("
                Upload file failed because unexpected reason. 
                Maybe there is miss configuration in your php.ini settings"
            , 3);
        }
    }

    public function __toString()
    {
        return (string) file_get_contents($this->getLocation());
    }

}