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
		if ($user->get('id') == $member->get('id'))
		{
			$areas['objectstorage'] = 'Object Storage';
			$areas['icon'] = '2709';
			$areas['icon-class'] = 'icon-envelope';
			$areas['menu'] = $this->params->get('display_tab', 1);
		}

		return $areas;
	}

	public function onMembers($user, $member, $option, $areas)
	{
		$returnhtml = true;

		$arr = array(
			'html' => '',
			'metadata' => ''
		);

		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas))
		{
			if (!array_intersect($areas, $this->onMembersAreas($user, $member))
			 && !array_intersect($areas, array_keys($this->onMembersAreas($user, $member))))
			{
				$returnhtml = false;
			}
		}

		if ($returnhtml){
			$arr['html'] = $this->display();
		}
		// Return data
		return $arr;
	}

	private function display(){
		
		$params = $this->params;

		$view = $this->view('default', 'index')
			->set('params', $params)
			->set('option', 'com_members');

			return $view
			->setErrors($this->getErrors())
			->loadTemplate();
	}
}
