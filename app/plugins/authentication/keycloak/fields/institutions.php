<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Hubzero\Form\Fields;

use Hubzero\Form\Field;

class Institutions extends Field
{
	/**
	 * Get field input
	 *
	 * @return  string
	 */
	protected function getInput()
	{
		// TODO: change to relative path
		Document::addScript('/app/plugins/authentication/keycloak/assets/js/admin.js');
		Document::addStyleSheet('/app/plugins/authentication/keycloak/assets/css/admin.css');

		$html = array();
		$a = function($str)
		{
			return str_replace('"', '&quot;', $str);
		};
		$val = is_array($this->value) ? $this->value : json_decode($this->value, true);

		$html[] = '<div class="shibboleth" data-iconify="' . $a(preg_replace('#^' . preg_quote(PATH_APP) . '#', '', __FILE__)) . '">';
		$html[] = '<input type="hidden" class="serialized" name="' . $this->name . '" value="' . $a(json_encode($val)) . '" />';
		$html[] = '</div>';

		// rest of the form is managed on the client side
		return implode("\n", $html);
	}
}
