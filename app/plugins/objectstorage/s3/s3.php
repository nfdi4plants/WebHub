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
class plgObjectstorageS3 extends \Hubzero\Plugin\Plugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var  boolean
	 */
	protected $_autoloadLanguage = true;
	/**
	 *
	 * @return  object
	 **/
	public static function onView()
	{
		$content = array();

		// Retrieve configuration from admin area. Fields are defined in s3.xml
		$params = Plugin::params('objectstorage', 's3');
		if(empty($params))
		{
			$content[] = 'Found';
			$content[] = 'the';
			$content[] = 'dragons';
		}
		else
		{
			$content = $params;
		}
		
		return $content;
	}
}
