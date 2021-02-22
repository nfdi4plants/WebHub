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
 * Migration script for installing shibboleth tables
 **/
class Migration20210123000000PlgMembersObjectstorage extends Base
{
	/**
	 * Up
	 **/
	public function up()
	{
		if (!$this->db->tableExists('#__objectstorage'))
		{
			$query = "CREATE TABLE `#__objectstorage` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) unsigned NOT NULL,
			  `access_key` varchar(32) NOT NULL,
			  `secret_key` varchar(32) NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `id` (`id`),
			  UNIQUE KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

			$this->db->setQuery($query);
			$this->db->query();
		}
	}

	/**
	 * Down
	 **/
	public function down()
	{
		if ($this->db->tableExists('#__objectstorage'))
		{
			$query = "DROP TABLE IF EXISTS `#__objectstorage`;";
			$this->db->setQuery($query);
			$this->db->query();
		}
	}
}
