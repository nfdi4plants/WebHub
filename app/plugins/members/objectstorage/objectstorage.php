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
			
			//check if expected task parts are present (0 is component name, 1 user id, 2 plugin name)
			if (isset($parts[3]) && $parts[3] === 'settings') 
			{
				if (isset($parts[4]) && $parts[4] === 'api') 
				{
					return $this->getSettingsAPI();
				} 
				else if (isset($parts[4]) && $parts[4]  === 'elixir') 
				{
					return $this->view('elixir', 'settings');
				}
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
		return $this->view('api', 'settings')
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

		$connector = new S3($access_key, $secret_key);
		// Top level, buckets are stored under root

		if(Request::method() === 'POST')
		{
			$bucket = urldecode(Request::getVar('bucket', '', 'POST'));
			$folder = urldecode(Request::getVar('folder', '', 'POST'));
			$file = urldecode(Request::getVar('file', '', 'POST'));
		}

		$session = App::get('session');

		$bucket = isset($bucket) && !empty($bucket) ? $bucket : $session->get('S3Bucket');
		
		if (isset($bucket))
		{
			$session->set('S3Bucket', $bucket);
		}
	
		$prefix = isset($folder) && !empty($folder) ? $folder : $session->get('S3Prefix');

		if (isset($folder) && $folder === '..'){
			$last = substr($session->get('S3Prefix'), 0, -1);
			if (!isset($last))
			{
				$folder = '';
			}
			else
			{
				$parts = explode('/', $last);
				$tail = array_pop($parts);
				if (is_null($tail))
				{
					$folder = '';
				}
				else
				{
					$folder = implode('/', $parts) . '/';
				}
			}
			$prefix = $folder;
		}

		$url_params = array('delimiter' => '/');
		if(isset($prefix))
		{
			$url_params['prefix'] = $prefix;
			$session->set('S3Prefix', $prefix);
		}

		$response = $connector->getBucket($bucket, $url_params);
		$body = $response->body;

		// Handle error and display a message accordingly
		if(isset($response->error) || isset($response->code) && $response->code != 200)
		{
			//TODO: check if anything of this actually exists in all cases
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
			$folders = array();
			foreach($common_prefixes as $prefix)
			{
				$folders[] = $prefix->Prefix;
			}

		}

		return $view->set('current', $current)->set('files', $files)->set('folders', $folders);
	}
}
