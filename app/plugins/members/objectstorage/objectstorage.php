<?php

/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

include_once __DIR__ . DS . 'connector' . DS . 'S3.php';
include_once __DIR__ . DS . 'helpers' . DS . 'Browser.php';
include_once __DIR__ . DS . 'helpers' . DS . 'Presigning.php';
include_once __DIR__ . DS . 'helpers' . DS . 'Settings.php';

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

			// get html by task (none, settings, sign)
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

				$params = Settings::getSettingsAPI(); 
				$view = $this->view('settings', 'endpoint');
			}
			else if (isset($parts[3]) && $parts[3] === 'sign') 
			{
				Presigning::sign();
				exit();
			}
			else
			{
				$params = Browser::getS3View();
				$view = $this->view('default', 'endpoint');
			}

			foreach ($params as $key => $value)
			{
				$view->set($key, $value);
			}
			return $view;
		}
	}
}