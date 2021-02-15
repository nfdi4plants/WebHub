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
	<label for="access-key"> de.NBI S3 Access Key:</label>
	<?php if (empty($this->access_key))
	{
		echo '<input type="text" id="access-key" name="access-key" placeholder="Please Provide an Access Key" size="33" minlength="33" maxlength="33">';
	}
	else
	{
		echo '<input type="text" id="access-key" name="aaccess-key" value="' . $this->access_key . '" size="33" minlength="33" maxlength="33">';
	}
	?>
	<label for="secret-key"> de.NBI S3 Secret Key:</label>
	<?php if (empty($this->secret_key))
	{
		echo '<input type="text" id="secret-key" name="secret-key" placeholder="Please Provide a Secret Key" size="33" minlength="33" maxlength="33">';
	}
	else
	{
		echo '<input type="text" id="secret-key" name="secret-key" value="' . $this->secret_key . '" size="33" minlength="33" maxlength="33">';
	}
	?>
	<button>Submit</button>
</form>
