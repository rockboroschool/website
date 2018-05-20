<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPAdm_Method_Update')) {
    class WPAdm_Method_Update extends WPAdm_Method_Class {

        private $archive = null;

        public function getResult()
        {
            $error = array();
            if (isset($this->params['files'])) {
                if (is_array($this->params['files'])) {
                    $n = count($this->params['files']);
                    for($i = 0; $i < $n; $i++) {
                        if ( ( $f = $this->dl($this->params['files'][$i]) ) === false ) {
                            $error[] = __('Error to copy file ' ,'dropbox-backup') . $this->params['files'][$i]['file']; 
                        } else {
                            if ( is_string($f) && $this->unpack($f, $this->params['files'][$i]['to']) === false ) {
                                $error[] = __('Error to extract file ' ,'dropbox-backup') . $f; 
                            }
                            if (file_exists($f)) {
                                unlink($f);
                            }
                        }
                    }
                } 
            } else {
                $error[] = 'Files is not exist';
            }
            if (count($error) == 0) {
                $this->result->setResult(WPAdm_result::WPADM_RESULT_SUCCESS);
                $this->result->setData('');
            } else {
                $this->result->setError(implode("\n", $error));
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            }

            return $this->result;
        }

        private function dl($file)
        {

            if (isset($file['unpack']) && $file['unpack'] == 1) {
                $d_ = WPAdm_Core::getTmpDir() . "/update";
                if (! is_dir($d_)) {
                    mkdir($d_, 0755);
                }
                $b = uniqid('update_') . '.zip';

            } elseif (isset($file['unpack']) && $file['unpack'] == 0) {
                $d_ = ABSPATH; 
                $b = $file['to'];
            } else {
                $d_ = '';
                $b = $file['to'];
            }
            if (!empty($d_)) {
                //$headers = array( 'Authorization' => 'Basic ' . base64_encode( "admin24:admin24" ) );
                $f = wp_remote_get($file['file'], array('headers' => $headers));
                if (isset($f['body']) && !empty($f['body'])) {
                    file_put_contents($d_ . "/" . $b, $f['body']);
                    if (file_exists($d_ . "/" . $b)) {
                        if (isset($file['unpack']) && $file['unpack'] == 1) {
                            return $d_ . "/" . $b;
                        } else {
                            return true;
                        }
                    } 
                }
            }
            return false;

        }
        private function unpack($f, $to)
        {
            if (strpos($to, ABSPATH) === false) {
                $to = ABSPATH . $to;
            }
            require_once WPAdm_Core::getPluginDir() . '/modules/pclzip.lib.php';
            $this->archive = new PclZip($f);
            $res = $this->archive->extract(PCLZIP_OPT_PATH, WPAdm_Core::getPluginDir(), 
            PCLZIP_OPT_REPLACE_NEWER,
            PCLZIP_OPT_REMOVE_PATH, WPAdm_Core::$plugin_name
            ); 
            WPAdm_Core::log($this->archive->errorInfo(true));
            if ( $res ) {
                return true;
            }
            return false;
        }
    }
}
