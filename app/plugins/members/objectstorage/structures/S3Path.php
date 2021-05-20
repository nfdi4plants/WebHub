<?php

// No direct access
defined('_HZEXEC_') or die();

class S3Path {

    private $bucket, $prefix, $object;

    public function __construct($bucket, $prefix='', $object='')
    {
        $this->bucket = $bucket;
        // handle moving upwards
        if ($object == '..') 
        {
            $this->object = '';
            // are we already at the top of the bucket?
            if(empty($prefix))
            {
               $this->prefix  = '';
               $this->bucket = ''; 
            }
            else if (strpos($prefix, '/') === false)
            {
                $this->prefix = '';
            }
            else
            {
                // move upwards
                $parts = explode('/', $prefix);
                array_pop($parts);
                $this->prefix = implode('/', $parts);
            }
        }
        else
        {
            $this->prefix = $prefix;
            $this->object = $object;
        }
    }

    public function asPath()
    {
        return implode('/', array_filter($this->getParts()));
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getParts()
    {
        return array($this->bucket, $this->prefix, $this->object);
    }

    public function resetObject()
    {
        $this->object = '';
    }
}