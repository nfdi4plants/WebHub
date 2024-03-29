<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

Pathway::append(
	Lang::txt('COM_WIKI_SPECIAL_RECENT_CHANGES'),
	$this->page->link()
);

$dir = strtoupper(Request::getString('dir', 'DESC'));
if (!in_array($dir, array('ASC', 'DESC')))
{
	$dir = 'DESC';
}

$filters = array('state' => array(0, 1));

if ($space = Request::getString('namespace', ''))
{
	$filters['namespace'] = urldecode($space);
}

$rows = $this->book->pages($filters)
	->including([
		'versions',
		function ($version)
		{
			$version
				->select('id')
				->select('page_id')
				->select('version')
				->select('created_by')
				->select('summary');
		}
	])
	->order('modified', $dir)
	->rows();

$altdir = ($dir == 'ASC') ? 'DESC' : 'ASC';
?>
<form method="get" action="<?php echo Route::url($this->page->link()); ?>">
	<p>
		<?php echo Lang::txt('COM_WIKI_SPECIAL_RECENT_CHANGES_ABOUT'); ?>
	</p>
	<div class="container">
		<table class="file entries">
			<thead>
				<tr>
					<th scope="col">
						<?php echo Lang::txt('COM_WIKI_COL_DIFF'); ?>
					</th>
					<th scope="col">
						<?php echo Lang::txt('COM_WIKI_COL_DATE'); ?>
					</th>
					<th scope="col">
						<?php echo Lang::txt('COM_WIKI_COL_TITLE'); ?>
					</th>
					<th scope="col">
						<?php echo Lang::txt('COM_WIKI_COL_CREATOR'); ?>
					</th>
					<th scope="col">
						<?php echo Lang::txt('COM_WIKI_COL_EDIT_SUMMARY'); ?>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
			if ($rows)
			{
				$page_ids = array();
				foreach ($rows as $row)
				{	
					// Don't show unwanted pages
					if(in_array($row->get('access'), User::getAuthorisedViewLevels()) || $row->isAuthor() && $row->param('mode') == 'knol')
					{
						$page_ids[] = $row->get('id');
					}
				}
				$filters['id'] = $page_ids;
			}
			$rows = $this->book->pages($filters)
			->including([
				'versions',
				function ($version)
				{
					$version
					->select('id')
					->select('page_id')
					->select('version')
					->select('created_by')
					->select('summary');
				}
			])
			->order('modified', $dir)
			->paginated()
			->rows();
			if ($rows)
			{
				foreach ($rows as $row)
				{
					$name = $this->escape(stripslashes($row->version->creator->get('name', Lang::txt('COM_WIKI_UNKNOWN'))));
					if (in_array($row->version->creator->get('access'), User::getAuthorisedViewLevels()))
					{
						$name = '<a href="' . Route::url($row->version->creator->link()) . '">' . $name . '</a>';
					}
					?>
					<tr>
						<td>
							(
							<?php if ($row->version->get('version') > 1) { ?>
								<a href="<?php echo Route::url($row->link() . '&' . ($this->sub ? 'action' : 'task') . '=compare&oldid=' . ($row->version->get('version') - 1). '&diff=' . $row->version->get('version')); ?>"><?php echo Lang::txt('COM_WIKI_DIFF'); ?></a> |
							<?php } else { ?>
								<?php echo Lang::txt('COM_WIKI_DIFF'); ?> |
							<?php } ?>
								<a href="<?php echo Route::url($row->link() . '&' . ($this->sub ? 'action' : 'task') . '=history'); ?>"><?php echo Lang::txt('COM_WIKI_HIST'); ?></a>
							)
						</td>
						<td>
							<time datetime="<?php echo $row->get('modified'); ?>"><?php echo $row->get('modified'); ?></time>
						</td>
						<td>
							<a href="<?php echo Route::url($row->link()); ?>">
								<?php echo $this->escape(stripslashes($row->title)); ?>
							</a>
						</td>
						<td>
							<?php echo $name; ?>
						</td>
						<td>
							<span><?php echo $this->escape(stripslashes($row->version->get('summary'))); ?></span>
						</td>
					</tr>
					<?php
				}
			}
			else
			{
				?>
				<tr>
					<td colspan="5">
						<?php echo Lang::txt('COM_WIKI_NONE'); ?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
		$pageNav = $rows->pagination;
		$pageNav->setAdditionalUrlParam('scope', $this->page->get('scope'));
		$pageNav->setAdditionalUrlParam('pagename', $this->page->get('pagename'));

		echo $pageNav;
		?>
		<div class="clearfix"></div>
	</div>
</form>