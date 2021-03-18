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
        echo '<p class="error">Either the access or secret key are not set.</p>';
    }
    else if(isset($this->error))
    {
        list($code, $message, $resource) = $this->error;
        echo '<div class="error"><p>Error code: ' . $code . '</p><p>Error message: ' . $message . '</p><p>Resource: ' . $resource . '</p></div>';
    }
    else if(isset($this->buckets))
    {
        echo '<h4>Available buckets:</h4>';
        foreach($this->buckets as $bucket)
        {
            echo '<pre>' . $bucket->Name . '  ' . $bucket->CreationDate . '</pre>';
        }
    }
    else
    {
       //echo '<h4>Current Bucket: ' . $this->$current . '</h4>';
       foreach($this->files as $file)
       {
           echo '<pre>' . $file->name . '  ' . $file->size . '  ' . $file->modified . '</pre>';
       } 
    }
