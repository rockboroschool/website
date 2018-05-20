<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPadm_Command_Send_To_Dropbox')) {

    class WPadm_Command_Send_To_Dropbox extends WPAdm_Сommand {

        public function execute(WPAdm_Command_Context $context)
        {
            @session_start();
            if ( WPAdm_Running::is_stop() ) {
                require_once WPAdm_Core::getPluginDir() . '/modules/dropbox.class.php';

                WPAdm_Core::log(__('Send to dropbox files' ,'dropbox-backup') );
                $dropbox = new dropbox($context->get('key'), $context->get('secret'), $context->get('token'));

                if (!$dropbox->isAuth()) {
                    $context->setError( str_replace(array('%d', '%k', '%s'), 
                    array( SITE_HOME, $context->get('key'), $context->get('secret') ),__('Website "%d" can\'t authorize on Dropbox with using of "app key: %k" and "app secret: %s"' ,'dropbox-backup') ) );
                    return false;
                }
                if ( WPAdm_Running::is_stop() ) {
                    $files = $context->get('files');
                    $file = explode("/", $files);
                    $file_name = array_pop($file);
                    $folder_project_temp = $context->get('folder_project');
                    $folder_project = "";
                    if (!empty($folder_project_temp)) {
                        $folder_project = $folder_project_temp . "/";
                        $dropbox->createDir($folder_project_temp );
                        $dropbox->createDir($folder_project . $context->get('folder') ); 
                    } else {
                        $dropbox->createDir($context->get('folder') );
                    }
                } else {
                    return true;
                }

                $fromFile = str_replace('//', '/', $files);
                $toFile = str_replace('//', '/', $folder_project . $context->get('folder') . '/' . $file_name);
                $local = $context->get('local');
                $file_dropbox = $dropbox->listing($folder_project . $context->get('folder') . '/' . $file_name);
                $send = true;
                if ( !isset($file_dropbox['error']) ) {
                    if ($file_dropbox['bytes'] != filesize($fromFile)) {
                        if ( WPAdm_Running::is_stop() ) {
                            $delete_file = $dropbox->deleteFile($folder_project . $context->get('folder') . '/' . $file_name);
                            if (isset($delete_file['error'])) {
                                $context->setError( __('Dropbox returned an error during file sending: ' ,'dropbox-backup') . '"' . $delete_file['text'] . '"');
                                return false;
                            }
                            $data_error_command = WPAdm_Running::getCommandResultData('errors_sending');
                            if (isset($data_command[$fromFile])) {
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
                                $this->saveDataCommand($md5);
                                WPAdm_Process::setInc('dropbox', 1); 
                            }
                        }
                    }
                } else {
					$md5 = md5($fromFile);
                    $res = $dropbox->uploadFile($fromFile, $toFile, true);
                }
                if (isset($res['error']) && isset($res['text']) && $res['error'] == 1) {
                    $context->setError( __('Dropbox returned an error during file sending: ' ,'dropbox-backup') . '"' . $res['text'] . '"');
                    return false;
                }
                if ( WPAdm_Running::is_stop() ) {
                    if (isset($res['size']) && isset($res['client_mtime'])) {
                        WPAdm_Core::log( __('File upload: ' ,'dropbox-backup') . basename( $files ) . __(' size: ' ,'dropbox-backup') . $res['size']);
                        $this->saveDataCommand($md5);
                        WPAdm_Process::setInc('dropbox', 1);
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
    }
}