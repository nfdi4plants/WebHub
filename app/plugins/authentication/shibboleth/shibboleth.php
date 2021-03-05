<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

/**
 * Authentication Plugin class for Shibboleth/InCommon
 */
class plgAuthenticationShibboleth extends \Hubzero\Plugin\Plugin
{
	/**
	 * Actions to perform when logging in a user session
	 *
	 * @param   unknown &$credentials nothing is done with this
	 * @param   array &$options Contains return URI and processed mod_shib data is written into this
	 * @return  void
	 */
	public function login(&$credentials, &$options)
	{
		$session = App::get('session');

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
			App::redirect($service . '/index.php?option=' . $com_user . '&task=' . $task . '&authenticator=shibboleth');
		}

		// Get session id, default to null
		$sid = null;
		if (isset($_SERVER['REDIRECT_Shib-Session-ID']))
		{
			$sid = $_SERVER['REDIRECT_Shib-Session-ID'];
		}
		else if (isset($_SERVER['Shib-Session-ID']))
		{
			$sid = $_SERVER['Shib-Session-ID'];
		}

		// Extract variables set by mod_shib, if any
		if (isset($sid))
		{
			// Fetch identity provider (what is the difference here?)
			if(isset($_SERVER['REDIRECT_Shib-Identity-Provider']))
			{
				$idp = $_SERVER['REDIRECT_Shib-Identity-Provider'];
			}
			else 
			{
				$idp = $_SERVER['Shib-Identity-Provider'];
			}
			$attributes = array( 
				'id' => $sid,
				'idp' => $idp
			);
	
			// see /etc/shibboleth/attribute-map.xml and /etc/shibboleth/shibboleth2.xml
			// sn = surname, eduPersonUniqueID = elixir id
			$shibbolethAttributes = array('email', 'givenName', 'sn', 'eppn', 'eduPersonUniqueID');

			// fetch selected attributes from mod_shib
			foreach ( $shibbolethAttributes as $key)
			{
				if (isset($_SERVER[$key]))
				{
					$attributes[$key] = $_SERVER[$key];
				}
				elseif (isset($_SERVER['REDIRECT_'.$key]))
				{
					$attributes[$key] = $_SERVER['REDIRECT_'.$key];
				}
			}

			// set from fetched information
			$attributes['displayName'] = $attributes['givenName'] . ' ' . $attributes['sn'];
			// Strip domain and - from email adress for tentative user name
			$attributes['username'] = preg_replace(['/@.*$/', '~-~'], ['', ''], $attributes['email']);
			// Write data into user session
			$session->set('shibboleth_data', json_encode($attributes));

			// Write data into options (TODO: can this be safely removed?) 
			$options['shibboleth'] = $attributes;
		}
	}

	/**
	 * Fetch triple of service URL, component and task (login or link)
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
		$session = App::get('session');
		$session_data = json_decode($session->get('shibboleth_data'), true);

		if (isset($session_data))
		{	
			// Get unique username
			$username = $session_data['username'];
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
				// update the actual information
				if ($hzal)
				{
					$hzal->set('user_id', User::get('id'));
					$hzal->set('email', $session_data['email']);
					$hzal->update();
				}
				else
				{
					// if `$hzal` === false, then either:
					//    the authenticator Domain couldn't be found,
					//    no username was provided,
					//    or the Link record failed to be created
					// TODO: change this to a useful user facing error
					Log::error(sprintf('Hubzero\Auth\Link::find_or_create("authentication", "shibboleth", %s, %s) returned false', $session_data['idp'], $username));
				}
			}
		}
		else
		{
			// User somehow got redirect back without being authenticated (not sure how this would happen?)
			App::redirect(Route::url('index.php?option=com_members&id=' . User::get('id') . '&active=account'), 'No shibboleth session data present to link your account.', 'error');
		}
	}

	/**
	 * Fetches Keycloak endpoint URL
	 *
	 * @return string Keycloak URL
	 */
	private static function getEndpointURL()
	{
		// Get plugin data in static context
		$plugin = Plugin::byType('authentication', 'shibboleth');
		// Get keycloak data from admin area options
		$params = json_decode($plugin->params);
		return $params->endpoint;
	}


	/**
	 * Generate HTML for IDP button selection
	 */
	public static function onRenderOption($return = null)
	{
		// Attach style and scripts
		Hubzero\Document\Assets::addPluginScript('authentication', 'shibboleth', 'shibboleth.js');
		Hubzero\Document\Assets::addPluginStyleSheet('authentication', 'shibboleth', 'shibboleth.css');

		// fetch necessary data
		$endpoint = str_replace('"', '&quot;', self::getEndpointURL());
		$label = 'Keycloak';

		// Create a button for redirection to keycloak
		$html[] = '<div class="shibboleth account">';
		$html[] = '<button type="button" onclick=\'window.location.href="' .  Route::url('index.php?option=com_users&view=login&authenticator=shibboleth&idp=' . $endpoint) . '"\'>' . $label . '</button>';
		$html[] = '</div>';
		return $html;
	}



	/**
	 * When linking an account, by default a parameter of the plugin is used to
	 * determine the text "link your <something> account", and failing that the
	 * plugin name is used (EX: "link your Shibboleth account").
	 *
	 * @return  string
	 */
	public static function onGetLinkDescription()
	{
		return 'Elixir';
	}

	/**
	 * We want to show a button with the name of the previously-used ID
	 * provider on it instead of something generic like "Shibboleth"
	 *
	 * @param   $return
	 * @return  string  HTML
	 */
	public static function onGetSubsequentLoginDescription($return)
	{
		return '<input type="hidden" name="idp" value="' . self::getEndpointURL() . '" />Sign in with Keycloak';
	
	}

	/**
	 * Actions to perform when logging out a user session
	 * CMS handles redirection.
	 *
	 * @return  void
	 */
	public function logout()
	{
		// Session data should generally be invalidated on logout
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
		$return = $view->return ? '&return=' . $view->return : '';

		// Check if endpoint URL is set
		if (!self::getEndpointURL())
		{
			// missing idp in request, send back to login landing
			App::redirect($service . '/index.php?option=' . $com_user . '&task=login' . $return);
		}
		// The rewrite directs us back here to our login() method
		// where we can extract info about the authn from mod_shib
		App::redirect($service . '/login/shibboleth');
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
		if (isset($options['shibboleth']['username']))
		{
			$method = Component::params('com_members')->get('allowUserRegistration', false) ? 'find_or_create' : 'find';
			$hzal = Hubzero\Auth\Link::$method('authentication', 'shibboleth', $options['shibboleth']['idp'], $options['shibboleth']['username']);

			if ($hzal === false)
			{
				$response->status = \Hubzero\Auth\Status::FAILURE;
				$response->error_message = 'Unknown user and new user registration is not permitted.';
				return;
			}

			$hzal->email = $options['shibboleth']['email'];

			$response->auth_link = $hzal;
			$response->type = 'shibboleth';
			$response->status = \Hubzero\Auth\Status::SUCCESS;
			$response->fullname = ucwords(strtolower($options['shibboleth']['displayName']));

			if ($hzal->user_id)
			{
				$user = User::getInstance($hzal->user_id); // Bring this in line with the rest of the system

				$response->username = $user->username;
				$response->email    = $user->email;
				$response->fullname = $user->name;
			}
			else
			{
				// The Open Group Base Specifications Issue 6, Section 3.426
				$response->username = '-' . $hzal->id;
				// RFC2606, section 2
				$response->email    = $response->username . '@invalid';

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
