<?php
// no direct access
defined('_HZEXEC_') or die();

class plgStorageS3 extends \Hubzero\Plugin\Plugin
{
    protected $_autoloadLanguage = false;

    public function onReturnHTML()
    {
        $view = new \Hubzero\Plugin\View(array(
            'folder' => 'storage',
            'element' => 's3',
            'name' => 'display'
        ));

        $view->text = 'Dummy S3 Storage Plugin';

        if ($this->getError())
        {
            $view->setError( $this->getError() );
        }

        return $view->loadTemplate();
    }
}