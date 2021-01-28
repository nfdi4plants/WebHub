<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// no direct access
defined('_HZEXEC_') or die();

if (!$this->no_html)
{
	// Push the module CSS to the template
	$this->css();
	$this->js();
?>
	<ul class="module-nav">
		<li>
			<a class="icon-browse"></a>
		</li>
	</ul>
<?php } ?>
<?php foreach($this->content as $field => $value) {
	echo '<p>' . $field . ' = ' . $value . '</p>';
} 
?>
<?php if (!$this->no_html) { ?>
	</form>
<?php }