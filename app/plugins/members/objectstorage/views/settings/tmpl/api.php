<?php

/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die('Restricted access');

$this->css()
	->js();
?>

<form method="POST">
	<label for="api-key"> de.NBI S3 API Key:</label>
	<?php if (empty($this->key))
	{
		echo '<input type="text" id="api-key" name="api-key" placeholder="Please Provide an API Key" size="33" minlength="33" maxlength="33">';
	}
	else
	{
		echo '<input type="text" id="api-key" name="api-key" value="' . $this->key . '" size="33" minlength="33" maxlength="33">';
	}
	?>
	<button>Submit</button>
</form>
