<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

use Hubzero\Pagination\Paginator;
use Hubzero\View\Helper\Pagination;

// No direct access.
defined('_HZEXEC_') or die();

Pathway::append(
	Lang::txt('COM_WIKI_SPECIAL_SHORT_PAGES'),
	$this->page->link()
);

$limit = Request::getInt('limit', Config::get('list_limit'));
$start = Request::getInt('limitstart', 0);

$filters = array('state' => array(0, 1));

$pages    = \Components\Wiki\Models\Page::blank()->getTableName();
$versions = \Components\Wiki\Models\Version::blank()->getTableName();

$rows = $this->book->pages($filters)
	->select($pages . '.*')
	->select($versions . '.created_by')
	->select($versions . '.length')
	->join($versions, $versions . '.id', $pages . '.version_id')
	->order('length', 'asc')
	->ordered()
	->paginated()
	->rows();
?>
<form method="get" action="<?php echo Route::url($this->page->link()); ?>">
	<p>
		<?php echo Lang::txt('COM_WIKI_SPECIAL_SHORT_PAGES_ABOUT', Route::url($this->page->link('base') . '&pagename=Special:Longpages')); ?>
	</p>
	<div class="container">
		<table class="file entries">
			<thead>
				<tr>
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
						<?php echo Lang::txt('COM_WIKI_COL_LENGTH'); ?>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php
			if ($rows)
			{
				foreach ($rows as $row)
				{
					// Don't show unwanted pages
					if(!$row->access() && !$row->isAuthor())
					{	
						continue;
					}
					$name = $this->escape(stripslashes($row->creator->get('name', Lang::txt('COM_WIKI_UNKNOWN'))));
					if (in_array($row->creator->get('access'), User::getAuthorisedViewLevels()))
					{
						$name = '<a href="' . Route::url($row->creator->link()) . '">' . $name . '</a>';
					}
					?>
					<tr>
						<td>
							<time datetime="<?php echo $row->get('created'); ?>"><?php echo $row->get('created'); ?></time>
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
							<?php echo Lang::txt('COM_WIKI_HISTORY_BYTES', number_format($row->get('length'))); ?>
						</td>
					</tr>
					<?php
				}
			}
			else
			{
				?>
				<tr>
					<td colspan="4">
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
		file_put_contents('/var/www/nfdi4plants4/wiki.log', print_r($pageNav, true));

		echo $pageNav;
		?>
		<div class="clearfix"></div>
	</div>
</form>