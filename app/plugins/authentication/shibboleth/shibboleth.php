<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

use Hubzero\Utility\Cookie;

/**
 * Authentication Plugin class for Shibboleth/InCommon
 */
class plgAuthenticationShibboleth extends \Hubzero\Plugin\Plugin
{
		/**
	 * Actions to perform when logging in a user session
	 *
	 * @param   unknown &$credentials Parameter description (if any) ...
	 * @param   array &$options Parameter description (if any) ...
	 * @return  void
	 */
	public function login(&$credentials, &$options)
	{
		if ($return = Request::getString('return', ''))
		{
			$return = base64_decode($return);
			if (!\Hubzero\Utility\Uri::isInternal($return))
			{
				$return = '';
			}
		}
		$options['return'] = $return;

		// If someone is logged in already, then we're linking an account
		if (!User::get('guest'))
		{
			list($service, $com_user, $task) = self::getLoginParams();
			App::redirect($service . '/index.php?option=' . $com_user . '&task=' . $task . '&authenticator=shibboleth&shib-session=' . urlencode($_COOKIE['shib-session']));
		}

		// Extract variables set by mod_shib, if any
		if (($sid = isset($_SERVER['REDIRECT_Shib-Session-ID']) ? $_SERVER['REDIRECT_Shib-Session-ID'] : (isset($_SERVER['Shib-Session-ID']) ? $_SERVER['Shib-Session-ID'] : null)))
		{
			$attrs = array(
				'id' => $sid,
				'idp' => isset($_SERVER['REDIRECT_Shib-Identity-Provider']) ? $_SERVER['REDIRECT_Shib-Identity-Provider'] : $_SERVER['Shib-Identity-Provider']
			);
			foreach (array('email', 'eppn', 'displayName', 'givenName', 'sn', 'mail') as $key)
			{
				if (isset($_SERVER[$key]))
				{
					$attrs[$key] = $_SERVER[$key];
				}
				elseif (isset($_SERVER['REDIRECT_'.$key]))
				{
					$attrs[$key] = $_SERVER['REDIRECT_'.$key];
				}
			}
			if (isset($attrs['mail']) && strpos($attrs['mail'], '@'))
			{
				$attrs['email'] = $attrs['mail'];
				unset($attrs['mail']);
			}
			// Normalize things a bit
			if (!isset($attrs['username']) && isset($attrs['eppn']))
			{
				$attrs['username'] = preg_replace('/@.*$/', '', $attrs['eppn']);
			}
			// Eppn is sometimes or maybe always in practice an email address
			if (!isset($attrs['email']) && isset($attrs['eppn']) && strpos($attrs['eppn'], '@'))
			{
				$attrs['email'] = $attrs['eppn'];
			}
			if (!isset($attrs['displayName']) && isset($attrs['givenName']) && $attrs['sn'])
			{
				$attrs['displayName'] = $attrs['givenName'].' '.$attrs['sn'];
			}
			$options['shibboleth'] = $attrs;

			$key = trim(base64_encode(openssl_random_pseudo_bytes(128)));
			setcookie('shib-session', $key);
			$dbh = App::get('db');
			$dbh->setQuery('INSERT INTO `#__shibboleth_sessions` (session_key, data) VALUES('.$dbh->quote($key).', '.$dbh->quote(json_encode($attrs)).')');
			$dbh->execute();
		}
	}

		/**
	 * Summary (if any) ...
	 *
	 * @return  array  Array of service, user, and task
	 */
	private static function getLoginParams()
	{
		$service = rtrim(Request::base(), '/');

		if (empty($service))
		{
			$service = $_SERVER['HTTP_HOST'];
		}

		$com_user = 'com_users';
		$task     = (User::isGuest()) ? 'user.login' : 'user.link';

		return array($service, $com_user, $task);
	}

	/**
	 * @access  public
	 * @param   array  $options
	 * @return  void
	 */
	public function link($options = array())
	{
		$session_data = $this->sessionData();
		if (isset($session_data))
		{	
			// TODO: change for keycloak
			// Get unique username
			$username = $session_data['eppn'];
			$hzad = \Hubzero\Auth\Domain::getInstance('authentication', 'shibboleth', $session_data['idp']);

			if (\Hubzero\Auth\Link::getInstance($hzad->id, $username))
			{
				App::redirect(
					Route::url('index.php?option=com_members&id=' . User::get('id') . '&active=account'),
					'This account appears to already be linked to a hub account',
					'error'
				);
			}
			else
			{
				$hzal = \Hubzero\Auth\Link::find_or_create('authentication', 'shibboleth', $session_data['idp'], $username);
				// if `$hzal` === false, then either:
				//    the authenticator Domain couldn't be found,
				//    no username was provided,
				//    or the Link record failed to be created
				if ($hzal)
				{
					$hzal->set('user_id', User::get('id'));
					$hzal->set('email', $session_data['email']);
					$hzal->update();
				}
				else
				{
					Log::error(sprintf('Hubzero\Auth\Link::find_or_create("authentication", "shibboleth", %s, %s) returned false', $session_data['idp'], $username));
				}
			}
		}
		else
		{
			// User somehow got redirect back without being authenticated (not sure how this would happen?)
			App::redirect(Route::url('index.php?option=com_members&id=' . User::get('id') . '&active=account'), 'There was an error linking your account, please try again later.', 'error');
		}
	}

	/**
	 * Return session data from current session based on session key in cookie or global sessiond data
	 *
	 * @access  public
	 * @return  Array session data
	 */
	public function sessionData()
	{	
		// Get session key from either the session cookie or from global session
		if (isset($_COOKIE['shib-session'])) 
		{
			$key = trim($_COOKIE['shib-session']);
		} 
		else if (isset($_GET['shib-session']))
		{
			$key = trim($_GET['shib-session']);
		}
		
		// fetch session data for session key
		if (isset($key))
		{
			$db = App::get('db');
			$db->setQuery('SELECT data FROM `#__shibboleth_sessions` WHERE session_key = '.$db->quote($key));
			$db->execute();
			if (($sess = $db->loadResult()))
			{
				return json_decode($sess, true);
			}
		}
		return array();
	}


	/**
	 * Fetches list of active IDPs set in admin area
	 *
	 * @return array Active IDPs to select from
	 */
	private static function getInstitutions()
	{
		// Get plugin data in static context
		$plugin = Plugin::byType('authentication', 'shibboleth');
		// Get institutions as associative array
		$params = json_decode($plugin->params);
		$inst = json_decode($params->institutions, true);
		$inst = isset($inst['activeIdps']) ? $inst['activeIdps'] : [];
		return $inst;
	}

	/**
	 * Gets the link domain name
	 *
	 * @param   int  $adid  The auth domain ID
	 * @return  string
	 **/
	public static function getLinkIndicator($adid)
	{
		\Hubzero\Document\Assets::addPluginStylesheet('authentication', 'shibboleth', 'shibboleth.css');
		$dbh = App::get('db');
		$dbh->setQuery('SELECT domain FROM `#__auth_domain` WHERE id = '.(int)$adid);

		// oops ... hopefully not reachable
		if (!($idp = $dbh->loadResult()) || !($label = self::getInstitutionByEntityId($idp, 'label')))
		{
			return 'InCommon';
		}

		return $label;
	}

	public static function onRenderOption($return = null, $title = 'With an affiliated institution:')
	{
		$params = Plugin::params('authentication', 'shibboleth');
		// Saved id provider? Use it as the default
		$prefill = isset($_COOKIE['shib-entity-id']) ? $_COOKIE['shib-entity-id'] : null;
		if (!$prefill && // no cookie
				($host = self::getHostByAddress(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'], $params->get('dns', '8.8.8.8'))) && // can get a host
				preg_match('/[.]([^.]*?[.][a-z0-9]+?)$/', $host, $ma))
		{ // Hostname lookup seems php jsonrational (not an ip address, has a few dots in it)
			// Try to look up a provider to pre-select based on the user's hostname
			foreach (self::getInstitutions() as $inst)
			{
				if (fnmatch('*'.$ma[1], $inst['host']))
				{
					$prefill = $inst['entity_id'];
					break;
				}
			}
		}

		// Attach style and scripts
		foreach (array('bootstrap-select.min.js', 'shibboleth.js', 'bootstrap-select.min.css', 'bootstrap-theme.min.css', 'shibboleth.css') as $asset)
		{
			$mtd = 'addPlugin'.(preg_match('/[.]js$/', $asset) ? 'script': 'stylesheet');
			\Hubzero\Document\Assets::$mtd('authentication', 'shibboleth', $asset);
		}

		list($a, $h) = self::htmlify();

		// Make a dropdown/button combo that (hopefully) gets prettied up client-side into a bootstrap dropdown
		$html = ['<div class="shibboleth account incommon-color" data-placeholder="'.$a($title).'">'];
		$html[] = '<h3>Select an affiliated institution</h3>';
		$html[] = '<ol>';
		$html = array_merge($html, array_map(function($idp) use($h, $a) {
			return '<li data-entityid="'.$a($idp['entity_id']).'" data-content="'.(isset($idp['logo_data']) ? $a($idp['logo_data']) : '').' '.$h($idp['label']).'"><a href="'.Route::url('index.php?option=com_users&view=login&authenticator=shibboleth&idp='.$a($idp['entity_id'])).'">'.$h($idp['label']).'</a></li>';
		}, self::getInstitutions()));
		$html[] = '</ol></div>';
		return $html;
	}

	/**
	 * Looks up a hostname by ip address to see if we can infer and institution
	 *
	 * We use this instead of standard php function gethostbyaddr because we need
	 * the timeout to prevent load issues.
	 *
	 * @param   string        $ip       the ip address to look up
	 * @param   string|array  $dns      the dns server to use
	 * @param   int           $timeout  the timeout after which requests should expire
	 * @return  string
	 **/
	private static function getHostByAddress($ip, $dns, $timeout=2)
	{
		try
		{
			$resolver = new Net_DNS2_Resolver(['nameservers' => (array) $dns, 'timeout' => $timeout]);
			$result   = $resolver->query($ip, 'PTR');
		}
		catch (Net_DNS2_Exception $e)
		{
			return $ip;
		}

		if ($result
		 && isset($result->answer)
		 && count($result->answer) > 0
		 && isset($result->answer[0]->ptrdname))
		{
			return $result->answer[0]->ptrdname;
		}

		return $ip;
	}


	private static function htmlify()
	{
		return array(
			function($str) { return str_replace('"', '&quot;', $str); },
			function($str) { return htmlentities($str); }
		);
	}
	
	/**
	 * Summary
	 *
	 * @param   unknown  $eid ID compared to entity_id
	 * @param   unknown  $key
	 * @return  unknown
	 */
	public static function getInstitutionByEntityId($eid, $key = null)
	{
		foreach (self::getInstitutions() as $inst)
		{
			if ($inst['entity_id'] == $eid)
			{
				return $key ? $inst[$key] : $inst;
			}
		}
		return null;
	}

	/**
	 * When linking an account, by default a parameter of the plugin is used to
	 * determine the text "link your <something> account", and failing that the
	 * plugin name is used (EX: "link your Shibboleth account").
	 *
	 * Neither is appropriate here because we want to vary the text based on the
	 * ID provider used. I don't think the average user knows what InCommon or
	 * Shibboleth mean in this context.
	 *
	 * @return  string
	 */
	public static function onGetLinkDescription()
	{
		$sess = App::get('session')->get('shibboleth.session', null);
		if ($sess && $sess['idp'] && ($rv = self::getInstitutionByEntityId($sess['idp'], 'label')))
		{
			return $rv;
		}
		// Probably only possible if the user abruptly deletes their cookies
		return 'InCommon';
	}

	/**
	 * Similar justification to that for onGetLinkDescription.
	 *
	 * We want to show a button with the name of the previously-used ID
	 * provider on it instead of something generic like "Shibboleth"
	 *
	 * @param   $return
	 * @return  string  HTML
	 */
	public static function onGetSubsequentLoginDescription($return)
	{
		// look up id provider
		if (isset($_COOKIE['shib-entity-id']) && ($idp = self::getInstitutionByEntityId($_COOKIE['shib-entity-id'])))
		{
			return '<input type="hidden" name="idp" value="'.$idp['entity_id'].'" />Sign in with '.htmlentities($idp['label']);
		}

		// if we couldn't figure out where they want to go to log in, we can't really help, so we redirect them with ?reset to get the full log-in provider list
		list($service, $com_user, $task) = self::getLoginParams();
		App::redirect($service.'/index.php?reset=1&option='.$com_user.'&task=login'.(isset($_COOKIE['shib-return']) ? '&return='.$_COOKIE['shib-return'] : $return));
	}

	/**
	 * Actions to perform when logging out a user session
	 *
	 * @return  void
	 */
	public function logout()
	{
		/**
		 * Placeholder if Shibboleth needs to perform any cleanup.
		 * CMS handles redirection.
		 **/
	}

	/**
	 * Method to display login prompt
	 *
	 * @access  public
	 * @param   object  $view  View object
	 * @param   object  $tpl   Template object
	 * @return  void
	 */
	public function display($view, $tpl)
	{
		list($service, $com_user, $task) = self::getLoginParams();
		$return = $view->return ? '&return='.$view->return : '';

		// Discovery service for mod_shib to feed back the appropriate id provider
		// entityID. See below for more info
		if (array_key_exists('wayf', $_GET))
		{
			if (isset($_GET['return']) && isset($_COOKIE['shib-entity-id']) && strpos($_GET['return'], 'https://'.$_SERVER['HTTP_HOST'].'/Shibboleth.sso/') === 0)
			{
				App::redirect($_GET['return'].'&entityID='.$_COOKIE['shib-entity-id']);
			}
			// Invalid request, back to the login page with you
			App::redirect($service.'/index.php?option='.$com_user.'&task=login'.(isset($_COOKIE['shib-return']) ? '&return='.$_COOKIE['shib-return'] : $return));
		}

		// Invalid idp in request, send back to login landing
		$eid = isset($_GET['idp']) ? $_GET['idp'] : (isset($_COOKIE['shib-entity-id']) ? $_COOKIE['shib-entity-id'] : null);
		if (!isset($eid) || !self::getInstitutionByEntityId($eid))
		{
			App::redirect($service.'/index.php?option='.$com_user.'&task=login'.$return);
		}

		// We're about to do at least a few redirects, some of which are out of our
		// control, so save a bit of state for when we get back
		//
		// We don't use the session store because we'd like it to outlive the
		// session so we can suggest this idp next time
		if (isset($_GET['idp']))
		{
			setcookie('shib-entity-id', $_GET['idp'], time()+60*60*24, '/');
		}
		// Send the request to mod_shib.
		//
		// This path should be set up in your configuration something like this:
		//
		// <Location /login/shibboleth>
		// 	AuthType shibboleth
		// 	ShibRequestSetting requireSession 1
		// 	Require valid-user
		// 	RewriteRule (.*) /index.php?option=com_users&authenticator=shibboleth&task=user.login [L]
		// </Location>
		//
		// mod_shib protects the path, and in doing so it looks at your SessionInitiators.
		// in shibobleth2.xml. ithis is what we use:
		//
		// <SessionInitiator type="Chaining" Location="/login/shibboleth" isDefault="true" id="Login">
		// 	<SessionInitiator type="SAML2" template="bindingTemplate.html"/>
		// 	<SessionInitiator type="Shib1"/>
		// 	<SessionInitiator type="SAMLDS" URL="https://dev06.hubzero.org/login?authenticator=shibboleth&amp;wayf"/>
		// </SessionInitiator>
		//
		// The important part here is the SAMLDS line pointing mod_shib right back
		// here, but with &wayf in the query string. We look for that a little bit
		// above here and feed the appropriate entity-id back to mod_shib with
		// another redirect. I wouldn't be at all surprised if there is a cleaner
		// way to communicate this that avoids the network hop. Pull request, pls
		//
		// (if you are only using one ID provider you can avoid configuring
		// SessionInitiators at all and just define that service like:
		//
		//	<SSO entityID="https://idp.testshib.org/idp/shibboleth">
		// 	SAML2 SAML1
		// </SSO>
		//
		// in which case mod_shib will not need to do discovery, having only one
		// option.
		//
		// Either way, the rewrite directs us back here to our login() method
		// where we can extract info about the authn from mod_shib
		App::redirect($service.'/login/shibboleth');
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access  public
	 * @param   array    $credentials  Array holding the user credentials
	 * @param   array    $options      Array of extra options
	 * @param   object   $response	   Authentication response object
	 * @return  boolean 
	 */
	public function onAuthenticate($credentials, $options, &$response)
	{
		return $this->onUserAuthenticate($credentials, $options, $response);
	}

	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access  public
	 * @param   array    $credentials  Array holding the user credentials
	 * @param   array    $options      Array of extra options
	 * @param   object   $response	   Authentication response object
	 * @return  boolean
	 * @since   1.5
	 */
	public function onUserAuthenticate($credentials, $options, &$response)
	{
		// eppn is eduPersonPrincipalName and is the absolute lowest common
		// denominator for InCommon attribute exchanges. We can't really do
		// anything without it
		if (isset($options['shibboleth']['eppn']))
		{
			$method = (\Component::params('com_members')->get('allowUserRegistration', false)) ? 'find_or_create' : 'find';
			$hzal = \Hubzero\Auth\Link::$method('authentication', 'shibboleth', $options['shibboleth']['idp'], $options['shibboleth']['eppn']);

			if ($hzal === false)
			{
				$response->status = \Hubzero\Auth\Status::FAILURE;
				$response->error_message = 'Unknown user and new user registration is not permitted.';
				return;
			}

			$hzal->email = isset($options['shibboleth']['email']) ? $options['shibboleth']['email'] : null;

			$response->auth_link = $hzal;
			$response->type = 'shibboleth';
			$response->status = \Hubzero\Auth\Status::SUCCESS;
			$response->fullname = isset($options['shibboleth']['displayName']) ? ucwords(strtolower($options['shibboleth']['displayName'])) : $options['shibboleth']['username'];

			if ($hzal->user_id)
			{
				$user = User::getInstance($hzal->user_id); // Bring this in line with the rest of the system

				$response->username = $user->username;
				$response->email    = $user->email;
				$response->fullname = $user->name;
			}
			else
			{
				$response->username = '-' . $hzal->id; // The Open Group Base Specifications Issue 6, Section 3.426
				$response->email    = $response->username . '@invalid'; // RFC2606, section 2

				// Also set a suggested username for their hub account
				App::get('session')->set('auth_link.tmp_username', $options['shibboleth']['username']);
			}

			$hzal->update();

			// If we have a real user, drop the authenticator cookie
			if (isset($user) && is_object($user))
			{
				// Set cookie with login preference info
				$prefs = array(
					'user_id'       => $user->get('id'),
					'user_img'      => $user->picture(0, false),
					'authenticator' => 'shibboleth'
				);

				$namespace = 'authenticator';
				$lifetime  = time() + 365*24*60*60;

				\Hubzero\Utility\Cookie::bake($namespace, $lifetime, $prefs);
			}
		}
		else
		{
			$response->status = \Hubzero\Auth\Status::FAILURE;
			$response->error_message = 'An error occurred verifying your credentials.';
		}
	}

}
