<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Delete backup 
 * Class WPAdm_Method_Backup_Delete
 */
if (!class_exists('WPAdm_Method_Backup_Delete')) {
    class WPAdm_Method_Backup_Delete extends WPAdm_Method_Class {
        public function getResult()
        {
            $backup_dir = DROPBOX_BACKUP_DIR_BACKUP;
            $dropbox_options = wpadm_wp_full_backup_dropbox::getSettings();
            if ($dropbox_options) {
                if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                    $backup_dir = $dropbox_options['backup_folder'];
                }
            }
            $backups_dir = realpath($backup_dir . '/' . $this->params['name']);
            if(strpos($backups_dir,  DIRECTORY_SEPARATOR . 'DROPBOX_BACKUP_DIR_NAME' . DIRECTORY_SEPARATOR) === false || !is_dir($backups_dir)) {
                $this->result->setResult = WPAdm_result::WPADM_RESULT_ERROR;
                $this->result->setError('Wrong name backup');
            } else {
                if (is_dir($backups_dir)) {
                    WPAdm_Core::rmdir($backups_dir);
                    if (!is_dir($backups_dir)) {
                        $this->result->setResult = WPAdm_result::WPADM_RESULT_SUCCESS;
                    } else {
                        $this->result->setResult = WPAdm_result::WPADM_RESULT_ERROR;
                        $this->result->setError('Failed to remove backup');        
                    }
                }
            }
            return $this->result;
        }
    }
}