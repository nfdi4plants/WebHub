<?php

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'Settings.php';
include_once dirname(__DIR__) . DS . 'connector' . DS . 'S3.php'; 
include_once dirname(__DIR__) . DS . 'structures' . DS . 'S3Path.php'; 

class Endpoints {

    public static function collectFileNames(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
  
        if (empty($bucket))
        {
            // prefix can be empty if we are at the top of the bucket
            //TODO: return proper value/message for missing part
            return;
        }

        $url_params['prefix'] = $prefix . '/';
        $response = $connector->getBucket($bucket, $url_params);
        $body = $response->body;
        // get bucket name
        $bucket = $body->Name;
        // process objects on the specified level
        $contents = $body->Contents;
        if (isset($contents[0]) && !isset($contents[1]))
        {
            $contents = array($contents);
        }
        
        if (isset($contents))
        {
            $fileNames = array();
            foreach($contents as $content)
            {
                $prefix = explode('/', $content->Key);
                $object = array_pop($prefix);
                $fileNames[] = $object;
            }
            echo json_encode($fileNames);
        }

    }

    public static function delete(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket', '', 'POST');
        $prefix = Request::getVar('prefix', '', 'POST');
        $object = Request::getVar('object', '', 'POST');

        if (empty($bucket) || empty($prefix))
        {
            echo "Either bucket name or prefix is missing.";
        }
        else if (!empty($object))
        {
            $response = $connector->deleteObject($bucket, $prefix . '/' . $object);
            // TODO: handle response
        }
        else
        {   
            $url_params['prefix'] = $prefix . '/';
            $response = $connector->getBucket($bucket, $url_params);
            $body = $response->body;
            // get bucket name
            $bucket = $body->Name;
            // process objects on the specified level
            $contents = $body->Contents;
            if (isset($contents[0]) && !isset($contents[1]))
            {
                $contents = array($contents);
            }
            
            if (isset($contents))
            {
                foreach($contents as $content)
                {
                    $prefix = explode('/', $content->Key);
                    $object = array_pop($prefix);
                    $response = $connector->deleteObject($bucket, implode('/', $prefix) . '/' . $object);
                }
            }
        }
    }

    public static function info(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
        $object = Request::getVar('object');

        $response = $connector->getObjectInfo($bucket, $prefix . '/' . $object);
        if ($response->error == null && $response->code == 200)
        {
            $info = array();
            $info['File format'] = $response->headers['content-type'];
            $size = (int) $response->headers['content-length'];
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            for ($i = 0; $i < count($units); $i++)
            {
                $current = $size / pow(1024, $i);
                if( $current < 1){
                    if ($i > 0)
                    {
                        $info['File size']  = '' . round($current*1000, 3) . ' ' . $units[$i-1];
                    }
                    else
                    {
                        $info['File size']  = '' . $size . ' ' . $units[0];
                    }
                    break;
                }
            }
            echo json_encode($info);
        }
    }
    
    // public static function sign(){
    //     $connector = self::getConnector();

    //     $bucket = Request::getVar('bucket');
    //     $prefix = Request::getVar('prefix');
    //     $name = Request::getVar('name');
    //     $path = empty($prefix)? $name : $prefix . '/' . $name; 
    //     if (!empty($bucket) && !empty($path))
    //     {
    //         $url = $connector->getPresignedObjectURL($bucket,$path, 'PUT');
    //         echo json_encode($url);
    //     }
    //     else
    //     {
    //         echo json_encode(array('Missing Data Error' => 'Either bucket name or path are empty'));
    //     }
    // }
    
    public static function upload(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket', '', 'POST');
        $prefix = Request::getVar('prefix', '', 'POST');
        $path = Request::getVar('path', '', 'POST');

        if (!empty($_FILES))
        {
            $file = $_FILES['file'];    
        }

        if (isset($file) && $file['error'] === 0)
        {
            if (!empty($path))
            {
                $name = $path;
            }
            else
            {
                $name = $file['name'];
            }
            $data = fopen($file['tmp_name'], 'r');
            $response = $connector->putObject($bucket, $prefix . '/' . $name, $data);
            // TODO: handle errors
            fclose($data);
        }
    }
    
    public static function getConnector(){
        $access_key = Settings::getKey('access_key');
        $secret_key =  Settings::getKey('secret_key');
        return new S3($access_key, $secret_key);
    }
}