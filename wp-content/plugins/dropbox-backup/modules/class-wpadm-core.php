<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once DRBBACKUP_BASE_DIR . '/modules/class-wpadm-result.php';
require_once DRBBACKUP_BASE_DIR . '/modules/class-wpadm-command.php';
require_once DRBBACKUP_BASE_DIR . '/modules/class-wpadm-command-context.php';
require_once DRBBACKUP_BASE_DIR . '/modules/class-wpadm-queue.php';
require_once DRBBACKUP_BASE_DIR . '/modules/class-wpadm-running.php';
require_once DRBBACKUP_BASE_DIR . '/modules/class-wpadm-command-factory.php';


if (!class_exists('WPAdm_Core')) {
    
    class WPAdm_Core {

        /*
        *  "fly" POST from server 
        * @var array
        */
        private $request = array();

        /*
        * public key for auth
        * @var string
        */
        private $pub_key;

        /*
        * work result
        * @var WPAdm_Result
        */
        private $result;

        private $plugin;
		
		private $sign = false;

        public $name = '',
        $time = '';

        public static $cron = true;

        public static $pl_dir;

        public static $error = '';
		
		private static $self = null;

        public static $plugin_name;

        private static $cron_method = array('local_backup', 'send_to_dropbox');


        public function __construct(array $request, $plugin = '', $plugin_dir = '', $sign = false) {
            $this->result = new WPAdm_Result();
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            $this->request = $request;
			if ( empty( $this->request ) ) {
				return;
			}
            $this->plugin = $plugin;
			$this->sign = $sign;
            self::$pl_dir = $plugin_dir;
            self::$plugin_name = $plugin;
            // auth request
			if ( !$this->sign ) {
				if (!$this->auth()) {
					return;
				}                          
			}
            if ('connect' == $request['method']) {
                $this->connect();
            } elseif ('local' == $request['method']){

            } elseif($obj = $this->getObject($request['method'], $request['params'])) {
                if (!is_null($obj) && !is_bool($obj) && $obj->isError()) {
                    $this->result = $obj->get_results();
                } elseif(!is_null($obj) && !is_bool($obj)) {
                    if (isset($obj->name)) {
                        $this->name = $obj->name;
                    }
                    if (isset($obj->time)) {
                        $this->time = $obj->time;
                    }   
                    $this->result = $obj->getResult();
                }
            } else {
                $this->result->setError(sprintf(__('Unknown method "%s"', 'dropbox-backup'), $request['method'] ) );
            }
        }


        /**
        * return path to tmp dir
        * @return string
        */
        static public function getTmpDir() {
            $tmp_dir = self::$pl_dir . '/tmp';
            self::mkdir($tmp_dir);
            if (!file_exists($tmp_dir . '/index.php')) {
                @file_put_contents($tmp_dir . '/index.php', '');
                if (!file_exists($tmp_dir . '/index.php')) {
                    self::$error = ( sprintf( __('Backup creating<br /><br />Please check the permissions on folder "%s".<br />Failed to create folder.', 'dropbxo-backup'), $tmp_dir ) );
                }
            }
            return $tmp_dir;
        }
        
        static public function getPluginDir() {
            return self::$pl_dir;
        }

        /**
        * @param string $method
        * @param mixed $params
        * @return null|WPAdm_Method_Class
        */
        private function getObject($method, $params) {
            if (!preg_match("|[a-zA-Z0-9_\-]|", $method)) {
                return null;
            }
            if (function_exists('mb_strtolower')) {
                $method = mb_strtolower($method); 
            } else {
                $method = strtolower($method); 
            }
            $class_file = self::$pl_dir . "/methods/class-wpadm-method-" . str_replace('_', '-', $method) . ".php";
            if (file_exists($class_file)) {
                require_once $class_file;
                $tmp = explode('_', str_replace('-', '_', $method));
                foreach($tmp as $k=>$m) {
                    $tmp[$k] = ucfirst(strtolower($m));
                }
                $method = implode('_', $tmp);

                $class_name = "WPAdm_Method_{$method}";
                if (!class_exists($class_name)) {
                    $this->getResult()->setError(sprintf( __( "Class '%s' not found", 'dropbox-backup' ), $class_name ) );
                    $this->getResult()->setResult(WPAdm_result::WPADM_RESULT_ERROR);
                    return null;
                }
                if (in_array( strtolower( $method ), self::$cron_method) && self::$cron) {
                    WPAdm_Running::setCommand( strtolower($this->request['method']), $params );
                    WPAdm_Running::run();
                    self::$cron = true;
                    return true;
                } else {      
                    return new $class_name($params);
                }

            }
            return null;

        }


        public static function getLog()
        {
            $file_log = self::getTmpDir() . '/log.log';
            if (file_exists($file_log)) {
                return @file_get_contents($file_log);
            }
            return "";
        }

        private function connect() {

            add_option('wpadm_pub_key', $this->pub_key);
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);

            $sendData['system_data'] = get_system_data();
            $data['actApi'] = 'setStats';
            $data['site'] = get_option('siteurl');
            $data['data'] = wpadm_pack($sendData);
            if (!class_exists('WP_Http')) {
                include_once ABSPATH . WPINC . '/class-http.php';
            }

            $remote            = array();
            $remote['body']    = $data;
            $remote['timeout'] = 20;

            $result = wp_remote_post(WPADM_URL_BASE . "/api/", $remote);
        }
        public static function setPluginDIr($dir)
        {
            self::$pl_dir = $dir;
        }

        /*
        * auth request
        */
        private function auth() {
            $this->pub_key = get_option('wpadm_pub_key');
            $methods_local = array('local_backup', 'send-to-dropbox', 'local_restore', 'local', 'queue_controller', 'local_send_to_s3');
            if ( in_array($this->request['method'], $methods_local) ) {
                return true;
            }
            self::log($this->request['method']);
            if (empty($this->pub_key)) {
                if ('connect' == $this->request['method']) {
                    $this->pub_key = $this->request['params']['pub_key'];
                } else {
                    $this->getResult()->setError( __( 'Activate site in WPAdm.com for work to plugins.', 'dropbox-backup' ) );
                    return false;
                }
            } elseif ('connect' == $this->request['method']) {
                if( $this->pub_key != $this->request['params']['pub_key'] ){
                    $this->getResult()->setError( __( 'Error. Reconnect Plugin.', 'dropbox-backup' ) );
                    return false;
                }
            } elseif('queue_controller' == $this->request['method']) {
                //todo: check run self
                return true;

            } 

            $sign = md5(serialize($this->request['params']));
            //openssl_public_decrypt($this->request['sign'], $request_sign, $this->pub_key);
			$ret = $this->verifySignature(base64_decode( $this->request['sign'] ), base64_decode( $this->request['sign2'] ), $this->pub_key, $sign);


            //$ret = ($sign == $request_sign);
            if (!$ret) {
                $this->getResult()->setError(__("Incorrect signature", 'dropbox-backup'));
            }
            return $ret;
        }
		
		
        /**
        * create dir
        * @param $dir
        */
        static public function mkdir($dir) {
            if(!file_exists($dir)) {
                @mkdir($dir, 0755);
                if (!is_dir($dir)) {
                    self::$error = str_replace("&s", $dir, __('Backup creating<br /><br />Please check the permissions on folder "&s". Failed to create folder.','dropbox-backup') );
                } else {
                    //todo: права доступа
                    @file_put_contents($dir . '/index.php', '<?php echo "Hello World!"; ');
                    if ( !is_writable($dir . '/index.php') ) {
                        self::$error = str_replace("&s", $dir . '/index.php' , __('Backup creating<br /><br />Please check the permissions on file "&s". Failed to create file.','dropbox-backup') );
                    }
                }
            }
            if (!file_exists($dir . '/.htaccess')) {
                @file_put_contents($dir . '/.htaccess', 'DENY FROM ALL');
            }
            return self::$error;
        }

        /**
        * @return WPAdm_result result
        */
        public function getResult() {
            return $this->result;
        }
		
		public static function getInstance()
		{
			if (is_null( self::$self ) ) {
				self::$self = new self( array() );
			}
			return self::$self;
		}


        public function verifySignature($sign, $sign2, $pub_key, $text) {
			if (function_exists('openssl_public_decrypt')) {
                openssl_public_decrypt($sign, $request_sign, $pub_key);
                $ret = ($text == $request_sign);
				return $ret;
            } else {
                set_include_path(get_include_path() . PATH_SEPARATOR . self::getPluginDir() . '/modules/phpseclib');
                require_once 'Crypt/RSA.php';
                $rsa = new Crypt_RSA();
                $rsa->loadKey($pub_key);
                $ret = $rsa->verify($text, $sign2);
                return $ret;
            }
        }

        /**
        * @param $sign
        * @param $request_sign
        * @param $pub_key
        */
        public function openssl_public_decrypt($sign, &$request_sign, $pub_key) {
            //openssl_public_decrypt($sign, $request_sign, $pub_key);

        }


        static public function log($txt, $class='') {
            if (!empty($txt)) {
                $log_file = self::getTmpDir() . '/log.log';
                file_put_contents($log_file, date("Y-m-d H:i:s") ."\t{$class}\t{$txt}\n", FILE_APPEND);
            }
        }

        /**
        * Удаляет директорию со всем содержимым
        * @param $dir
        */
        static function rmdir($dir) {
            if (is_dir($dir)) {
                $dir_open = opendir($dir);
                while($f = readdir($dir_open)) {
                    if ($f == '..' or $f == '.') {
                        continue;
                    }
                    if (is_dir($dir . '/' . $f)) {
                        self::rmdir($dir . '/' . $f);
                    }
                    if (file_exists($dir . '/' . $f)) {
                        @unlink($dir . '/' . $f);
                    }
                }
                @rmdir($dir);
            } elseif (is_file($dir)) {
                @unlink($dir);
            }
        }
        static function dir_writeble($dir)
        {
            $error = self::mkdir($dir);
            $ret = true;
            if (!empty($dir)) {
                @file_put_contents($dir . "/test", "Hello World!!!");
				if (file_exists($dir . "/test")) {
					if (!@is_writable($dir . "/test") && @filesize($dir . "/test") == 0) {
						$ret = false;
					}
				} else {
					$ret = false;
				}
                @unlink($dir . "/test");
            }
            return $ret;
        }
    }
}
