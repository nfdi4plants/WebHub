<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Modules\MyStorage;

use Hubzero\Module\Module;
use Request;
use User;

/**
 * Module class for displaying a user's resources
 */
class Helper extends Module
{
	/**
	 * Display module content
	 *
	 * @return  void
	 */
	public function display()
	{
		$this->no_html = Request::getInt('no_html', 0);
		$this->content = $this->getFileList()[0];

		require $this->getLayoutPath();
	}

	/**
	 * Retrieves filelist to be displayed from objectstorage plugins
	 * 
	 * @return array
	 */
	public function getFileList(){
		// Triggers the onView() event for objectstorage plugins, collects all return values in an array
		$storage_systems = Event::trigger('objectstorage.onView');
		
		return $storage_systems;
	}
}
