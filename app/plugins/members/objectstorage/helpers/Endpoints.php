<?php

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'Settings.php';
include_once dirname(__DIR__) . DS . 'connector' . DS . 'S3.php'; 
include_once dirname(__DIR__) . DS . 'structures' . DS . 'S3Path.php'; 

class Endpoints {

    public static function createBucket(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
        $object = Request::getVar('object');

        if(empty($bucket) || !empty($prefix) || !empty($object))
        {
            $response = array();
            $response['code'] = '405';
            $response['error'] = 'Either bucket name is missing or unsupported arguments (prefix/object) were provided';
            $response['success'] = 'false';
            echo json_encode($response);
        }
        else if (strpos($bucket, '/') !== false)
        {
            $response = array();
            $response['code'] = '422';
            $response['error'] = '/ not allowed in bucket name';
            $response['success'] = 'false';
            echo json_encode($response);
        }
        else
        {
            $response = $connector->createBucket($bucket);
            if ($response->error != null || $response->code < 200 || $response->code > 299){
                $response->success = 'false';
                echo json_encode($response);
            }
        }
    }
    
    public static function info(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
        $object = Request::getVar('object');

        $path = new S3Path($bucket, $prefix, $object);
        
        $bucket = $path->getBucket();
        $prefix = $path->getPrefix();
        $object = $path->getObject();

        if (empty($prefix))
        {
            $location = $object;
        }
        else
        {
            $location = $prefix . '/' . $object;
        }

        $response = $connector->getObjectInfo($bucket, $location);
        
        if ($response->error == null && $response->code == 200)
        {
            $info = array();
            $info['File format'] = $response->headers['content-type'];
            $size = (int) $response->headers['content-length'];
            $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
            for ($i = 0; $i < count($units); $i++)
            {
                $current = $size / pow(1024, $i);
                if( $current < 1){
                    if ($i > 1)
                    {
                        $info['File size']  = '' . round($current*1024, 1) . ' ' . $units[$i-1];
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
        else
        {   
            $response->success = 'false';
            echo json_encode($response);
        }
    }

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
    
    public static function upload(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket', '', 'POST');
        $prefix = Request::getVar('prefix', '', 'POST');
        $object = Request::getVar('object', '', 'POST');
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
            // use constructor to resolve .. correctly
            $path = new S3Path($bucket, $prefix, $object);
            $data = fopen($file['tmp_name'], 'r');
            if(!empty($path->getPrefix()))
            {
                $location = $path->getPrefix() . '/' . $name;
            }
            else
            {
                $location = $name;
            }
            $response = $connector->putObject($path->getBucket(), $location, $data);
            fclose($data);
            if ($response->error != null || $response->code < 200 || $response->code > 299){
                $response->success = 'false';
                echo json_encode($response);
            }
        }
    }

    public static function delete(){
        $connector = self::getConnector();

        $bucket = Request::getVar('bucket', '', 'POST');
        $prefix = Request::getVar('prefix', '', 'POST');
        $object = Request::getVar('object', '', 'POST');

        $path = new S3Path($bucket, $prefix, $object);
        
        $bucket = $path->getBucket();
        $prefix = $path->getPrefix();
        $object = $path->getObject();

        if (empty($bucket))
        {
            $response = array();
            $response['code'] = 400;
            $response['error'] = "Either bucket name is missing.";
            $response['success'] = false;
            echo json_encode($response);
        }
        else if (!empty($object))
        {
            if (empty($prefix))
            {
                $location = $object;
            }
            else
            {
                $location = $prefix . '/' . $object;
            }
            $response = $connector->deleteObject($bucket, $location);
            if ($response->error != null || $response->code < 200 || $response->code > 299){
                $response->success = 'false';
                echo json_encode($response);
            }
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
                $responses = array();
                foreach($contents as $content)
                {
                    $prefix = explode('/', $content->Key);
                    $object = array_pop($prefix);
                    $response = $connector->deleteObject($bucket, implode('/', $prefix) . '/' . $object);
                    if ($response->error != null || $response->code < 200 || $response->code > 299){
                        $responses[] = $response;
                    }
                }
                if (count($responses) > 0)
                {
                    $responses['success'] = 'false';
                    echo json_encode($responses);
                }
            }
        }
    }

    
    public static function getConnector(){
        $access_key = Settings::getKey('access_key');
        $secret_key =  Settings::getKey('secret_key');
        return new S3($access_key, $secret_key);
    }
}