<?php

/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die('Restricted access');

$session = App::get('session');
$data = json_decode($session->get('shibboleth_data'));
if (isset($data))
{
        $elixirID = $data->eduPersonUniqueID;
}

$this->css()
        ->js();
?>

<?php if (isset($elixirID))
{
        echo  'Your Elixir ID is: <p>' . $elixirID . '</p>';
}
else
{
        echo 'You are not logged in with an account linked to an Elixir ID';
}