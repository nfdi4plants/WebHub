<?php  
      
      use Hubzero\Content\Migration\Base;  
        
      // No direct access  
      defined('_HZEXEC_') or die();  
        
      /** 
       * Migration script for registering the example plugin 
       **/  
      class Migration20210111165011PlgStorageS3 extends Base  
      {  
          /** 
           * Up 
           **/  
          public function up()  
          {  
              // Register the component Note the 'com_' prefix is optional.  
              //  
              // @param   string  $folder   (required) Plugin folder  
              // @param   string  $element  (required) Plugin element  
              // @param   int     $enabled  (optional, default: 1) Whether or not the plugin should be enabled  
          // @param   string  $params          (optional) Plugin params (if already known)  
              $this->addPluginEntry('storage', 's3');  
          }  
        
          /** 
           * Down 
           **/  
          public function down()  
          {  
              $this->deletePluginEntry('storage', 's3');  
          }  
      }  