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
        $prefix = urldecode($this->prefix);
        $prefix = empty($prefix) ? '/' : $prefix;
        $upDesc = $prefix == '/' ? 'Back to bucket selection' : 'Back to parent folder';
        echo 
        '<div class="s3-content">
        <div class="actions">
        <form enctype="multipart/form-data" method="POST">
        <div class="fileUpload">
        <label for="uploadFiles">Choose files: </label>
        <input type="file" name="uploadFiles" multiple>
        </div>
        <div class="fileUpload">
        <label for="uploadFolder">Choose folder: </label>
        <input type="file" name="uploadFolder" webkitdirectory directory>
        </div>
        <button id="upload" class="icon-upload" title="Upload files"/>
        </form>
        </div>
        <div class="filebrowser">
        <div class="current">Your current location: ' . $prefix . '</div>
        <ul>';
        echo '<li><a id="up" class="item icon-arrow-up" href="' . $base . '&bucket=' .$this->bucket . '&prefix=' . $this->prefix . '&object=..">' . $upDesc . '</a></li>';
        foreach($this->folders as $folder)
        {
            $prefix = urldecode($folder->getPrefix());
            if (strpos($prefix, '/') !== false)
            {
                $parts = explode('/', $prefix);
                $name = array_pop($parts);
            }
            else
            {
                $name = $prefix;
            }
            echo '<li><div class="item"><a class="icon-folder" href="' . $base . '&bucket=' . $folder->getBucket() . '&prefix=' . $folder->getPrefix() . '">' . $name .  '</a><button class="icon-delete" title="Delete folder" onclick="deleteItem(this)"/></div></li>';
        }
        if(!empty($this->files))
        {
            echo '</ul>
            <ul>';
            foreach($this->files as $file)
            {
                echo '<li><div class="item"><a class="icon-file" href="' . $base . '&bucket=' . $file->getBucket() . '&prefix=' . $file->getPrefix() . '&object=' . $file->getObject() . '">' . urldecode($file->getObject()) .  '</a><div class="buttons"><button class="icon-download" title="Download file" onclick="downloadItem(this)"/><button class="icon-info" title="File information" onclick="itemInfo(this)"/><button class="icon-delete" title="Delete file" onclick="deleteItem(this)"/></div></div></li>';
            } 
            echo '</ul>';
        }
        echo '</div></div>';
    }
