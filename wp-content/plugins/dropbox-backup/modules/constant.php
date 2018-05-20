<?php
if( !defined('WPADM_DIR_NAME') ) {
    define('WPADM_DIR_NAME', 'wpadm_backups');
}

if( !defined('DROPBOX_BACKUP_DIR_NAME') ) {
    define('DROPBOX_BACKUP_DIR_NAME', 'Dropbox_Backup');
}

if( !defined('DROPBOX_BACKUP_DIR_BACKUP') ) {
    define('DROPBOX_BACKUP_DIR_BACKUP', WP_CONTENT_DIR . '/' . DROPBOX_BACKUP_DIR_NAME);
}

if (!defined('WPADM_DIR_BACKUP')) {
    define('WPADM_DIR_BACKUP',  WP_CONTENT_DIR . '/' . WPADM_DIR_NAME );
}

if (! defined("WPADM_URL_BASE")) {
    define("WPADM_URL_BASE", 'http://secure.webpage-backup.com/');
}
if (! defined("WPADM_URL_PRO_VERSION")) {
    define("WPADM_URL_PRO_VERSION", 'https://secure.wpadm.com/');
}

if (! defined("WPADM_APP_KEY")) {
    define("WPADM_APP_KEY", 'nv751n84w2nif6j');
}

if (! defined("WPADM_APP_SECRET")) {
    define("WPADM_APP_SECRET", 'qllasd4tbnqh4oi');
}

if (!defined("SERVER_URL_INDEX")) {
    define("SERVER_URL_INDEX", "http://www.webpage-backup.com/");
}
if (!defined("PHP_VERSION_DEFAULT")) {
    define("PHP_VERSION_DEFAULT", '5.2.4' );
}
if (!defined("MYSQL_VERSION_DEFAULT")) {
    define("MYSQL_VERSION_DEFAULT", '5.0' );
}

if (!defined("PREFIX_BACKUP_")) { 
    define("PREFIX_BACKUP_", "wpadm_backup_"); 
}   
if (!defined("WPADM_1DAY")) { 
    define("WPADM_1DAY", 86400);      // 86400 sec = 1 day = 24 hours
}   
if (!defined("WPADM_1WEEK")) { 
    define("WPADM_1WEEK", WPADM_1DAY * 7); 
}   
if (!defined("WPADM_COUNT_LIMIT_SEND_TO_DROPBOX")) { 
    define("WPADM_COUNT_LIMIT_SEND_TO_DROPBOX", 5); 
}   

if (!defined("SITE_HOME") && function_exists( 'home_url' ) ) {
    define("SITE_HOME", str_ireplace( array( 'http://', 'https://' ), '', home_url() ) );
} else {
    if (class_exists('dbr_database')) {
        $data = dbr_database::db_get('options', array('option_value'), array('option_name' => 'home'), 1);
        if (isset($data['option_value'])) {
            define("SITE_HOME", str_ireplace( array( 'http://', 'https://' ), '', $data['option_value'] ) );
        }
    }
}
