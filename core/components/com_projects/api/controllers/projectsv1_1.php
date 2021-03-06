<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Components\Projects\Api\Controllers;

use Components\Projects\Models\Project;
use Hubzero\Component\ApiController;
use Hubzero\Utility\Date;
use Exception;
use stdClass;
use Request;
use Route;
use Lang;

require_once dirname(dirname(__DIR__)) . DS . 'models' . DS . 'project.php';

/**
 * API controller for the projects component
 */
class Projectsv1_1 extends ApiController
{
	/**
	 * Display projects user belongs to
	 *
	 * @apiMethod GET
	 * @apiUri    /projects/list
	 * @apiParameter {
	 * 		"name":          "limit",
	 * 		"description":   "Number of result to return.",
	 * 		"type":          "integer",
	 * 		"required":      false,
	 * 		"default":       0
	 * }
	 * @apiParameter {
	 * 		"name":          "start",
	 * 		"description":   "Number of where to start returning results.",
	 * 		"type":          "integer",
	 * 		"required":      false,
	 * 		"default":       0
	 * }
	 * @apiParameter {
	 * 		"name":          "sort",
	 * 		"description":   "Field to sort results by.",
	 * 		"type":          "string",
	 * 		"required":      false,
	 *      "default":       "title",
	 * 		"allowedValues": "title, created, alias"
	 * }
	 * @apiParameter {
	 * 		"name":          "sort_Dir",
	 * 		"description":   "Direction to sort results by.",
	 * 		"type":          "string",
	 * 		"required":      false,
	 * 		"default":       "asc",
	 * 		"allowedValues": "asc, desc"
	 * }
	 * @apiParameter {
	 * 		"name":          "verbose",
	 * 		"description":   "Receive verbose output for project status, team member role and privacy.",
	 * 		"type":          "integer",
	 * 		"required":      false,
	 * 		"default":       "0",
	 * 		"allowedValues": "0, 1"
	 * }
	 * @return  void
	 */
	public function listTask()
	{
		// Incoming
		$verbose = Request::getInt('verbose', 0);

		$model = new Project();

		// Set filters
		$filters = array(
			'limit'      => Request::getInt('limit', 20),
			'start'      => Request::getInt('start', 0),
			'sortby'     => Request::getWord('sort', 'title', 'post'),
			'sortdir'    => strtoupper(Request::getWord('sort_Dir', 'ASC')),
			'getowner'   => 1,
			'updates'    => 1,
			'mine'       => 1
		);

		$admin = false;
		if (User::authorise('core.admin', 'com_projects'))
		{
			$searchable = Request::getBool('searchable', false);
			unset($filters['mine']);
			$admin = true;
		}

		$response = new stdClass;
		$response->projects = array();
		$response->total = $model->entries('count', $filters, $admin);

		if ($response->total)
		{
			$base = rtrim(Request::base(), '/');
			if (isset($searchable))
			{
				return false;
			}
			foreach ($model->entries('list', $filters, $admin) as $i => $entry)
			{
				if (isset($searchable))
				{
					$obj = new stdClass;
					$obj->id          = 'project-' . $entry->get('id');
					$obj->hubtype     = 'project';
					$obj->title       = $entry->get('title');
					$obj->description = $entry->get('about');
					$obj->url         = str_replace('/api', '', $base . '/' . ltrim(Route::url($entry->link()), '/'));

					$obj->owner_type = 'user';
					foreach ($entry->team() as $member)
					{
						$obj->owner[] = $member->userid;
					}

					if ($entry->isPublic() && $entry->isProvisioned())
					{
						$obj->access_level = 'public';
					}
					else
					{
						$obj->access_level = 'private';
					}
				}
				else
				{
					$obj = new stdClass;
					$obj->id            = $entry->get('id');
					$obj->alias         = $entry->get('alias');
					$obj->title         = $entry->get('title');
					$obj->description   = $entry->get('about');
					$obj->state         = $entry->get('state');
					$obj->inSetup       = $entry->inSetup();
					$obj->owner         = $entry->owner('name');
					$obj->created       = $entry->get('created');
					//$obj->userRole      = $entry->member()->role;
					$obj->thumbUrl      = str_replace('/api', '', $base . '/' . ltrim(Route::url($entry->link('thumb')), '/'));
					$obj->privacy       = $entry->get('private');
					$obj->access        = $entry->get('access');
					$obj->provisioned   = $entry->isProvisioned();
					$obj->groupOwnerId  = $entry->groupOwner('id');
					$obj->userOwnerId   = $entry->owner('id');
					$obj->uri           = str_replace('/api', '', $base . '/' . ltrim(Route::url($entry->link()), '/'));

					// Explain what status/role means
					if ($verbose)
					{
						// Project status
						switch ($entry->get('state'))
						{
							case 0:
								$obj->state = $entry->inSetup() ? Lang::txt('setup') : Lang::txt('suspended');
								break;

							case 1:
							default:
								$obj->state = Lang::txt('active');
								break;

							case 2:
								$obj->state = Lang::txt('deleted');
								break;

							case 5:
								$obj->state = Lang::txt('pending approval');
								break;
						}

						// Privacy
						$obj->privacy = $entry->isPrivate() ? Lang::txt('private') : Lang::txt('public');

						// Team role
						switch ($obj->userRole)
						{
							case 0:
							default:
								$obj->userRole = Lang::txt('collaborator');
								break;
							case 1:
								$obj->userRole = Lang::txt('manager');
								break;
							case 2:
								$obj->userRole = Lang::txt('author');
								break;
							case 5:
								$obj->userRole = Lang::txt('reviewer');
								break;
						}
					}
				}

				$response->projects[] = $obj;
			}
		}

		$this->send($response);
	}

	/**
	 * Get project info (if user is in project)
	 *
	 * @apiMethod GET
	 * @apiUri    /projects/{id}
	 * @apiParameter {
	 * 		"name":        "id",
	 * 		"description": "Project identifier (numeric ID or alias)",
	 * 		"type":        "string",
	 * 		"required":    true,
	 * 		"default":     null
	 * }
	 * @return  void
	 */
	public function getTask()
	{
		// Incoming
		$id = Request::getString('id', '');

		$this->model = new Project($id);

		// Project did not load?
		if (!$this->model->exists())
		{
			throw new Exception(Lang::txt('COM_PROJECTS_PROJECT_CANNOT_LOAD'), 404);
		}

		// Check authorization
		if (!$this->model->access('member') && !$this->model->isPublic())
		{
			throw new Exception(Lang::txt('ALERTNOTAUTH'), 401);
		}

		$base = rtrim(Request::base(), '/');

		$obj = new stdClass;
		$obj->id            = $this->model->get('id');
		$obj->alias         = $this->model->get('alias');
		$obj->title         = $this->model->get('title');
		$obj->description   = $this->model->get('about');
		$obj->private       = $this->model->get('private');
		$obj->access        = $this->model->get('access');
		$obj->owner         = $this->model->owner('name');
		$obj->created       = $this->model->get('created');
		$obj->groupOwnerId  = $this->model->groupOwner('id');
		$obj->userOwnerId   = $this->model->owner('id');
		$obj->uri           = str_replace('/api', '', $base . '/' . ltrim(Route::url($this->model->link()), '/'));
		$obj->thumbUrl      = str_replace('/api', '', $base . '/' . ltrim(Route::url($this->model->link('thumb')), '/'));

		if ($this->model->access('member'))
		{
			$obj->provisioned   = $this->model->isProvisioned();
			$obj->state         = $this->model->get('state');
			$obj->inSetup       = $this->model->inSetup();
			$obj->userRole      = $this->model->member()->role;
		}

		$response = new stdClass;
		$response->project = $obj;

		$this->send($response);
	}
}
