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
        echo '<ul>';
        foreach($this->buckets as $bucket)
        {
            echo '<li><a href="' . $base . '&bucket='. $bucket->Name . '">' . $bucket->Name . '</a></li>';
        }
        echo '</ul>';
    }
    else if(isset($this->folders) && isset($this->files))
    {
        echo '<div class="actions">
        <ul>
        <li class="uploadFile">Download folder</li>
        <li class="uploadFile">Upload a file</li>
        <li class="uploadFolder">Upload a folder</li>
        </ul>';
        echo '<h4>Folders:</h4>
        <ul>';
        echo '<li><a href="' . $base . '&bucket=' .$this->bucket . '&prefix=' . $this->prefix . '&object=..">..</p>';
        foreach($this->folders as $folder)
        {
            echo '<li><a href="' . $base . '&bucket=' . $folder->getBucket() . '&prefix=' . $folder->getPrefix() . '">' . urldecode($folder->getPrefix()) .  '</li></a>';
        }
        if(!empty($this->files))
        {
            echo '</ul>';
            echo '<h4>Files:</h4>';
            echo '<ul>';
            foreach($this->files as $file)
            {
                echo '<li><a href="' . $base . '&bucket=' . $file->getBucket() . '&prefix=' . $file->getPrefix() . '&object=' . $file->getObject() . '">' . urldecode($file->getPrefix()) . '/' . urldecode($file->getObject()) .  '</a></li>';
            } 
            echo '</ul>';
        }
    }
