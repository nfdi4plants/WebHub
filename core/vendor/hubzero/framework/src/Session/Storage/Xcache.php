<?php
/**
 * @package    framework
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Hubzero\Session\Storage;

use Hubzero\Session\Store;
use Exception;

/**
 * XCache session storage handler
 *
 * Inspired by Joomla's JSessionStorageXcache class
 */
class Xcache extends Store
{
	/**
	 * Key prefix
	 *
	 * @var  string
	 */
	private $prefix  = 'sess_';

	/**
	 * Constructor
	 *
	 * @param   array  $options  Optional parameters.
	 * @return  true
	 */
	public function __construct($options = array())
	{
		if (!self::isAvailable())
		{
			throw new Exception(\Lang::txt('JLIB_SESSION_XCACHE_EXTENSION_NOT_AVAILABLE'));
		}

		if (isset($options['prefix']))
		{
			$this->prefix = $options['prefix'];
		}

		parent::__construct($options);
	}

	/**
	 * Read the data for a particular session identifier from the SessionHandler backend.
	 *
	 * @param   string  $session_id  The session identifier.
	 * @return  string  The session data.
	 */
	public function read($session_id)
	{
		// Check if id exists
		if (!xcache_isset($this->key($session_id)))
		{
			return;
		}

		return (string) xcache_get($this->key($session_id));
	}

	/**
	 * Write session data to the SessionHandler backend.
	 *
	 * @param   string   $session_id    The session identifier.
	 * @param   string   $session_data  The session data.
	 * @return  boolean  True on success, false otherwise.
	 */
	public function write($session_id, $session_data)
	{
		return xcache_set($this->key($session_id), $session_data, ini_get("session.gc_maxlifetime"));
	}

	/**
	 * Destroy the data for a particular session identifier in the SessionHandler backend.
	 *
	 * @param   string   $session_id  The session identifier.
	 * @return  boolean  True on success, false otherwise.
	 */
	public function destroy($session_id)
	{
		if (!xcache_isset($this->key($session_id)))
		{
			return true;
		}

		return xcache_unset($this->key($session_id));
	}

	/**
	 * Build the storage key
	 *
	 * @param   string  $id  The session identifier.
	 * @return  string
	 */
	protected function key($id)
	{
		return $this->prefix . $id;
	}

	/**
	 * Test to see if the SessionHandler is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 */
	public static function isAvailable()
	{
		return (extension_loaded('xcache'));
	}
}
