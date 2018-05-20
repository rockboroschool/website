<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
* Class WPAdm_Method_Exec
*/
if (!class_exists('WPAdm_Method_Local_Restore')) {
    class WPAdm_Method_Local_Restore extends WPAdm_Method_Class {

        private $restore = false;

        private $files_resotre = array();

        private $md5_info = "";

        function __construct($params)
        {
            parent::__construct($params);
            $this->init(
            array(
            'id' => uniqid('wpadm_method_restore__'),
            'stime' => time(),
            )
            );
            $this->getFiles();
            $file_log = WPAdm_Core::getTmpDir() . "/log.log";
            if (file_exists($file_log)) {
                unlink($file_log);
            }
            WPAdm_Core::log(__('Create Unique Id ','dropbox-backup') . $this->id);
            if (count($this->files_resotre) > 0) {
                $this->restore = true;
            }

        }
        private function getFiles()
        {
            $res = false;
            if (isset($this->params['name_backup']) && !empty($this->params['name_backup'])) {
                $res = self::readFiles( DROPBOX_BACKUP_DIR_BACKUP . '/' . $this->params['name_backup'] );
                if ($res === false) {
                    $res = self::readFiles(WPADM_DIR_BACKUP . '/' . $this->params['name_backup']);
                }
                if ($res === false) {
                    $res = self::readFiles(ABSPATH . WPADM_DIR_NAME . '/' . $this->params['name_backup']);
                }
            } 
            if ($res === false) {
                $str = str_replace(array('%d', '%s'), array(SITE_HOME, $this->params['name_backup']), __('Website "%d" returned an error during backup restoration: Archive of Backup wasn\'t found "%s"','dropbox-backup') );
                WPAdm_Core::log($str);
                $this->setError($str);
            }
            return $res;

        }

        private function readFiles($dir_backup)
        {
            if (is_dir($dir_backup)) { 
                WPAdm_Core::log('Read of Backup Files for Restore (' . $this->params['name_backup'] . ')');
                $dir_open = opendir($dir_backup);
                while($d = readdir($dir_open)) {
                    if ($d != "." && $d != '..') {
                        if(strpos($d, ".md5") !== false) {
                            $this->md5_info = explode ("\n", file_get_contents( $dir_backup . "/$d" ) );
                        } elseif(strpos($d, ".zip") !== false) {
                            $this->files_resotre[$d] = $dir_backup . "/$d";
                        }
                    }
                }
                return true;
            }
            return false;
        }
        private function setError($errors)
        {
            $this->result->setError($errors);
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
        }
        private function init(array $conf) 
        {
            $this->id = $conf['id'];
            $this->stime = $conf['stime'];
            $this->queue = new WPAdm_Queue($this->id);
        }
        public function getResult()
        {  
            if ($this->restore) {
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
                $this->result->setError('');

                WPAdm_Core::log(__('Start Restore process','dropbox-backup'));
                $n = count($this->md5_info);
               
                if (in_array('files', $this->params['types']) ) {
                    foreach($this->files_resotre as $key => $file) {
                        if (file_exists($file)) {
                            $commandContext = new WPAdm_Command_Context();
                            $commandContext ->addParam('command', 'restore_backup')
                            ->addParam('zip_file', $file );
                            $this->queue->clear()
                            ->add($commandContext)->save()
                            ->execute();
                            unset($commandContext);
                        }
                    }
                }
                if (in_array('db', $this->params['types'])) {
                    $this->getWpMysqlParams();
                    for($i = 0; $i < $n; $i++) {
                        if (strpos($this->md5_info[$i], "mysqldump.sql") !== false ) {
                            $data = explode("\t", $this->md5_info[$i]);
                            if (isset($this->files_resotre[$data[2]]) && file_exists($this->files_resotre[$data[2]])) {    
                                $commandContext = new WPAdm_Command_Context();
                                $commandContext ->addParam('command', 'restore_backup')
                                ->addParam('file', $data[0])
                                ->addParam('zip_file', $this->files_resotre[$data[2]] );
                                $commandContext->addParam('db_password', $this->db_data['password'])->
                                addParam('db_name', $this->db_data['db'])->
                                addParam('db_user', $this->db_data['user'])->
                                addParam('db_host', $this->db_data['host']);
                                $this->queue->clear()
                                ->add($commandContext)->save()
                                ->execute();
                                unset($commandContext);
                                if (file_exists(DROPBOX_BACKUP_DIR_BACKUP .  "/mysqldump.sql")) {
                                    @unlink(DROPBOX_BACKUP_DIR_BACKUP .  "/mysqldump.sql");
                                }
                            } else {
                                $log = str_replace( array('%d', '%s'), array(SITE_HOME, $data[2]) , __('Website "%d" returned an error during backup restoration: Part Backup is not exist "%s" ','dropbox-backup'));
                                $this->setError($log);
                                WPAdm_Core::log($log);
                                break;
                            }
                        }
                    }
                }
            } else {
                WPAdm_Core::log(
                str_replace( array('%d'), array(SITE_HOME), __('Website "%d" returned an error during restore backup: Files for restore is not exist, check permission your backup data or You can send to us support request using "Help" button on plugin page.','dropbox-backup') )
                );
            }
            return $this->result;
        }
        private function getWpMysqlParams()
        {
            $db_params = array(
            'password' => 'DB_PASSWORD',
            'db' => 'DB_NAME',
            'user' => 'DB_USER',
            'host' => 'DB_HOST',
            );

            $r = "/define\('(.*)', '(.*)'\)/";
            preg_match_all($r, file_get_contents(ABSPATH . "wp-config.php"), $m);
            $params = array_combine($m[1], $m[2]);
            foreach($db_params as $k=>$p) {
                $db_params[$k] = $params[$p];
            }
            $this->db_data = $db_params;
        }
    }
}