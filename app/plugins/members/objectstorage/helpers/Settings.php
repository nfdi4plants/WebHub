<?php

// No direct access
defined('_HZEXEC_') or die();

class Settings {

    public static function getSettingsAPI(){
        // get key from POST Request triggered by form, set to empty string if no Var set
        $keys['access_key'] = Request::getVar('access-key', '', 'POST'); 
        $keys['secret_key'] = Request::getVar('secret-key', '', 'POST');
    
        foreach($keys as $key_name => $key)
        {
            if (empty($key)){
                // get key from DB, if empty
                $keys[$key_name] = self::getKey($key_name);
            } else {
                // update DB
                self::updateKey($key_name, $key);
            }
        }
        // Load correct view and make api key available
        return array('access_key' => $keys['access_key'], 'secret_key' => $keys['secret_key']);
    }

    public static function getKey($key_name)
    {
        $id = User::get('id');
        $db = App::get('db');

        $query = 'SELECT ' . $db->quoteName($key_name) . ' FROM `#__objectstorage` WHERE user_id = ' . $db->quote($id) . ';';

        // run query and fetch result
        $db->setQuery($query);
        $db->query();
        $key = $db->loadResult();

        return $key;
    }

    private static function updateKey($key_name, $key)
    {
        $id = User::get('id');
        $db = App::get('db');

        // Insert or update API key, if user id is already present
        $query = 'INSERT INTO `#__objectstorage` (user_id, ' . $db->quoteName($key_name) . ') VALUES(' . $db->quote($id) . ',' . $db->quote($key) . ') ON DUPLICATE KEY UPDATE ' . $db->quoteName($key_name) . '=' . $db->quote($key) . ';';

        $db->setQuery($query);
        $db->query();
    }
}