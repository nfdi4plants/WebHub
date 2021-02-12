<?php

/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

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
		return $this->view('default', 'index');
	}

	private function getSettingsAPI(){
		// get key from POST Request triggered by form, set to empty string if no Var set
		$key = Request::getVar('api-key', '', 'POST');
		// update DB
		if (!empty($key)){
			$this->updateKey($key);
		} else {
			// get key from DB, if empty
			$key = $this->getKey();
		}
		// Load correct view and make api key available
		return $this->view('api', 'settings')
			->set('key', $key);
	}

	private function getKey()
	{
		$id = User::get('id');
		$db = App::get('db');

		$query = 'SELECT api_key FROM `#__objectstorage` WHERE user_id = ' . $db->quote($id) . ';';

		// run query and fetch result
		$db->setQuery($query);
		$db->query();
		$key = $db->loadResult();

		if(!isset($key)) {
			$key = '';
		}

		return $key;
	}

	private function updateKey($key)
	{
		$id = User::get('id');
		$db = App::get('db');

		// Insert or update API key, if user id is already present
		$query = 'INSERT INTO `#__objectstorage` (user_id, api_key) VALUES(' . $db->quote($id) . ',' . $db->quote($key) . ') ON DUPLICATE KEY UPDATE api_key=' . $db->quote($key) . ';';

		$db->setQuery($query);
		$db->query();
	}
}
