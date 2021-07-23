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

<?php 
    echo '<a class="settings" href="' . $base . '/settings">Settings</a>';
?>
<?php if(isset($this->missing_keys) && $this->missing_keys) 
    { 
        ?>
        <p class="error">Either the access or secret key are not set.<br/>
        Keys can be obtained from your de.NBI dashboard under Project->API Access->View Credentials.<br/>
        These can then be set here under settings to access your project buckets.</p>
        <?php
    }
    else if(isset($this->error))
    {
        list($code, $message, $resource) = $this->error;
        echo '<div class="error"><p>Error code: ' . $code . '</p><p>Error message: ' . $message . '</p><p>Resource: ' . $resource . '</p></div>';
    }
    else if(isset($this->buckets))
    {
        echo '<h4>Available buckets:</h4>';
        if (empty($this->buckets))
        {
            echo '<p id="no-buckets">No buckets found.<br/>Please create a bucket using a command line client first.</p>';
        }
        else
        {
            echo '<ul>';
            foreach($this->buckets as $bucket)
            {
                echo '<li><div class="item"><a class="icon-bucket" href="' . $base . '&bucket='. $bucket->Name . '">' . $bucket->Name . '</a></div></li>';
            }
            echo '</ul>';
        }

    }
    else if(isset($this->folders) && isset($this->files))
    {
        $prefix = urldecode($this->prefix);
        $prefix = empty($prefix) ? '/' : $prefix;
        $upDesc = $prefix == '/' ? 'Back to bucket selection' : 'Back to parent folder';
        echo '<div id="s3-content">
            <div id="s3-header">
            <div class="current">Your current location: <p class="location">' . $prefix . '</p></div>
            <p id="section-upload"> Upload</p>
            <div class="actions">
                <form id="s3-upload" enctype="multipart/form-data" method="POST">
                    <div class="fileUpload">
                    <input type="file" name="uploadFiles" id="uploadFiles" class="hide-input" multiple>
                        <label for="uploadFiles">Select files </label>
                    </div>
                    <div class="fileUpload">
                    <input type="file" name="uploadFolder" id="uploadFolder" class="hide-input" webkitdirectory directory>
                        <label for="uploadFolder">Select folder </label>
                    </div>
                    <button id="upload" class="icon-upload" title="Upload files"/>
                </form>
            </div>
        </div>
        <p id="section-files"> Files</p><a id="up" class="item icon-arrow-up" href="' . $base . '&bucket=' .$this->bucket . '&prefix=' . $this->prefix . '&object=..">' . $upDesc . '</a>
        <div id="filebrowser">
            <ul>';
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
                echo '<li><div class="item"><a class="icon-file" href="' . $base . '&bucket=' . $file->getBucket() . '&prefix=' . $file->getPrefix() . '&object=' . $file->getObject() . '">' . urldecode($file->getObject()) .  '</a><div class="buttons"><button class="icon-download" title="Download file" onclick="downloadItem(this)"/><button class="icon-info" title="" onmouseover="itemInfo(this)"/><button class="icon-delete" title="Delete file" onclick="deleteItem(this)"/></div></div></li>';
            } 
            echo '</ul>';
        }
        echo '</div>';
    }
