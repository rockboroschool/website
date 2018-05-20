<?php


if ( ! defined( 'ABSPATH' ) ) exit;


/**
* Class WPadm_Method_Send_To_Dropbox
*/
if (!class_exists('WPadm_Method_Send_To_Dropbox')) {
    class WPadm_Method_Send_To_Dropbox extends WPAdm_Method_Class {
        /**
        * @var WPAdm_Queue
        */
        private $queue;

        private $id;

        private $time_send = 120; // seconds for send to dropbox
        //private $name = '';

        public function getResult()
        {
            if ( WPAdm_Running::is_stop() ) {
                $errors = array();
                $this->id = uniqid('wpadm_method_send_to_dropbox_');

                $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
                $this->result->setError('');
                if ( WPAdm_Running::is_stop() ) {
                    if (isset($this->params['local']) && $this->params['local']) {
                        $params_data_cron = WPAdm_Running::getCommandResultData('local_backup');
                        if ( isset($res['result']) && $res['result'] == 'error' ) {
                            $errors[] = $res['error'];
                        } else {
                            $this->params['files'] = $params_data_cron['data'];
                            $this->params['access_details']['dir'] = $params_data_cron['name'];
                        }
                    }
                } else {
                    return true;
                }
                if ( WPAdm_Running::is_stop() ) {
                    if (empty($errors)) {

                        $ad = $this->params['access_details'];
                        $files = $this->params['files'];
                        //$this->getResult()->setData($files);
                        //$dir = trim($dir, '/') . '/' . $this->name;
                        if (is_array($files)) {
                            $send = false;
                            if (!class_exists('WPAdm_Process')) {
                                require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-process.php'; 
                            }
                            WPAdm_Process::init( 'dropbox', count($files) );
                            WPAdm_Core::log( __('Start copy to Dropbox Cloud', 'dropbox-backup') );
                            foreach($files as $file) {
                                try {
                                    if (isset($this->params['is_folder_set']) && $this->params['is_folder_set']) {
                                        $this->sendFileToDropbox( $file, $ad ); 
                                    } else {
                                        $this->sendFileToDropbox( ABSPATH . $file, $ad ); 
                                    }
                                    
                                } catch (Exception $e) {
                                    $this->result->setError( $e->getMessage() );
                                    $this->result->setResult( WPAdm_Result::WPADM_RESULT_ERROR );
                                    return $this->deleteBackup();
                                } 
                                if (isset($this->params['local']) && $this->params['local']) {
                                    $data_command = WPAdm_Running::getCommandResultData('command_dropbox');
                                    $data_error_command = WPAdm_Running::getCommandResultData('errors_sending');
                                    if (isset($data_error_command[ABSPATH . $file]) && $data_error_command[ABSPATH . $file]['count'] > WPADM_COUNT_LIMIT_SEND_TO_DROPBOX) {
                                        $msg = str_replace('%file%', $file, __('Not perhaps sending file: "%file%". If you wish make upload file, increase execution time code or speed internet provider is small for upload file.' ,'dropbox-backup'));
                                        WPAdm_Core::log( $msg );
                                        $errors[] = $msg;
                                        break;
                                    }
                                    $md5 = md5(ABSPATH . $file);
                                    if ( !empty($data_command) && in_array($md5, $data_command) ) {
                                        continue;
                                    }
                                }
                                /*  $commandContext = new WPAdm_Command_Context();
                                $commandContext->addParam('command', 'send_to_dropbox')
                                ->addParam('key', $ad['key'])
                                ->addParam('secret', $ad['secret'])
                                ->addParam('token', $ad['token'])
                                ->addParam('folder_project', $ad['folder'])
                                ->addParam('folder', $dir)
                                ->addParam('files', ABSPATH . $file);
                                if (isset($this->params['local']) && $this->params['local']) {
                                $commandContext->addParam('local', true);
                                }
                                $this->queue->add($commandContext);
                                unset($commandContext);
                                $send = true;
                                }  */
                            }
                            WPAdm_Core::log( __('End Copy Files to Dropbox' ,'dropbox-backup') ); 
                            /*if (isset($res) && !$res) {
                            WPAdm_Core::log(__('Answer from Dropbox ' ,'dropbox-backup') . $this->queue->getError());
                            $errors[] = __('Answer from Dropbox ' ,'dropbox-backup') . $this->queue->getError();
                            } */
                        }
                    }
                } else {
                    return true;
                }
                if (count($errors) > 0) {
                    $this->result->setError(implode("\n", $errors));
                    $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                    return $this->deleteBackup();
                } else { 
                    if ( WPAdm_Running::is_stop() ) {
                        if (class_exists('wpadm_wp_full_backup_dropbox') && !file_exists( WPAdm_Core::getTmpDir() . "/notice-star") ) {
                            wpadm_wp_full_backup_dropbox::setFlagToTmp( 'notice-star', time() . "_1d" );
                        }
                        if (isset($this->params['local']) && $this->params['local'] && isset($params_data_cron)) {
                            if ( WPAdm_Running::is_stop() ) {
                                $this->result->setData($this->params['files']);
                                $this->result->setSize($params_data_cron['size']);
                                $this->result->setValue('md5_data', md5 ( print_r($this->result->toArray(), 1 ) ) );
                                $this->result->setValue('name', $params_data_cron['name']);
                                $this->result->setValue('time', $params_data_cron['time']);
                                $this->result->setValue('type', 'dropbox');
                                $this->result->setValue('counts', $params_data_cron['counts'] );
                                if( (isset($this->params['is_local_backup']) && $this->params['is_local_backup'] == 0 ) || ( !isset($this->params['is_local_backup']) ) ) {
                                    $backup_dir = DROPBOX_BACKUP_DIR_BACKUP;
                                    $dropbox_options = wpadm_wp_full_backup_dropbox::getSettings();
                                    if ($dropbox_options) {
                                        if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                                            $backup_dir = $dropbox_options['backup_folder'];
                                        }
                                    }
                                    WPAdm_Core::rmdir( $backup_dir . "/{$params_data_cron['name']}");
                                }
                                return $this->result;
                            } else {
                                return $this->result;
                            }
                        }
                    } else {
                        return $this->result;
                    }
                } 
                return $this->result;
            }
            return true;
        }

        private function init(array $conf) 
        {
            $this->id = $conf['id'];
            $this->stime = $conf['stime'];
            $this->type = $conf['type'];
        }

        private function sendFileToDropbox($file, $ad)
        {
            if ( WPAdm_Running::is_stop() ) {
                require_once WPAdm_Core::getPluginDir() . '/modules/dropbox.class.php';
                $command_dropbox = WPAdm_Running::getCommandResultData('command_dropbox'); 
                $fromFile = str_replace('//', '/', $file); 
                $md5 = md5($fromFile);
                if (in_array($md5, $command_dropbox)) {
                    return true;
                }
                WPAdm_Core::log(__('Send to dropbox files' ,'dropbox-backup') );
                
                if (isset($ad['type']) && !empty($ad['type'])) {
                    $dropbox = new dropbox($ad['key'], $ad['secret'] );
                    $dropbox->setAccessToken2($ad['token'], $ad['type']);
                    WPAdm_Core::log(__('Use dropbox auth APIv2' ,'dropbox-backup') );
                } else {
                    $dropbox = new dropbox($ad['key'], $ad['secret'], $ad['token'] );
                }

                if ( !$dropbox->isAuth() && !$dropbox->isAuth2() ) {
                    WPAdm_Core::log(__('error' ,'dropbox-backup') );
                    $this->setError( str_replace(array('%d', '%k', '%s'), 
                    array( SITE_HOME, $ad['key'], $ad['secret'] ),__('Website "%d" can\'t authorize on Dropbox with using of "app key: %k" and "app secret: %s"' ,'dropbox-backup') ) );
                    return false;
                }
                $dir = (isset($ad['dir'])) ? $ad['dir'] : '/';
                if ( WPAdm_Running::is_stop() ) {
                    $file_ = explode("/", $file);
                    $file_name = array_pop($file_);
                    $folder_project_temp = $ad['folder'];
                    $folder_project = "";
                    if (!empty($folder_project_temp)) {
                        $folder_project = $folder_project_temp . "/";
                        $dropbox->createDir($folder_project_temp );
                        $dropbox->createDir($folder_project . $dir ); 
                    } else {
                        $dropbox->createDir( $dir );
                    }
                } else {
                    return true;
                }
                //$files = ''; // to comment
                $fromFile = str_replace('//', '/', $file);
                $toFile = str_replace('//', '/', $folder_project . $dir . '/' . $file_name);
                $local = isset( $this->params['local'] ) ? $this->params['local'] : false ;
                $file_dropbox = $dropbox->listing($folder_project . $dir. '/' . $file_name);
                $send = true;
                if ( !isset($file_dropbox['error']) ) {
                    if ($file_dropbox['bytes'] != filesize($fromFile)) {
                        if ( WPAdm_Running::is_stop() ) {
                            $delete_file = $dropbox->deleteFile($folder_project . $dir . '/' . $file_name);
                            if (isset($delete_file['error'])) {
                                //$this->setError( __('Dropbox returned an error during file sending: ' ,'dropbox-backup') . '"' . $delete_file['text'] . '"(d)');
                                //return false;
                                WPAdm_Core::log( __('File in Dropbox Cloud: ' ,'dropbox-backup') . '"' . $delete_file['text'] . '"' );
                                
                                WPAdm_Core::log( __('Try send file again' ,'dropbox-backup') . '(' . $file_name . ')' );
                            }
                            $data_error_command = WPAdm_Running::getCommandResultData('errors_sending');
                            if (isset($data_error_command[$fromFile])) {
                                $data_error_command[$fromFile]['count'] += 1;
                            } else {
                                $data_error_command[$fromFile] = array();
                                $data_error_command[$fromFile]['count'] = 1;
                            }
                            WPAdm_Running::setCommandResultData('errors_sending', $data_error_command); 
                        }
                    } else {
                        $send = false;
                    }
                }      
                if ( $local ) {
                    if ( WPAdm_Running::is_stop() ) {
                        $data_command = WPAdm_Running::getCommandResultData('command_dropbox');
                        $md5 = md5($fromFile);
                        if (empty($data_command) || !in_array($md5 ,$data_command) ) { 
                            if ($send) {
                                $res = $dropbox->uploadFile($fromFile, $toFile, true);
                            } else {
                                $data_command = WPAdm_Running::getCommandResultData('command_dropbox');
                                if (!in_array($md5, $data_command)) {
                                    $this->saveDataCommand($md5);
                                    WPAdm_Process::setInc('dropbox', 1); 
                                }

                            }
                        }
                    }
                } else {
                    $md5 = md5($fromFile);  
                    $res = $dropbox->uploadFile($fromFile, $toFile, true);
                }
                if (isset($res['error']) && isset($res['text']) && $res['error'] == 1) {
                    $this->setError( __('Dropbox returned an error during file sending: ' ,'dropbox-backup') . '"' . $res['text'] . '"(u)');
                    return false;
                }
                if ( WPAdm_Running::is_stop() ) {
                    if ( ( isset($res['size']) && isset($res['client_mtime']) ) || ( isset($res['result']['size']) && isset($res['result']['path_display']) ) ) {
                        $data_command = WPAdm_Running::getCommandResultData('command_dropbox');
                        if (!in_array($md5, $data_command)) {
							
							if (isset($res['result']['size'])) {
								WPAdm_Core::log( __('File upload: ' ,'dropbox-backup') . basename( $file ) . __(' size: ' ,'dropbox-backup') . $res['result']['size']);
							} else {
								WPAdm_Core::log( __('File upload: ' ,'dropbox-backup') . basename( $file ) . __(' size: ' ,'dropbox-backup') . $res['size']);
							}
                            $this->saveDataCommand($md5);
                            WPAdm_Process::setInc('dropbox', 1);
                        }
                    }
                }

            }
            return true;
        }

        private function saveDataCommand($file) 
        {
            $data_command = WPAdm_Running::getCommandResultData('command_dropbox');
            $data_command[] = $file;
            WPAdm_Running::setCommandResultData('command_dropbox', $data_command); 
        }

        private function setError($txt = '')
        {
            throw new Exception($txt);
        }

        private function deleteBackup()
        {
            if( (isset($this->params['is_local_backup']) && $this->params['is_local_backup'] == 0 ) || ( !isset($this->params['is_local_backup']) ) ) {
                if (isset($this->params['access_details']['dir'])) {   
                    $backup_dir = DROPBOX_BACKUP_DIR_BACKUP;
                    $dropbox_options = wpadm_wp_full_backup_dropbox::getSettings();
                    if ($dropbox_options) {
                        if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                            $backup_dir = $dropbox_options['backup_folder'];
                        }
                    }
                    WPAdm_Core::rmdir( $backup_dir . "/{$this->params['access_details']['dir']}");
                }
            }
            return $this->result;
        }
    }
}