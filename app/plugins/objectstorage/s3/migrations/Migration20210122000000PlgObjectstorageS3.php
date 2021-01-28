<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

use Hubzero\Content\Migration\Base;

// No direct access
defined('_HZEXEC_') or die();

/**
 * Migration script for adding Filesystem - AWS S3 plugin
 **/
class Migration20210122000000PlgObjectstorageS3 extends Base
{
	/**
	 * Up
	 **/
	public function up()
	{
		$this->addPluginEntry('objectstorage', 's3', 1);
	}

	/**
	 * Down
	 **/
	public function down()
	{
		$this->deletePluginEntry(',objectstorage', 's3');
	}
}
