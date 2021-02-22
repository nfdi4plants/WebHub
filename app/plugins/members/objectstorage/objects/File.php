<?php

// No direct access
defined('_HZEXEC_') or die();

class File {

    public $name;
    public $size;
    public $modified;

    public function __construct($content)
    {
        $this->name = $content->Key;
        $this->size = $content->Size;
        $this->modified = $content->LastModified;
    }
    
}