<?php namespace Rakit\Framework\Http;

use Rakit\Framework\MacroableTrait;

class UploadedFile {

    use MacroableTrait;

    public $tmp;

    public $name;

    public $size;

    public $error;

    public $mimeType;

    public $extension;

    protected $location;

    public function __construct(array $_file)
    {
        $this->tmp = $_file['tmp_name'];
        $this->size = $_file['size'];
        $this->error = $_file['error'];
        $this->mimeType = $_file['type'];
        $this->location = $this->tmp;
        $this->name = pathinfo($_file['name'], PATHINFO_FILENAME);
        $this->extension = pathinfo($_file['name'], PATHINFO_EXTENSION);
    }

    public function getFilename()
    {
        $ext = empty($this->extension)? "" : ".".$this->extension;
        return $this->name.$ext;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function move($location)
    {
        if(!is_uploaded_file($this->tmp)) return FALSE;

        $location = rtrim($location,"/");

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