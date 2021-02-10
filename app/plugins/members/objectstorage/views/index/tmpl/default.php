<?php

/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die('Restricted access');

$base = $this->member->link() . '/' . $this->name;

$this->css()
	->js();
?>

<div class="main-section">
	<div id="file-panel">
		<div id="files-favorites">
			<div class="item-title">
				<p class="description">Favorites</p>
				<p class="toggle-visibility">Hide</p>
			</div>
			<ul class=file-list>
				<li>Fav1</li>
				<li>Fav2</li>
				<li>Fav3</li>
			</ul>
		</div>
		<div id="files-recent">
			<div class="item-title">
				<p class="description">Recent Files</p>
				<p class="toggle-visibility">Hide</p>
			</div>
			<ul class="file-list">
				<li>Recent1</li>
				<li>Recent2</li>
				<li>Recent3</li>
			</ul>
		</div>
		<div id="files-bydate">
			<div class="item-title">
				<p class="description">All Files</p>
				<p class="toggle-visibility">Hide</p>
			</div>
			<ul class="file-list">
				<li>First</li>
				<li>Second</li>
				<li>Third</li>
			</ul>
		</div>
	</div>
	<div id="file-actions">
		<p class="description">Actions</p>
		<ul>
			<li>Upload File</li>
			<li>Upload Folder</li>
			<li>Create Folder</li>
		</ul>
	</div>
	<div id="settings">
		<p class="description">Options</p>
		<ul>
			<li><a href="<?php echo Route::url($base . '&task=settings/elixir'); ?>"">Show Elixir ID</a></li>
			<li><a href="<?php echo Route::url($base . '&task=settings/api'); ?>">Configure API Key</a></li>
		</ul>
	</div>
</div>