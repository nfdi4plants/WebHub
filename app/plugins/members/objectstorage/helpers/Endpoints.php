<?php

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'Settings.php';
include_once dirname(__DIR__) . DS . 'connector' . DS . 'S3.php'; 
include_once dirname(__DIR__) . DS . 'structures' . DS . 'S3Path.php'; 

class Endpoints {

    public static function collectFileNames(){

    }

    public static function delete(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
        $object = Request::getVar('object');

        if (!isset($bucket) || !isset($prefix))
        {
            echo "Either bucket name or prefix is missing.";
        }
        else if (isset($object))
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
    
    public static function upload (){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
        $path = Request::getVar('path');

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