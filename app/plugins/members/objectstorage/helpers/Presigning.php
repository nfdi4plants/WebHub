<?php

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'Settings.php';
include_once dirname(__DIR__) . DS . 'connector' . DS . 'S3.php'; 
include_once dirname(__DIR__) . DS . 'structures' . DS . 'S3Path.php'; 

class Presigning {

    public static function sign(){

        $access_key = Settings::getKey('access_key');
        $secret_key =  Settings::getKey('secret_key');
        $connector =  new S3($access_key, $secret_key);

        $bucket = Request::getVar('bucket');
        $prefix = Request::getVar('prefix');
        $name = Request::getVar('name');
        $path = empty($prefix)? $name : $prefix . '/' . $name; 
        if (!empty($bucket) && !empty($path))
        {
            $url = $connector->getPresignedObjectURL($bucket,$path, 'PUT');
            echo json_encode($url);
        }
        else
        {
            echo json_encode(array('Missing Data Error' => 'Either bucket name or path are empty'));
        }
        exit();
    }
}