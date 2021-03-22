<?php

/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'connector' . DS . 'Filter.php';
include_once __DIR__ . DS . 'connector' . DS . 'S3.php';
include_once __DIR__ . DS . 'objects' . DS . 'File.php';

/**
 * Plugin class for S3 Objectstorage
 */
class plgMembersObjectstorage extends \Hubzero\Plugin\Plugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var  boolean
	 */
	protected $_autoloadLanguage = true;

	public function &onMembersAreas($user, $member)
	{
		//default areas returned to nothing
		$areas = array();

		//if this is the logged in user show them
		if ($user->get('id') == $member->get('id')) {
			// defines for sidemenu button
			// first key should be plugin name for text to display on button
			$areas['objectstorage'] = 'Object Storage';
			$areas['icon'] = '2709';
			$areas['icon-class'] = 'icon-envelope';
			// should button be shown?
			$areas['menu'] = $this->params->get('display_tab', 1);
		}

		return $areas;
	}

	public function onMembers($user, $member, $option, $areas)
	{
		// is this a user request or something like ajax
		$returnhtml = true;

		// as defined in other plugins, if only html is returned this could be simplified
		// no idea, what qualifies as metadata here
		$arr = array(
			'html' => '',
			'metadata' => ''
		);

		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas)) {
			if (
				!array_intersect($areas, $this->onMembersAreas($user, $member))
				&& !array_intersect($areas, array_keys($this->onMembersAreas($user, $member)))
			) {
				$returnhtml = false;
			}
		}

		$this->member = $member;

		if ($returnhtml) {
			$params = $this->params;

			// get html by task (none, elixir-settings, api-settings)
			$view = $this->processRoute()
				->set('option', 'com_members')
				->set('params', $params)
				->set('name', $this->_name)
				->set('member', $this->member)
				->setErrors($this->getErrors())
				->loadTemplate();

			$arr['html'] = $view;
		}
		// Return data
		return $arr;
	}

	private function processRoute()
	{
		$path = Request::path();
		if (strstr($path, '/')) {
			// remove base url from path
			$path = str_replace(Request::base(true), '', $path);
			// remove index.php
			$path = str_replace('index.php', '', $path);
			// remove dangling /
			$path = trim($path, '/');
			// convert to array
			$parts = explode('/', $path);
			
			//check if expected task parts are present (0 is component name, 1 user id, 2 plugin name, 3 task)
			if (isset($parts[3]) && $parts[3] === 'settings') 
			{
				return $this->getSettingsAPI(); 
			}
		}
		return $this->getS3View();
	}

	private function getSettingsAPI(){
		// get key from POST Request triggered by form, set to empty string if no Var set
		$keys['access_key'] = Request::getVar('access-key', '', 'POST'); 
		$keys['secret_key'] = Request::getVar('secret-key', '', 'POST');
		
		foreach($keys as $key_name => $key)
		{
			if (empty($key)){
				// get key from DB, if empty
				$keys[$key_name] = $this->getKey($key_name);
			} else {
				// update DB
				$this->updateKey($key_name, $key);
			}
		}
		// Load correct view and make api key available
		return $this->view('default', 'settings')
			->set('access_key', $keys['access_key'])
			->set('secret_key', $keys['secret_key']);
	}

	private function getKey($key_name)
	{
		$id = User::get('id');
		$db = App::get('db');

		$query = 'SELECT ' . $db->quoteName($key_name) . ' FROM `#__objectstorage` WHERE user_id = ' . $db->quote($id) . ';';

		// run query and fetch result
		$db->setQuery($query);
		$db->query();
		$key = $db->loadResult();

		return $key;
	}

	private function updateKey($key_name, $key)
	{
		$id = User::get('id');
		$db = App::get('db');

		// Insert or update API key, if user id is already present
		$query = 'INSERT INTO `#__objectstorage` (user_id, ' . $db->quoteName($key_name) . ') VALUES(' . $db->quote($id) . ',' . $db->quote($key) . ') ON DUPLICATE KEY UPDATE ' . $db->quoteName($key_name) . '=' . $db->quote($key) . ';';

		$db->setQuery($query);
		$db->query();
	}

	private function getS3View()
	{
		$view  = $this->view('test', 'index');

		$access_key = $this->getKey('access_key');
		$secret_key = $this->getKey('secret_key');

		// Debug stuff
		$clear = Request::getVar('clear');
		if (isset($clear))
		{
			$session = App::get('session');
			$session->clear('S3Bucket');
			$session->clear('S3Prefix');
		}

		// Both access-key and secret key need to be set for this to work, pass information to template if one of them is missing
		if(!isset($access_key) || !isset($secret_key))
		{
			return $view->set('missing_keys', true);
		}

		// retrieve results from ajax posts in objectstorage.js
		if(Request::method() === 'POST')
		{
			$bucket = urldecode(Request::getVar('bucket', '', 'POST'));
			$folder = urldecode(Request::getVar('folder', '', 'POST'));
			$file = urldecode(Request::getVar('file', '', 'POST'));
		}

		$session = App::get('session');

		// fetch bucket name from POST or session
		$bucket = empty($bucket) ? $session->get('S3Bucket') : $bucket;
		// not empty, write current bucket to session
		if (isset($bucket))
		{
			$session->set('S3Bucket', $bucket);
		}
	
		// same as with bucket, fetch from POST oder from session
		$prefix = empty($folder) ? $session->get('S3Prefix') : $folder;

		// traverse upwards through prefixes
		if (isset($folder) && $folder === '..'){
			// get current common prefix without trailing /
			$last = substr($session->get('S3Prefix'), 0, -1);
			// folder is already empty, return to bucket overview
			if (empty($last))
			{
				$folder = '';
				$bucket = '';
				$session->set('S3Bucket', $bucket);
			}
			else
			{
				// get all prefix parts
				$parts = explode('/', $last);
				$tail = array_pop($parts);
				// empty after removal, i.e. we are at the top level of the bucket
				if (empty($parts))
				{
					$folder = '';
				}
				else
				{
					// set path without the last part
					$folder = implode('/', $parts) . '/';
				}
			}
			$prefix = $folder;
		}

		// apparently this needs to be set before the prefix, else traversal is not working
		$url_params = array('delimiter' => '/');
		// persist prefix in User session for next call and add to params for S3 call 
		if(isset($prefix))
		{
			$url_params['prefix'] = $prefix;
			$session->set('S3Prefix', $prefix);
		}
		else
		{
			$session->clear('S3Prefix');
		}

		// fetch actual response
		$connector = new S3($access_key, $secret_key);
		$response = $connector->getBucket($bucket, $url_params);
		$body = $response->body;

		// Handle error and display a message accordingly
		if(isset($response->error) || isset($response->code) && $response->code != 200)
		{
			//TODO: check if all of this actually exists in all cases
			$error_code = $response->code . ' - ' . $body->Code;
			$error = array($error_code, $body->Message, $body->Resource);
			return $view->set('error', $error);
		}
		
		// Handle top level return, i.e. we are not in any bucket here
		$buckets = $body->Buckets;
		if(isset($buckets) && !empty($buckets))
		{	
			// Pass array of buckets to view for displaying - buckets is the xml object, bucket the actual array
			return $view->set('buckets', $buckets->Bucket);
		}

		// TODO: handle marker and truncated 
		// $marker = $body->Marker;
		// $is_truncated = $body->Truncated;
		
		// get bucket name
		$current = $body->Name;
		// process objects on the specified level
		$contents = $body->Contents;
		// only single object present -> pack into array
		// -> the returned content for multiple items is not actually an array, but an iterable object
		if (!isset($contents[1]))
		{
			$contents = array($contents);
		}

		if (isset($contents))
		{
			$files = array();
			foreach($contents as $content)
			{
				$files[] = new File($content);
			}
		}

		// process prefixes on the specified level
		$common_prefixes = $body->CommonPrefixes;
		if (isset($common_prefixes))
		{
			// TODO: check if a similar thing is necessary as with the files, when only one 
			$folders = array();
			foreach($common_prefixes as $prefix)
			{
				$folders[] = $prefix->Prefix;
			}

		}

		// pass necessary data to view
		return $view->set('current', $current)->set('files', $files)->set('folders', $folders);
	}
}
