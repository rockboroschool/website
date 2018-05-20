<?php 

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


    if(@session_id() == '') {
        @session_start();
    }

    require_once DRBBACKUP_BASE_DIR . "/libs/error.class.php";
    require_once DRBBACKUP_BASE_DIR . "/libs/wpadm.server.main.class.php";
    if (! class_exists("wpadm_wp_full_backup_dropbox") ) {

        add_action('wp_ajax_wpadm_local_restore', array('wpadm_wp_full_backup_dropbox', 'restore_backup') );
        add_action('wp_ajax_wpadm_restore_dropbox', array('wpadm_wp_full_backup_dropbox', 'wpadm_restore_dropbox') );
        add_action('wp_ajax_wpadm_logs', array('wpadm_wp_full_backup_dropbox', 'getLog') );
        add_action('wp_ajax_wpadm_local_backup', array('wpadm_wp_full_backup_dropbox', 'local_backup') );
        add_action('wp_ajax_wpadm_dropbox_create', array('wpadm_wp_full_backup_dropbox', 'dropbox_backup_create') );
        add_action('wp_ajax_set_user_mail', array('wpadm_wp_full_backup_dropbox', 'setUserMail') );

        add_action('wp_ajax_saveSetting', array('wpadm_wp_full_backup_dropbox', 'saveSetting') );


        add_action('admin_post_wpadm_delete_backup', array('wpadm_wp_full_backup_dropbox', 'delete_backup') );
        add_action('admin_post_dropboxConnect', array('wpadm_wp_full_backup_dropbox', 'dropboxConnect') );
        add_action('admin_post_stop_backup', array('wpadm_wp_full_backup_dropbox', 'stopBackup') );

        add_action('admin_post_login-process', array('wpadm_wp_full_backup_dropbox', 'auth_user') );

        add_action('admin_post_wpadm_download', array('wpadm_wp_full_backup_dropbox', 'download') );
        add_action('init', array('wpadm_wp_full_backup_dropbox', 'init'), 10 );

        if (DRBBACKUP_MULTI === false) {
            add_action('admin_notices', array('wpadm_wp_full_backup_dropbox', 'notice') );
            add_action('admin_notices', array('wpadm_wp_full_backup_dropbox', 'notice_stars'));
        } else {
            add_action('network_admin_notices', array('wpadm_wp_full_backup_dropbox', 'notice') );
            add_action('network_admin_notices', array('wpadm_wp_full_backup_dropbox', 'notice_stars') );
        }

        add_action('admin_post_hide_notice', array('wpadm_wp_full_backup_dropbox', 'hide_notice') );
        add_action( 'admin_bar_menu', array('wpadm_wp_full_backup_dropbox', 'add_link_to_admin_bar') ,999 );

        add_filter( 'nonce_life', 'wpadm_nonce_life' );

        class wpadm_wp_full_backup_dropbox extends wpadm_class  {

            private static $default_optimization = 1;

            const MIN_PASSWORD = 6;

            private static $circle = 42;

            private static $local_delete = false;

            private static $type_archive = array('zip_archive' => 1, 'pclzip_archive' => 1, 'targz_archive' => 1);

            static function is_auth_user()
            {
                if ( !is_user_logged_in() ) {
                    exit;
                }
                $dropbox_options = self::getSettings();
                if ( ( isset($dropbox_options['is_admin']) && $dropbox_options['is_admin'] == 1 ) || !isset($dropbox_options['is_admin']) ) {
                    if (!is_admin() || !is_super_admin()) {  // check admin
                        exit;
                    }
                    if ( !current_user_can('administrator') ) { // check admin
                        exit;
                    }
                }
                $nonce = '';
                if (isset($_REQUEST['nonce'])) {
                    $nonce = sanitize_text_field($_REQUEST['nonce']);
                }
                if ( ! wp_verify_nonce( $nonce, 'wpadm_nonce' ) ) {
                    exit;
                }
            }

            static function stopBackup($local = false, $type_backup = '')
            {
                self::is_auth_user();
                if (isset($_POST['type_backup']) || !empty($type_backup)) {
                    self::$local_delete = $local;
                    if (!empty($type_backup)) {
                        $type = $type_backup;
                    } else {
                        $type = sanitize_text_field( $_POST['type_backup'] );
                    }
                    $setting_backup = array();
                    if ( $type == 'local_backup' ) {
                        $setting_backup = WPAdm_Running::getCommand( 'local_backup' );
                        $type_backup = 'local';
                    } elseif ( $type == 'send-to-dropbox') {
                        $setting_backup = WPAdm_Running::getCommand( 'send-to-dropbox' );
                        $type_backup = 'dropbox';
                    }
                    // WPAdm_Running::setCommandResultData('stop_backup', $setting_backup);
                    $name = '';
                    if (isset($setting_backup['params']['time'])) {
                        $name = get_option('siteurl');

                        $name = str_replace("http://", '', $name);
                        $name = str_replace("https://", '', $name);
                        $name = str_ireplace( array( 'Ä',  'ä',  'Ö',  'ö', 'ß',  'Ü',  'ü', 'å'), 
                        array('ae', 'ae', 'oe', 'oe', 's', 'ue', 'ue', 'a'), 
                        $name );
                        $name = preg_replace("|\W|", "_", $name);  
                        $name .= '-full-' . date("Y_m_d_H_i", $setting_backup['params']['time']);

                        self::delete_backup('local', $name);
                        if ($type == 'send-to-dropbox') {
                            self::delete_backup('dropbox', $name);
                        }

                    }
                }
                WPAdm_Running::init_params_default();
                WPAdm_Running::setCommandResultData('stop_process', array( 'stop' => 1, 'name' => $name, 'type' => $type ) );
                if ($local === false || empty($local)) {
                    header("Location: " . admin_url("admin.php?page=wpadm_wp_full_backup_dropbox"));
                    exit;
                }
            }

            static function add_link_to_admin_bar($wp_admin_bar) 
            {
                $show = true;
                $dropbox_options = self::getSettings();

                if ( ( isset($dropbox_options['is_show_admin_bar']) && $dropbox_options['is_show_admin_bar'] == 0 ) ) {
                    $show = false;
                }
                if ( ( isset($dropbox_options['is_admin']) && $dropbox_options['is_admin'] == 1 ) || !isset($dropbox_options['is_admin']) ) {
                    if (!is_admin() || !is_super_admin()) {
                        $show = false;
                    }
                }
                if ($show) {
                    if (!DRBBACKUP_MULTI) {
                        $wp_admin_bar->add_node( array(
                        'id' => 'dropbox-backup',
                        'title' => 'Dropbox backup',
                        'href' => esc_url( admin_url("admin.php?page=wpadm_wp_full_backup_dropbox") ),
                        'meta' => array('class' => 'dropbox-image-toolbar')
                        ));
                    }
                }

            } 

            public static function notice_stars()
            {
                if ( file_exists(WPAdm_Core::getTmpDir() . "/notice-star") ) {
                    $star = file_get_contents(WPAdm_Core::getTmpDir() . "/notice-star");
                    if ($star != 0) {
                        $d = explode("_", $star);
                        $time = $hide = '';
                        if (isset($d[1])) {
                            if ($d[1] == '1d' && ( (int)$d[0] + WPADM_1DAY ) <= time() ) {
                                $time = __("1 day",'dropbox-backup');
                                $hide = '1d';
                            } elseif ($d[1] == 'w' && ( (int)$d[0] + WPADM_1WEEK ) <= time() ) {
                                $time = __("1 week",'dropbox-backup');
                                $hide = 'week';
                            }        
                        }
                        if (!empty($time) && !empty($hide) && file_exists(DRBBACKUP_BASE_DIR . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "notice5.php")) {
                            ob_start();
                            require_once DRBBACKUP_BASE_DIR . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "notice5.php";
                            echo ob_get_clean();
                        }
                    }
                }
            }

            public static function setFlagToTmp($flag = '', $data = false)
            {
                if ( !empty($flag) ) {
                    if (!class_exists('WPAdm_Core')) {
                        require_once DRBBACKUP_BASE_DIR . "/libs/class-wpadm-core.php" ;
                        WPAdm_Core::$pl_dir = DRBBACKUP_BASE_DIR;
                    }
                    file_put_contents( WPAdm_Core::getTmpDir() . "/$flag" , $data );
                }
            }

            public static function auth_user()
            {
                if (isset($_POST['username']) && $_POST['password']) {
                    if(!function_exists("wp_safe_remote_post")) {
                        include ABSPATH . "/http.php";
                    }
                    $res = wp_safe_remote_post(SERVER_URL_INDEX, array('username' => sanitize_text_field( $_POST['username'] ), 'password' => sanitize_text_field( $_POST['password'] ), 'plugin' => 'dropbox-backup'));
                    if (!Empty($res['body'])) {
                        $data_res = json_decode($res['body']);
                        if (isset($data_res['url'])) {
                            header("Location: " . $data_res['url']);
                            exit;
                        }
                    }
                }
                header("Location: " . admin_url("admin.php?page=wpadm_wp_full_backup_dropbox") );
                exit;
            }

            public static function init()
            {
                parent::$plugin_name = 'dropbox-backup';
                require_once  DRBBACKUP_BASE_DIR . '/modules/class-wpadm-core.php';
                WPAdm_Core::$pl_dir = DRBBACKUP_BASE_DIR ;
            }

            static function include_admins_script()
            {
                if (isset($_GET['page']) && ($_GET['page'] == 'wpadm_wp_full_backup_dropbox' || $_GET['page'] == 'wpadm_plugins') ) {
                    wp_enqueue_style('admin-wpadm', plugins_url( "/template/css/admin-style-wpadm.css", dirname( __FILE__ )) );
                    wp_enqueue_script( 'js-admin-wpadm', plugins_url( "/template/js/admin-wpadm.js",  dirname( __FILE__ ) ) );
                    wp_enqueue_script( 'postbox' );
                    wp_enqueue_script( 'jquery-ui-tooltip' );
                }
                wp_enqueue_style('css-admin-wpadm-toolbar', plugins_url( "/template/css/tool-bar.css", dirname( __FILE__ )) );
            }

            public static function setUserMail()
            {
                if (isset($_POST['email'])) {
                    $email = sanitize_email( $_POST['email'] );
                    $mail = get_option(PREFIX_BACKUP_ . "email");
                    if ($mail) {
                        add_option(PREFIX_BACKUP_ . "email", $email);
                    } else {
                        update_option(PREFIX_BACKUP_ . "email",$email);
                    }
                } 
                echo 'true';
                wp_die();
            }
            public static function saveSetting()
            {
                if (isset($_POST['is_admin']) || isset($_POST['is_optimization']) || isset($_POST['is_local_backup_delete']) 
                || isset($_POST['is_repair']) || isset($_POST['time_error']) || isset($_POST['is_show_admin_bar'] ) || isset($_POST['backup_folder'] )
                || isset($_POST['clear_backup_folder']) || isset($_POST['zip_archive']) || isset( $_POST['tar_archive'] ) || isset( $_POST['targz_archive'] ) ) {

                    $dropbox_options = self::getSettings();

                    if (isset($_POST['time_error'])) {
                        $dropbox_options['time_error'] = (int)sanitize_text_field( $_POST['time_error'] );
                    }
                    if (isset($_POST['is_admin'])) {
                        $dropbox_options['is_admin'] = (int)sanitize_text_field( $_POST['is_admin'] );
                    }
                    if (isset($_POST['is_optimization'])) {
                        $dropbox_options['is_optimization'] = (int)sanitize_text_field( $_POST['is_optimization'] );
                    }
                    if (isset($_POST['is_local_backup_delete'])) {
                        $dropbox_options['is_local_backup_delete'] = (int)sanitize_text_field( $_POST['is_local_backup_delete'] );
                    } 
                    if (isset($_POST['is_repair'])) {
                        $dropbox_options['is_repair'] = (int)sanitize_text_field( $_POST['is_repair'] );
                    }  
                    if (isset($_POST['is_show_admin_bar'])) {
                        $dropbox_options['is_show_admin_bar'] = (int)sanitize_text_field( $_POST['is_show_admin_bar'] );
                    }
                    if (isset($_POST['backup_folder'])) {
                        $dropbox_options['backup_folder'] =  rtrim( sanitize_text_field( $_POST['backup_folder'] ), '/' );
                    }
                    if (isset($_POST['clear_backup_folder']) && $_POST['clear_backup_folder'] == 1) { 
                        if ( isset($dropbox_options['backup_folder']) ) {
                            unset($dropbox_options['backup_folder']);
                        }
                    }

                    $type_archive = '';
                    if (isset($_POST['zip_archive']) ) {
                        $type_archive =  'zip_archive';
                    } elseif (isset($_POST['tar_archive'])) {
                        $type_archive =  'tar_archive';
                    } elseif (isset($_POST['targz_archive'])) {
                        $type_archive =  'targz_archive';
                    }

                    if ( !empty($type_archive) ) {
                        if (isset(self::$type_archive[$type_archive])) {
                            if (isset($dropbox_options['type_archive'])) {
                                $dropbox_options['type_archive'] = array_merge( self::$type_archive, $dropbox_options['type_archive'] );
                            } else {
                                $dropbox_options['type_archive'] = self::$type_archive;
                            }
                            if ($dropbox_options['type_archive'][$type_archive] == 1) {
                                $dropbox_options['type_archive'][$type_archive] = 0;
                            } else {
                                $dropbox_options['type_archive'][$type_archive] = 1;
                            }
                        }
                    }

                    self::setSettings($dropbox_options);
                }
            }

            public static function getSettings()
            {
                $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                if ($dropbox_options) {
                    $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                } else {
                    $dropbox_options = array();
                }
                return $dropbox_options;
            }

            public static function setSettings($data, $key = '')
            {
                $dropbox_options = get_option(PREFIX_BACKUP_ . 'dropbox-setting');
                if ($dropbox_options) {
                    $dropbox_options = unserialize( base64_decode( $dropbox_options ) );
                } else {
                    $dropbox_options = array();
                }
                if ( !empty($key) ) {
                    if(isset($dropbox_options[$key])) {
                        $dropbox_options[$key] = $data;
                    }
                } else {
                    $dropbox_options = $data;
                }

                update_option(PREFIX_BACKUP_ . 'dropbox-setting', base64_encode(  serialize( $dropbox_options ) ) );

                return $dropbox_options;
            }

            public static function local_backup()
            {
                self::is_auth_user();
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-process.php";
                @session_write_close();
                parent::$type = 'full'; 
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    @unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }  
                if (file_exists(WPAdm_Core::getTmpDir() . "/log.log")) {
                    file_put_contents(WPAdm_Core::getTmpDir() . "/log.log", '');
                }   

                WPAdm_Core::rmdir(WPAdm_Core::getTmpDir() . "/log-archive.log");  

                WPAdm_Process::clear();

                $backup_dir = DROPBOX_BACKUP_DIR_BACKUP;
                $dropbox_options = self::getSettings();
                if ($dropbox_options) {
                    if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                        $backup_dir = $dropbox_options['backup_folder'];
                    }
                }
                WPAdm_Core::mkdir($backup_dir); 
                WPAdm_Running::init_params_default();
                self::testOtherArchives();
                $res['result'] = 'success';
                if (defined('DISABLE_WP_CRON')) {  
                    if (DISABLE_WP_CRON === true || DISABLE_WP_CRON == 'true') { 
                        $res['result'] = 'error';
                        $res['error'] = __('Please enable cron-tasks on your website.','dropbox-backup') . '<br /><br /><a href="javascript:void(0)" onclick="showEnableCron();" >' . __('How to enable cron-tasks on my website?','dropbox-backup') . '</a><br /><br />';
                        $res['data'] = array();
                        $res['size'] = 0;
                    }
                }

                if ( (isset($res['result']) && $res['result'] != 'error') ) {
                    if ( WPAdm_Core::dir_writeble($backup_dir) ) {
                        WPAdm_Running::delCommandResultData("local_backup");
                        $dropbox_options = self::getSettings();
                        $optimization = (isset($dropbox_options['is_optimization']) && $dropbox_options['is_optimization'] == 1) || (!isset($dropbox_options['is_optimization'])) ? 1 : 0;
                        $repair = (isset($dropbox_options['is_repair']) && $dropbox_options['is_repair'] == 1) ? 1 : 0;
                        $backup = new WPAdm_Core(array('method' => "local_backup", 'params' => array('optimize' => $optimization, 'repair' => $repair, 'limit' => 0, 'time' => (int)$_POST['time'], 'types' => array('db', 'files') )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                        if (WPAdm_Core::$cron === false) {
                            $res = $backup->getResult()->toArray();
                            $res['md5_data'] = md5( print_r($res, 1) );
                            $res['name'] = $backup->name;
                            $res['time'] = $backup->time;
                            $res['type'] = 'local';
                            $res['counts'] = count($res['data']);
                        } else {
                            set_transient('running_command', 'local_backup', HOUR_IN_SECONDS);
                            $res['result'] = 'work';
                            $res['error'] = '';
                            $res['data'] = array();
                            $res['size'] = 0;
                        }
                    } else {
                        $res['result'] = 'error';
                        $res['error'] = str_replace(array('%domain', '%dir-backup'), array(SITE_HOME, $backup_dir), __('Website "%domain" returned an error during file creation: Failed to create file, please check the permissions on the folder "%dir-backup".','dropbox-backup') );
                        $res['data'] = array();
                        $res['size'] = 0;
                    }
                }

                @session_start();
                echo json_encode($res);
                wp_die();

            }

            public static function getLog()
            {   
                self::is_auth_user();
                @session_write_close();
                @session_start();       
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-process.php";
                $backup = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                $log = WPAdm_Core::getLog();
                $log2 = WPAdm_Core::getTmpDir() . "/logs2";
                $log_array = array();
                if ( file_exists($log2) ) {
                    $text = @file_get_contents($log2);
                    if ($text == $log) {
                        $circle = WPAdm_Running::getCommandResultData( 'circle' );
                        if ( empty($circle['count']) || $circle['count'] == 0) {
                            $circle['count'] = 0;
                        }
                        $dropbox_options = self::getSettings();
                        if ($dropbox_options) {
                            if (isset($dropbox_options['time_error'])) {
                                self::$circle = ( (int)$dropbox_options['time_error'] * 6 );
                            }
                        }
                        $log_array['circle'] = self::$circle;
                        if ( $circle['count'] <= self::$circle ) {
                            $circle['count']++;
                            $circle['time'] = time();
                            WPAdm_Running::setCommandResultData( 'circle', $circle );
                        } else {
                            $date_systm = get_system_data();
                            $error_msg = __('There is not enough script running time to perform backup operations, please increase the PHP variable max_execution_time.', 'dropbox-backup');
                            if ( $date_systm['upMemoryLimit'] == 0 ) {
                                $error_msg = __('There is not enough memory to perform archiving of big files and continue backup operations, please increase the PHP variable memory_limit.', 'dropbox-backup');
                            }
                            $log_array['data'] = array(
                            'result' => 'error', 
                            'error' => $error_msg, 
                            'data' => null, 
                            'size' => 0 );
                            $type_backup = sanitize_text_field( $_POST['type-backup'] );
                            self::stopBackup(true, $type_backup);
                        }
                        $log_array['example'] = $circle;
                    } else {
                        WPAdm_Running::setCommandResultData( 'circle', array( 'count' => 0, 'time' => time() ) ); 
                    }
                    file_put_contents($log2, $log); 
                    $log = str_replace($text, "", $log);
                } else {
                    file_put_contents($log2, $log);
                }
                $log = explode("\n", $log);
                krsort($log);
                $log_array['log'] = $log;
                $type_backup = sanitize_text_field( $_POST['type-backup'] );
                $data_result = WPAdm_Running::getCommandResultData( $type_backup );
                if (!empty($data_result)) {
                    $log_array['data'] = $data_result;
                    set_transient('drb_running', 0, 1); 
                }
                if (isset($_POST['type-backup2'])) {
                    $type_backup2 = sanitize_text_field( $_POST['type-backup2'] );
                    $data_result = WPAdm_Running::getCommandResultData( $type_backup2 );
                    if (!empty($data_result) && $data_result['result'] != 'success') {
                        $log_array['data'] = $data_result;
                        set_transient('drb_running', 0, 1); 
                    }
                } 
                $log_array['processes'] = WPAdm_Process::getAll(); 
                if (defined('WP_CRON_LOCK_TIMEOUT')) {
                    $log_array['lock_cron'] = WP_CRON_LOCK_TIMEOUT; 
                }
                echo json_encode( $log_array );
                exit;
            }
            public static function restore_backup()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                @session_write_close();
                parent::$type = 'full'; 
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    @unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                $name_backup = isset($_POST['name']) ? sanitize_text_field( $_POST['name'] ) : "";
                $backup = new WPAdm_Core(array('method' => "local_restore", 'params' => array('types' => array('files', 'db'), 'name_backup' => $name_backup )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                $res = $backup->getResult()->toArray();
                @session_start();
                echo json_encode($res);
                wp_die();
            }
            public static function wpadm_restore_dropbox()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                @session_write_close();
                $log_class = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    @unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                if (file_exists(WPAdm_Core::getTmpDir() . "/log.log")) {
                    @unlink(WPAdm_Core::getTmpDir() . "/log.log");
                }
                WPAdm_Core::log( __('Start Restore from Dropbox cloud' ,'dropbox-backup')) ;
                $dropbox_options = self::getSettings();
                if ($dropbox_options && isset( $dropbox_options['app_key'] ) ) {
                    require_once DRBBACKUP_BASE_DIR. "/modules/dropbox.class.php";
                    $folder_project = self::getNameProject();
                    $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'], $dropbox_options['auth_token_secret']);
                    if ($dropbox->isAuth()) {
                        WPAdm_Core::mkdir(DROPBOX_BACKUP_DIR_BACKUP); 
                        $name_backup = isset($_POST['name']) ? sanitize_text_field( $_POST['name'] ): "";
                        $dir_backup = DROPBOX_BACKUP_DIR_BACKUP . "/$name_backup";
                        $error = WPAdm_Core::mkdir($dir_backup);
                        if (!empty($error)) {
                            WPAdm_Core::log($error);
                            $res['result'] = WPAdm_Result::WPADM_RESULT_ERROR;
                            $res['error'] = $error;
                            $res['data'] = array();
                            $res['size'] = 0;

                        } else {
                            $files = $dropbox->listing("$folder_project/$name_backup");
                            if (isset($files['items'])) {
                                $n = count($files['items']);
                                for($i = 0; $i < $n; $i++) {
                                    $res = $dropbox->downloadFile("$folder_project/$name_backup/{$files['items'][$i]['name']}", "$dir_backup/{$files['items'][$i]['name']}");
                                    if ($res != "$dir_backup/{$files['items'][$i]['name']}" && isset($res['text'])) {
                                        WPAdm_Core::log(__('Error: ' ,'dropbox-backup') . $res['text'] );
                                    } else {
                                        $log = str_replace('%s', $files['items'][$i]['name'], __('Download file (%s) with Dropbox' ,'dropbox-backup') );
                                        WPAdm_Core::log($log);
                                    }
                                }
                                parent::$type = 'full'; 
                                $backup = new WPAdm_Core(array('method' => "local_restore", 'params' => array('types' => array('files', 'db'), 'name_backup' => $name_backup )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                                $res = $backup->getResult()->toArray();
                                WPAdm_Core::rmdir($dir_backup);
                            }
                        }
                    } else {
                        WPAdm_Core::log( str_replace(array('%d', '%k', '%s'), 
                        array( SITE_HOME, $dropbox_options['app_key'], $dropbox_options['app_secret'] ), __('Website "%d" can\'t authorize on Dropbox with using of "app key: %k" and "app secret: %s"' ,'dropbox-backup')
                        ) );
                    }
                } else {
                    WPAdm_Core::log( str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') ) );
                }
                @session_start();
                echo json_encode($res);
                wp_die();
            }
            public static function download()
            {
                if (isset($_REQUEST['backup'])) {
                    $backup = sanitize_text_field($_REQUEST['backup']);
                    require_once DRBBACKUP_BASE_DIR . "/class-wpadm-core.php"; 
                    require_once DRBBACKUP_BASE_DIR . '/modules/pclzip.lib.php';
                    $backup = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                    $filename = $backup . ".zip";
                    $file = WPAdm_Core::getTmpDir() . "/" . $filename;
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                    $archive = new PclZip($file);
                    $dir_backup = DROPBOX_BACKUP_DIR_BACKUP . '/' . $backup;

                    $backups = array('data' => array(), 'md5' => '');
                    if (is_dir($dir_backup)) { 
                        $i = 0;
                        $dir_open = opendir($dir_backup);
                        while($d = readdir($dir_open)) {
                            if ($d != '.' && $d != '..' && file_exists($dir_backup . "/$d") && substr($d, -3) != "php") {
                                $archive->add($dir_backup . "/$d", PCLZIP_OPT_REMOVE_PATH, DROPBOX_BACKUP_DIR_BACKUP );
                            }
                        }
                    }


                    $now = gmdate("D, d M Y H:i:s");
                    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
                    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
                    header("Last-Modified: {$now} GMT");

                    // force download  
                    header("Content-Type: application/force-download");
                    header("Content-Type: application/octet-stream");
                    header("Content-Type: application/download");

                    // disposition / encoding on response body
                    header("Content-Disposition: attachment;filename={$filename}");
                    header("Content-Transfer-Encoding: binary");

                    ob_start();
                    $df = fopen("php://output", 'w');
                    echo file_get_contents($file);
                    fclose($df);
                    echo ob_get_clean();
                    @unlink($file);
                    exit;
                }
            }

            public static function delete_backup($type = '', $name = '')
            {
                if (self::$local_delete === false || empty(self::$local_delete)) {
                    self::is_auth_user();
                }
                if ( isset($_POST['backup-type']) ) {
                    $type = sanitize_text_field( $_POST['backup-type'] );
                } elseif( !empty($type) ) {
                    $type = sanitize_text_field( $type ); 
                }

                if (isset($_POST['backup-name'])) {
                    $name = sanitize_text_field( $_POST['backup-name'] );
                } elseif (!empty($name)) {
                    $name = sanitize_text_field( $name );
                }

                if ($type == 'local') {
                    require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                    $backup_dir = DROPBOX_BACKUP_DIR_BACKUP;
                    $dropbox_options = self::getSettings();
                    if ($dropbox_options) {
                        if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                            $backup_dir = $dropbox_options['backup_folder'];
                        }
                    }

                    $dir = $backup_dir . '/' . $name ;
                    $delete = false;
                    if (is_dir($dir)) {
                        WPAdm_Core::rmdir($dir);
                        $delete = true;
                    }
                    $dir = ABSPATH . WPADM_DIR_NAME .  '/' . $name ;
                    if (is_dir($dir)) {
                        WPAdm_Core::rmdir($dir);
                        $delete = true;
                    }
                    $dir = WPADM_DIR_BACKUP .  '/' . $name ;
                    if (is_dir($dir)) {
                        WPAdm_Core::rmdir($dir);
                        $delete = true;
                    }
                    if ($delete) {
                        parent::setMessage( str_replace('%s', $name, __('Backup(%s) was deleted','dropbox-backup') ) );
                    }
                } elseif ($type == 'dropbox') {
                    require_once DRBBACKUP_BASE_DIR . "/modules/dropbox.class.php";
                    $dropbox_options = self::getSettings();
                    if ($dropbox_options && isset($dropbox_options['app_key'])) {
                        if (isset($dropbox_options['auth_token_secret'])) {
                            $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'], $dropbox_options['auth_token_secret']);
                        } elseif (isset($dropbox_options['access_token']) && !empty($dropbox_options['token_type'])) {
                            $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'] );
                            $dropbox->setAccessToken2($dropbox_options['access_token'], $dropbox_options['token_type']);
                        }
                        $folder_project = self::getNameProject();
                        $res = $dropbox->deleteFile("$folder_project/{$name}");
                        if ($res['is_deleted'] === true) {
                            parent::setMessage( str_replace('%s', $name, __('Backup(%s) was deleted','dropbox-backup') ) );
                        }
                    } 
                }
                if (self::$local_delete === false || empty(self::$local_delete)) {
                    header("Location: " . $_SERVER['HTTP_REFERER']);
                }
            }

            protected static function getPluginName()
            {

                preg_match("|wpadm_wp_(.*)|", __CLASS__, $m);
                return $m[1];
            }
            protected static function getPathPlugin()
            {
                return "wpadm_full_backup_dropbox";
            }

            public static function dropboxConnect()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/dropbox.class.php";
                if (isset($_GET['app_key']) && isset($_GET['app_secret'])) {
                    if (empty($_GET['app_key']) && empty($_GET['app_secret'])) {
                        $app_key =  WPADM_APP_KEY;
                        $app_secret =  WPADM_APP_SECRET;
                    } else {
                        $app_key = sanitize_text_field($_GET['app_key']);
                        $app_secret = sanitize_text_field($_GET['app_secret']);
                    }
                    $dropbox = new dropbox($app_key, $app_secret);
                    $_SESSION['dropbox_key'] = $app_key; 
                    $_SESSION['dropbox_secret'] = $app_secret; 
                    $_SESSION['dropbox_request_token'] = $dropbox->getRequestToken();
                    $dropbox->is_oauth2(true);
                    echo '<script>window.location.href="' . $dropbox->generateAuthUrl( admin_url('admin-post.php?action=dropboxConnect') ) . '";</script>';
                } elseif (isset($_GET['oauth_token']) && isset($_GET['uid'])) {
                    $dropbox_options = self::getSettings();
                    $dropbox = new dropbox(@$_SESSION['dropbox_key'], @$_SESSION['dropbox_secret']);
                    $access_token = $dropbox->getAccessToken($_SESSION['dropbox_request_token']);
                    $dropbox_options['app_key'] = @$_SESSION['dropbox_key'] ;
                    $dropbox_options['app_secret'] = @$_SESSION['dropbox_secret'] ;
                    $dropbox_options['auth_token_secret'] = $access_token;
                    $dropbox_options['oauth_token'] = sanitize_text_field( @$_GET['oauth_token'] ) ;
                    $dropbox_options['uid'] = sanitize_text_field( @$_GET['uid'] ) ;
                    self::setSettings($dropbox_options);
                    echo '<script>
                    if(window.opener){
                    window.opener.connectDropbox(null, null, "'. htmlspecialchars($access_token['oauth_token_secret']) . '", "'.htmlspecialchars($access_token['oauth_token']).'", "'.htmlspecialchars($access_token['uid']).'");window.close();
                    }else{
                    window.location.href="' . admin_url("admin.php?page=wpadm_wp_full_backup_dropbox") . '";
                    }
                    </script>';
                    echo '<script>window.close();</script>';
                    exit;
                } elseif (isset($_GET['not_approved'])) {
                    if( $_GET['not_approved'] == 'true' ){   // check of GET params == string 'true'
                        echo '<script>window.close();</script>';
                        exit;
                    }
                } elseif( isset($_POST['dropbox_code_auth']) ) {
                    $dropbox = new dropbox( @$_SESSION['dropbox_key'], @$_SESSION['dropbox_secret'] );
                    $dropbox->is_oauth2(true);
                    $token = $dropbox->getAccessToken( sanitize_text_field( $_POST['dropbox_code_auth'] ) );
                    if (isset( $token['access_token'] ) && isset( $token['token_type'] ) ) {
                        $dropbox_options = self::getSettings();
                        $dropbox_options['app_key'] = @$_SESSION['dropbox_key'] ;
                        $dropbox_options['app_secret'] = @$_SESSION['dropbox_secret'] ;
                        $dropbox_options['uid'] = $token['uid'] ; 
                        $dropbox_options['access_token'] = $token['access_token'];
                        $dropbox_options['token_type'] = $token['token_type'];
                        if (isset($dropbox_options['auth_token_secret'])) {
                            unset($dropbox_options['auth_token_secret']);
                        }
                        if (isset($dropbox_options['oauth_token'])) {
                            unset($dropbox_options['oauth_token']);
                        }
                        self::setSettings($dropbox_options);

                    }
                    echo json_encode( array( 'uid' => @$token['uid'] ) );  
                    exit;
                } else {
                    WPAdm_Core::log( str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') ) );
                }
                exit;
            }

            public static function dropbox_backup_create()
            {      
                self::is_auth_user();
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-core.php";
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-process.php";
                @session_write_close();          

                $log = new WPAdm_Core(array('method' => "local"), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                if (file_exists(WPAdm_Core::getTmpDir() . "/logs2")) {
                    @unlink(WPAdm_Core::getTmpDir() . "/logs2");
                }
                if (file_exists(WPAdm_Core::getTmpDir() . "/log.log")) {
                    file_put_contents(WPAdm_Core::getTmpDir() . "/log.log", '');
                }  

                WPAdm_Core::rmdir(WPAdm_Core::getTmpDir() . "/log-archive.log");  

                WPAdm_Process::clear();  

                $backup_dir = DROPBOX_BACKUP_DIR_BACKUP;
                $dropbox_options = self::getSettings();
                if ($dropbox_options) {
                    if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                        $backup_dir = $dropbox_options['backup_folder'];
                    }
                }

                if ( WPAdm_Core::dir_writeble($backup_dir) ) {
                    $send_to_dropbox = true;
                    if ($dropbox_options) {
                        if (!isset($dropbox_options['app_key'])) {
                            WPAdm_Core::log( str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "App Key" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') ) );
                            $send_to_dropbox = false;
                            $res['result'] = 'error';
                            $res['error'] = str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "App Key" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') );
                            $res['data'] = array();
                            $res['size'] = 0;
                        }
                        if (!isset($dropbox_options['app_secret'])) {
                            WPAdm_Core::log( str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "App Secret" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') ) );
                            $send_to_dropbox = false;

                            $res['result'] = 'error';
                            $res['error'] = str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "App Secret" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') );
                            $res['data'] = array();
                            $res['size'] = 0;
                        }


                        if ( !isset($dropbox_options['oauth_token']) && (!isset($dropbox_options['access_token']) || !isset($dropbox_options['token_type']) )) {
                            $log = str_replace('%d', SITE_HOME, __('Website "%d" returned an error during file sending to Dropbox: "Auth Token not exist. Files cannot be sent to Dropbox cloud. Please, check your Dropbox settings."' ,'dropbox-backup') );
                            WPAdm_Core::log( $log );    
                            $send_to_dropbox = false;
                            $res['result'] = 'error';
                            $res['error'] = $log;
                            $res['data'] = array();
                            $res['size'] = 0;
                        }
                    } else {
                        WPAdm_Core::log( str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') ) );
                        $res['type'] = 'local';
                        $send_to_dropbox = false;

                        $res['result'] = 'error';
                        $res['error'] = str_replace('%d', SITE_HOME, __('Website "%d" returned an error during connection to Dropbox: "app key" and "app secret" wasn\'t found. Please, check your Dropbox settings.' ,'dropbox-backup') );
                        $res['data'] = array();
                        $res['size'] = 0;

                    }

                    if (defined('DISABLE_WP_CRON')) {
                        if (DISABLE_WP_CRON === true || DISABLE_WP_CRON == 'true') {
                            $res['result'] = 'error';
                            $res['error'] = __('Please enable cron-tasks on your website.','dropbox-backup') . '<br /><br /><a href="javascript:void(0)" onclick="showEnableCron();" >' . __('How to enable cron-tasks on my website?','dropbox-backup') . '</a><br /><br />';
                            $res['data'] = array();
                            $res['size'] = 0;
                            $send_to_dropbox = false;
                        }
                    }

                    if ($send_to_dropbox) {
                        parent::$type = 'full'; 
                        WPAdm_Running::init_params_default();
                        self::testOtherArchives();
                        WPAdm_Running::delCommandResultData("local_backup");

                        $dropbox_options = self::getSettings();
                        $optimization = (isset($dropbox_options['is_optimization']) && $dropbox_options['is_optimization'] == 1) || (!isset($dropbox_options['is_optimization'])) ? 1 : 0;
                        $repair = (isset($dropbox_options['is_repair']) && $dropbox_options['is_repair'] == 1) ? 1 : 0;

                        $backup_local = new WPAdm_Core(array('method' => "local_backup", 'params' => array('optimize' => $optimization, 'repair' => $repair, 'limit' => 0, 'time' => (int)$_POST['time'], 'types' => array('db', 'files') )), 'full_backup_dropbox', WPAdm_Core::$pl_dir);
                        $res = array();
                        if (WPAdm_Core::$cron === false) {
                            $res = $backup->getResult()->toArray();
                            $res['md5_data'] = md5( print_r($res, 1) );
                            $res['name'] = $backup->name;
                            $res['time'] = $backup->time;
                            $res['type'] = 'dropbox';
                            $res['counts'] = count($res['data']);
                        } 
                        unset($backup_local);
                        $folder_project = self::getNameProject();
                        WPAdm_Running::delCommandResultData("send-to-dropbox");

                        WPAdm_Running::delCommandResultData("send-to-dropbox");
                        if (isset($dropbox_options['token_type']) && isset($dropbox_options['access_token'])) {
                            $token = $dropbox_options['access_token']; 
                            $type  = $dropbox_options['token_type'];
                        } else {
                            $type = '';
                            $token = @$dropbox_options['auth_token_secret'];
                        }

                        $backup = new WPAdm_Core(array('method' => "send-to-dropbox", 
                        'params' => array('files' => isset($res['data']) ? $res['data'] : '', 
                        'local' => true,
                        'is_local_backup' => ( isset($dropbox_options['is_local_backup_delete']) && $dropbox_options['is_local_backup_delete'] == 1 ? $dropbox_options['is_local_backup_delete'] : 0 ),
                        'access_details' => array('key' => $dropbox_options['app_key'], 
                        'secret' => $dropbox_options['app_secret'], 
                        'token' => $token,
                        'type' => $type,

                        'dir' => isset($res['name']) ? $res['name'] : '',
                        'folder' => $folder_project),
                        'time' => (int)$_POST['time'],
                        'is_folder_set'  => isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder']),
                        )
                        ),
                        'full_backup_dropbox', WPAdm_Core::$pl_dir) ;
                        if (WPAdm_Core::$cron === false) {
                            $result_send = $backup->getResult()->toArray();
                            if ($result_send['result'] == 'error') {
                                $res = array();
                                $res['error'] = $result_send['error'];
                                $res['result'] = 'error';
                                @rename(WPAdm_Core::getTmpDir() . "/logs2", WPAdm_Core::getTmpDir() . "/logs_error_" . $backup_local->time);
                            }
                            WPAdm_Core::rmdir( $backup_dir . "/{$res['name']}");
                        } else {
                            set_transient('running_command', 'send-to-dropbox', HOUR_IN_SECONDS);
                            $res['result'] = 'work';
                            $res['error'] = '';
                            $res['data'] = array();
                            $res['size'] = 0;
                        }
                    } 
                } else {
                    $res['result'] = 'error';
                    $res['error'] = str_replace(array('%domain', '%dir-backup'), array(SITE_HOME, $backup_dir), __('Website "%domain" returned an error during file creation: Failed to create file, please check the permissions on the folder "%dir-backup".','dropbox-backup')  );
                    $res['data'] = array();
                    $res['size'] = 0;
                }
                @session_start();
                echo json_encode($res);
                wp_die(); 
            }
            public static function getNameProject()
            {
                $folder_project = str_ireplace( array("http://", "https://"), '', home_url() );
                $folder_project = str_ireplace( array( "-", '/', '.'), '_', $folder_project );
                $folder_project = str_ireplace( array( 'Ä',  'ä',  'Ö',  'ö', 'ß',  'Ü',  'ü', 'å'), 
                array('ae', 'ae', 'oe', 'oe', 's', 'ue', 'ue', 'a'), 
                $folder_project ); 
                return $folder_project;
            }

            public static function wpadm_show_backup()
            {
                require_once DRBBACKUP_BASE_DIR. "/modules/dropbox.class.php";
                parent::$type = 'full';
                $dropbox_options = self::getSettings();
                $stop_precess = WPAdm_Running::getCommandResultData('stop_process');
                $name_backup = isset($stop_precess['name']) ? $stop_precess['name'] : '' ;
                if ($dropbox_options) {
                    if (isset($dropbox_options['app_key']) && isset($dropbox_options['app_secret']) && ( isset( $dropbox_options['auth_token_secret'] ) || isset($dropbox_options['access_token']) ) ) {

                        if ( isset( $dropbox_options['auth_token_secret'] ) ) {
                            $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret'], $dropbox_options['auth_token_secret']);
                        } elseif( isset($dropbox_options['access_token']) ) {
                            $dropbox = new dropbox($dropbox_options['app_key'], $dropbox_options['app_secret']);
                            $dropbox->setAccessToken2($dropbox_options['access_token'], $dropbox_options['token_type']);
                        }
                        
                        $folder_project = self::getNameProject();
                        $backups = $dropbox->listing($folder_project);
                        $n = count($backups['items']);
                        $data['data'] = array();
                        $not_all_upload = false;
                        for($i = 0; $i < $n; $i++) {
                            if ($name_backup != $backups['items'][$i]['name']) {
                                $backup = $dropbox->listing($folder_project . "/" . $backups['items'][$i]['name']); 
                                $data['data'][$i]['name'] = $backups['items'][$i]['name'];
                                $data['data'][$i]['dt'] = parent::getDateInName($backups['items'][$i]['name']);
                                $data['data'][$i]['count'] = count($backup['items']);
                                $data['data'][$i]['type'] = 'dropbox';
                                $k = $data['data'][$i]['count'];
                                $size = 0;
                                $not_all_upload = false;
                                $data['data'][$i]['files'] = '[';
                                for($j = 0; $j < $k; $j++) {
                                    if ( strpos($backup['items'][$j]['name'] , '.md5') !== false || strpos($backup['items'][$j]['name'] , '.zip') !== true || strpos($backup['items'][$j]['name'] , '.tar.gz' ) !== false ) {
                                        if ( strpos($backup['items'][$j]['name'] , '.md5') !== false ) {
                                            $not_all_upload = true;
                                        }
                                        $data['data'][$i]['files'] .= $backup['items'][$j]['name'] . ',';
                                        $size += $backup['items'][$j]['size'];
                                    }
                                }
                                $data['data'][$i]['size'] = $size;
                                $data['data'][$i]['not_all_upload'] = $not_all_upload;

                            }
                        }
                    }
                } 
                if (isset($_GET['pay']) && $_GET['pay'] == 'success') {    // check of GET params == string 'success'
                    if (!file_exists(WPAdm_Core::getTmpDir() . "/pay_success")) {
                        file_put_contents(WPAdm_Core::getTmpDir() . "/pay_success", 1);
                        parent::setMessage( 'Checkout was successfully' );
                    }
                }
                if (isset($_GET['pay']) && $_GET['pay'] == 'cancel') {   // check of GET params == string 'cancel'
                    parent::setError( __('Checkout was canceled','dropbox-backup') );
                }
                $data_local = parent::read_backups();
                if (isset($data['data'])) {
                    $data['data'] = array_merge($data_local['data'], $data['data']);
                    $data['md5'] = md5( print_r( $data['data'] , 1 ) );
                } else {
                    $data = $data_local;
                }
                if (file_exists(WPAdm_Core::getTmpDir() . "/pay_success")) {
                    $plugin_info = get_plugins("/" . parent::$plugin_name);
                    $plugin_version = (isset($plugin_info[parent::$plugin_name . '.php']['Version']) ? $plugin_info[parent::$plugin_name . '.php']['Version'] : '');
                    $data_server = parent::sendToServer(
                    array(
                    'actApi' => "proBackupCheck",
                    'site' => home_url(),
                    'email' => get_option('admin_email'),
                    'plugin' => parent::$plugin_name,
                    'key' => '',
                    'plugin_version' => $plugin_version
                    )
                    ); 
                    if (isset($data_server['status']) && $data_server['status'] == 'success' && isset($data_server['key'])) {
                        update_option(PREFIX_BACKUP_ . 'pro-key', $data_server['key']);
                        if (isset($data_server['url']) && !empty($data_server['url'])) {
                            parent::setMessage( str_replace('&s', $data_server['url'], __('The "Dropbox backup & restore PRO" version can be downloaded here <a href="&s">download</a>','dropbox-backup') )  );
                        }
                    }
                }
                if ( ! function_exists( 'get_plugins' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $stars5 = file_exists( WPAdm_Core::getTmpDir() . "/notice-star");
                $plugin_data = array_values( get_plugins('/dropbox-backup') );
                $is_runnig = WPAdm_Running::is_running();
                $running_backup = WPAdm_Running::getCommand( 'local_backup' );
                if($running_backup === false) {
                    $running_backup = WPAdm_Running::getCommand( 'send-to-dropbox' ); 
                }
                if ( isset($running_backup['params']['time']) ) {
                    $name = get_option('siteurl');

                    $name_running_backup = str_replace("http://", '', $name);
                    $name_running_backup = str_replace("https://", '', $name_running_backup);
                    $name_running_backup = preg_replace("|\W|", "_", $name_running_backup);
                    $name_running_backup .= '-' . wpadm_class::$type . '-' . date("Y_m_d_H_i", $running_backup['params']['time']);
                }

                $backu_dir = DROPBOX_BACKUP_DIR_BACKUP;
                if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                    $backu_dir = $dropbox_options['backup_folder'];
                } 
                if ( !file_exists( $backu_dir . '/local-key') ) {
                    WPAdm_Core::mkdir($backu_dir);
                    $key = md5(time() . 'wpadm-key');
                    file_put_contents($backu_dir . '/local-key', base64_encode( serialize( array('key' => $key, 'time-update' => time() + 3600) ) ) );
                } else {
                    $key_values = unserialize( base64_decode(  file_get_contents($backu_dir . '/local-key') ) );
                    if (isset($key_values['time-update']) && $key_values['time-update'] <= time() ) {
                        $key = md5( time() . 'wpadm-key' );
                        file_put_contents($backu_dir . '/local-key', base64_encode( serialize ( array( 'key' => $key, 'time-update' => time() + 3600 ) ) ) );
                    } else {
                        $key = $key_values['key'];
                    }
                }

                $show = !get_option('wpadm_pub_key') && is_super_admin();
                $error = parent::getError(true);
                if ( !empty( WPAdm_Core::$error ) ) {
                    $error .= '<br />' . WPAdm_Core::$error;
                }
                $msg = parent::getMessage(true); 
                $default = self::$circle / 6; // 18 request for log files, one request every 10 seconds
                $base_path = DRBBACKUP_BASE_DIR ;

                if ( !isset( $dropbox_options['type_archive'] ) ) {
                    $dropbox_options['type_archive'] = self::$type_archive;
                } else {
                    $dropbox_options['type_archive'] = array_merge( self::$type_archive, $dropbox_options['type_archive'] );
                }

                $nonce = wp_create_nonce( 'wpadm_nonce' );

                ob_start();
                require_once $base_path . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "wpadm_show_backup.php";
                echo ob_get_clean();
            }

            public static function draw_menu()
            {
                $show = true;
                $dropbox_options = self::getSettings();

                if ( ( isset($dropbox_options['is_admin']) && $dropbox_options['is_admin'] == 1 ) || !isset($dropbox_options['is_admin']) ) {
                    if (!is_admin() || !is_super_admin()) {
                        $show = false;
                    }
                    if ( !current_user_can('administrator') ) {
                        $show = false;
                    }

                }
                if ($show) {
                    $menu_position = '1.9998887771'; 
                    if(self::checkInstallWpadmPlugins()) {      
                        $page = add_menu_page(
                        'WPAdm', 
                        'WPAdm', 
                        "read", 
                        'wpadm_plugins', 
                        'wpadm_plugins',
                        plugins_url('/img/wpadm-logo.png', dirname( __FILE__ )),
                        $menu_position     
                        );
                        add_submenu_page(
                        'wpadm_plugins', 
                        "Dropbox Full Backup",
                        "Dropbox Full Backup",
                        'read',
                        'wpadm_wp_full_backup_dropbox',
                        array('wpadm_wp_full_backup_dropbox', 'wpadm_show_backup')
                        );   
                    } else {        
                        $page = add_menu_page(
                        'Dropbox Full Backup', 
                        'Dropbox Full Backup', 
                        "read", 
                        'wpadm_wp_full_backup_dropbox', 
                        array('wpadm_wp_full_backup_dropbox', 'wpadm_show_backup'),
                        plugins_url('/img/wpadm-logo.png', dirname( __FILE__ ) ),
                        $menu_position     
                        );

                        add_submenu_page(
                        'wpadm_wp_full_backup_dropbox', 
                        "WPAdm",
                        "WPAdm",
                        'read',
                        'wpadm_plugins',
                        'wpadm_plugins'
                        );  
                    }

                }
            }
            public static function notice()
            {                      
                // check of isset GET params "page"                                                                            
                if (!isset($_GET['page']) || ( isset($_GET['page']) && $_GET['page'] != 'wpadm_wp_full_backup_dropbox' ) ) {
                    $notice_file = DRBBACKUP_BASE_DIR . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . "notice.php";
                    if (!file_exists(WPAdm_Core::getTmpDir() . "/notice") && file_exists($notice_file)) {
                        ob_start();
                        include_once $notice_file;
                        echo ob_get_clean();
                    }
                }
            }
            public static function hide_notice()
            {
                if (isset($_GET['type'])) {
                    $type = sanitize_text_field( $_GET['type'] );
                    switch( $type ) {
                        case 'preview' :
                            file_put_contents(WPAdm_Core::getTmpDir() . "/notice", 1);
                            break;
                        case 'star' :
                            if (isset($_GET['hide']) && $_GET['hide'] == '1d') {
                                file_put_contents(WPAdm_Core::getTmpDir() . "/notice-star", time() . '_w');
                            } elseif ( ( isset($_GET['hide']) && $_GET['hide'] == 'week' ) || !isset($_GET['hide']) ) {
                                file_put_contents(WPAdm_Core::getTmpDir() . "/notice-star", 0);
                            }
                            break;
                    }
                }
                header('location:' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            public static function testOtherArchives()
            {
                require_once DRBBACKUP_BASE_DIR . "/modules/class-wpadm-archive.php";  
                // test type archive 
                $archive = new WPAdm_Archive(WPAdm_Core::getTmpDir() . '/test.zip');
                $archive->setRemovePath(rtrim( ABSPATH, '/' ) );
                $zip_test = $archive->zipArhive( array( WPAdm_Core::getTmpDir() . '/index.php' , WPAdm_Core::getTmpDir() . '/.htaccess' ) );
                if ( $zip_test ) {
                    WPAdm_Running::setCommandResultData('test_zip_archive', true);
                    WPAdm_Core::log( __('Testing of ZIP under shell was finished successfully', 'dropbox-backup') );
                }

                $targz_archive = $archive->targzArchive( array( WPAdm_Core::getTmpDir() . '/index.php' , WPAdm_Core::getTmpDir() . '/.htaccess' ) );
                if ( $targz_archive ) {
                    WPAdm_Running::setCommandResultData('test_targz_archive', true);
                    WPAdm_Core::log( __('Testing of TarGz was finished successfully', 'dropbox-backup') );
                }
            }

            static function getTypeBackup()
            {
                $dropbox_options = self::getSettings();
                if ( !isset( $dropbox_options['type_archive'] ) ) {
                    $dropbox_options['type_archive'] = self::$type_archive;
                } else {
                    $dropbox_options['type_archive'] = array_merge( self::$type_archive, $dropbox_options['type_archive'] );
                }
                return $dropbox_options['type_archive'];
            }
        }
    }

?>
