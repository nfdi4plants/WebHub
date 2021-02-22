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

<?php if(isset($this->missing_keys) && $this->missing_keys) 
    { 
        echo '<p>Either the access or secret key are not set.</p>';
    }
    else
    {
       echo '<pre>Current Bucket: ' . $this->current . '</pre>';
       foreach($this->files as $file)
       {
           echo '<pre>' . $file->name . '  ' . $file->size . '  ' . $file->modified . '</pre>';
       } 
    }
