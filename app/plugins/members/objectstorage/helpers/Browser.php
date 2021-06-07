<?php

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'Settings.php';
include_once dirname(__DIR__) . DS . 'structures' . DS . 'S3Path.php'; 

class Browser {
    public static function getS3View()
	{
		// check if keys are set
		$keys = array(Settings::getKey('access_key'), Settings::getKey('secret_key'));
        foreach ($keys as $key)
        {
            if(!isset($key))
            {
                return array('missing_keys' => true);
            }    
        }

        $bucket = urldecode(Request::getVar('bucket'));
        $prefix = urldecode(Request::getVar('prefix'));
        $object = urldecode(Request::getVar('object'));
		
		// generate new path 
        $path = new S3Path($bucket, $prefix, $object);

        // coming into the objectstorage from somewhere else - resume from last path in session or new bucket selection
        if (empty($path->getBucket()))
        {
                return self::getBucketSelection();
        }
        // somewhere inside a bucket, but not an object event
        else if (empty($path->getPrefix()) || empty($path->getObject()))
        {
            return self::getFileSelection($path);
        }
        // objects need to be treated differently, i.e. click should trigger download
        else
        {
            return self::downloadFile($path);
        }
    }

	public static function downloadFolder($path){
		$connector = self::getConnector();
		$url_params = array('prefix', urldecode($path->getPrefix()) . '/');

		$response = $connector->getBucket(urldecode($path->getBucket()), $url_params);
		$error = self::handleError($response);
		if (isset($error)){
			return $error;
		}
		$body = $response->body;
		// get bucket name
		$bucket = $body->Name;
		// process objects on the specified level
		$contents = $body->Contents;
		// only single object present -> pack into array
		// -> the returned content for multiple items is not actually an array, but an iterable object
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
				$path = new S3Path($bucket, implode('/', $prefix), $object);
				self::downloadFile($path); 
			}
		}
	}


    private static function getBucketSelection(){
        $connector = self::getConnector();
		$url_params = array('delimiter' => '/');

		$response = $connector->getBucket('', $url_params);
		$error = self::handleError($response);
		if (isset($error)){
			return $error;
		}
		$body = $response->body;
		$buckets = $body->Buckets;
		if(isset($buckets) && !empty($buckets))
		{	
			// Pass array of buckets to view for displaying - buckets is the xml object, bucket the actual array
			return array('buckets' => $buckets->Bucket);
		}

        return array();
    }

    private static function getFileSelection($path){
        $connector = self::getConnector();
		$url_params = array('delimiter' => '/');
		if (!empty($path->getPrefix()))
		{
		 	$url_params['prefix'] = urldecode($path->getPrefix()) . '/';
		}
		$response = $connector->getBucket(urldecode($path->getBucket()), $url_params);
		$error = self::handleError($response);
		if (isset($error)){
			return $error;
		}
		$body = $response->body;
		// get bucket name
		$bucket = $body->Name;
		// process objects on the specified level
		$contents = $body->Contents;
		// only single object present -> pack into array
		// -> the returned content for multiple items is not actually an array, but an iterable object
		if (isset($contents[0]) && !isset($contents[1]))
		{
			$contents = array($contents);
		}
		
		if (isset($contents))
		{
			$files = array();
			foreach($contents as $content)
			{
				$prefix = explode('/', $content->Key);
				$object = array_pop($prefix);
				$files[] = new S3Path($bucket, implode('/', $prefix), $object);
			}
		}

		// process prefixes on the specified level
		$common_prefixes = $body->CommonPrefixes;
		if (isset($common_prefixes[0]) && !isset($common_prefixes[1]))
		{
			$common_prefixes = array($common_prefixes);
		}

		if (isset($common_prefixes))
		{ 
			$folders = array();
			foreach($common_prefixes as $prefix)
			{
				$folders[] = new S3Path($bucket, substr($prefix->Prefix, 0, -1));
			}
		}
		// pass necessary data to view
		return array('bucket' => $bucket, 'files' => $files, 'folders' => $folders, 'prefix' => $path->getPrefix());
	}

 
    private static function downloadFile($path){
        $connector = self::getConnector();

		$url = $connector->getPresignedObjectURL($path->getBucket(), $path->getPrefix() . '/' . $path->getObject());
		App::redirect($url);

		//$path->resetObject();
        //return self::getFileSelection($path);
    }



    private static function getConnector()
    {
        $access_key = Settings::getKey('access_key');
        $secret_key =  Settings::getKey('secret_key');
        return new S3($access_key, $secret_key);
    }
	

			
	private static function handleError($response)
	{
		// Handle error and display a message accordingly
		if(isset($response->error) || isset($response->code) && $response->code != 200)
		{	
			$body = $response->body;
			if (isset($response->body))
			{
				$error_code = $response->code . ' - ' . $body->Code;
				$error = array($error_code, $body->Message, $body->Resource);
			}
			else {
				$error = array($response->error['code'], $response->error['message'], '');
			}
			return array('error' => $error);
		}
	}

}