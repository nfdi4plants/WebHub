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
        <form enctype="multipart/form-data" method="POST">
        <div class="fileUpload">
        <label for="uploadFiles">Choose files: </label>
        <input type="file" name="uploadFiles" multiple>
        </div>
        <div class="fileUpload">
        <label for="uploadFolder">Choose folder: </label>
        <input type="file" name="uploadFolder" webkitdirectory directory>
        </div>
        <button id="upload">Upload Files</button>
        </form>
        </div>';
        echo '<h4>Folders:</h4>
        <ul>';
        echo '<li><a id="up" href="' . $base . '&bucket=' .$this->bucket . '&prefix=' . $this->prefix . '&object=..">..</a></li>';
        foreach($this->folders as $folder)
        {
            echo '<li><div class="item"><a href="' . $base . '&bucket=' . $folder->getBucket() . '&prefix=' . $folder->getPrefix() . '">' . urldecode($folder->getPrefix()) .  '</a><button class="delete" onclick="deleteItem(this)">Delete</button></div></li>';
        }
        if(!empty($this->files))
        {
            echo '</ul>
            <h4>Files:</h4>
            <ul>';
            foreach($this->files as $file)
            {
                echo '<li><div class="item"><a href="' . $base . '&bucket=' . $file->getBucket() . '&prefix=' . $file->getPrefix() . '&object=' . $file->getObject() . '">' . urldecode($file->getObject()) .  '</a><button class="delete" onclick="deleteItem(this)">Delete</button></div></li>';
            } 
            echo '</ul>';
        }
    }
