<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPAdm_Method_Local_Backup')) {
    class WPAdm_Method_Local_Backup extends WPAdm_Method_Class {

        private $start = true;

        private $tar_gz = false;

        public function __construct($params)
        {

            if ( WPAdm_Running::is_stop() ) {
                parent::__construct($params);
                $this->init(
                array(
                'id' => uniqid('wpadm_method__local_backup__'),
                'stime' => time(),
                )
                );

                WPAdm_Core::log(__('Create Unique Id ','dropbox-backup') . $this->id);


                $name = get_option('siteurl');

                if (!class_exists('WPAdm_Process')) {
                    require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-process.php'; 
                }

                $name = str_replace("http://", '', $name);
                $name = str_replace("https://", '', $name);
                $name = str_ireplace( array( 'Ä',  'ä',  'Ö',  'ö', 'ß',  'Ü',  'ü', 'å'), 
                array('ae', 'ae', 'oe', 'oe', 's', 'ue', 'ue', 'a'), 
                $name );
                $name = preg_replace("|\W|", "_", $name);  
                if (isset($params['time']) && !empty($params['time'])) { // time  1432751372
                    $this->time = date("Y-m-d H:i", $params['time']);
                    $name .= '-' . wpadm_class::$type . '-' . date("Y_m_d_H_i", $params['time']);
                } else {
                    $this->time = date("Y-m-d H:i");   //23.04.2015 13:45  
                    $name .= '-' . wpadm_class::$type . '-' . date("Y_m_d_H_i");
                }
                $this->name = $name;

                // folder for backup


                $dropbox_options = wpadm_wp_full_backup_dropbox::getSettings();

                $this->base_dir = DROPBOX_BACKUP_DIR_BACKUP; 
                $this->dir = DROPBOX_BACKUP_DIR_BACKUP . '/' . $name;
                if ($dropbox_options) {
                    if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder']) ) {
                        $this->dir = $dropbox_options['backup_folder'] . '/' . $name;
                        $this->base_dir = $dropbox_options['backup_folder']; 
                    }
                } 



                $opt_folder = WPAdm_Running::getCommandResultData('folder_create');
                if (!isset($opt_folder[$name])) {
                    if (($f = $this->checkBackup()) !== false) {
                        $this->dir = DROPBOX_BACKUP_DIR_BACKUP . '/' . $f;
                        if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                            $this->dir = $dropbox_options['backup_folder'] . '/' . $f;
                        }
                    } 
                    if (isset($dropbox_options['backup_folder']) && !empty($dropbox_options['backup_folder'])) {
                        $error = WPAdm_Core::mkdir($dropbox_options['backup_folder']);
                        if (!empty($error)) {
                            $this->result->setError($error);
                            $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                        }
                    } else {
                        $error = WPAdm_Core::mkdir(DROPBOX_BACKUP_DIR_BACKUP);
                        if (!empty($error)) {
                            $this->result->setError($error);
                            $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                        }
                    }
                    $error = WPAdm_Core::mkdir($this->dir);
                    if (!empty($error)) {
                        $this->result->setError($error);
                        $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                    }
                    $opt_folder = WPAdm_Running::getCommandResultData('folder_create');
                    $opt_folder[$name] = true;
                    WPAdm_Running::setCommandResultData('folder_create', $opt_folder);
                }
            }
        }
        public function checkBackup()
        {
            if (WPAdm_Running::getCommand('local_backup') !== false) {
                $archives = glob("{$this->dir}");
                if (empty($archives) && count($archives) <= 1) {
                    return false;
                }
                $n = count($archives);
                $f = "{$this->name}({$n})";
                return $f;
            }
            return false;
        }
        public function getResult()
        {

            $errors = array();
            if ( WPAdm_Running::is_stop() ) {
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
                $this->result->setError('');
                WPAdm_Core::log(__('Start Backup process...', 'dropbox-backup'));

                # create db dump
                if (in_array('db', $this->params['types']) ) {

                    $mysql_dump_file = DROPBOX_BACKUP_DIR_BACKUP . '/mysqldump.sql';
                    if ( !WPAdm_Running::getCommandResult('db') ) {
                        WPAdm_Running::setCommandResult('db');
                        WPAdm_Core::log(__('Creating Database Dump','dropbox-backup'));
                        $error = WPAdm_Core::mkdir(DROPBOX_BACKUP_DIR_BACKUP);
                        if (!empty($error)) {
                            $this->result->setError($error);
                            $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                            return $this->result; 
                        }
                        if (file_exists($mysql_dump_file) && !file_exists(WPAdm_Core::getTmpDir() . "/db")) {
                            unlink($mysql_dump_file);
                        }
                        $wp_mysql_params = $this->getWpMysqlParams();

                        if ( isset($this->params['repair']) && ( $this->params['repair'] == 1 ) ) { 
                            if ( WPAdm_Running::is_stop() ) {
                                if (!class_exists('WPAdm_Mysqldump')) {
                                    require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-mysqldump.php';
                                }

                                $mysql = new WPAdm_Mysqldump();
                                $mysql->host = $wp_mysql_params['host'];
                                $mysql->user = $wp_mysql_params['user']; 
                                $mysql->password = $wp_mysql_params['password'];
                                try {
                                    $mysql->repair($wp_mysql_params['db']);
                                    unset($mysql);
                                } catch (Exception $e) {
                                    $this->result->setError( $e->getMessage() );
                                    $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                                    return $this->result;
                                } 
                                unset($mysql); 
                            }
                        }

                        if (isset($this->params['optimize']) && ($this->params['optimize'] == 1 ) ) {
                            $opt_db = WPAdm_Running::getCommandResultData('db');
                            if (!isset($opt_db['optimize'])) {
                                if ( WPAdm_Running::is_stop() ) {
                                    //WPAdm_Core::log(__('Optimize Database Tables','dropbox-backup'));
                                    if (!class_exists('WPAdm_Mysqldump')) {
                                        require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-mysqldump.php';
                                    }
                                    $mysql = new WPAdm_Mysqldump();
                                    $mysql->host = $wp_mysql_params['host'];
                                    $mysql->user = $wp_mysql_params['user']; 
                                    $mysql->password = $wp_mysql_params['password'];
                                    try {
                                        $mysql->optimize($wp_mysql_params['db']);
                                    } catch (Exception $e) {
                                        $this->result->setError( $e->getMessage() );
                                        $this->result->setResult( WPAdm_Result::WPADM_RESULT_ERROR );
                                        return $this->result;
                                    } 
                                    unset($mysql); 
                                }
                            }
                        } 


                        if ( WPAdm_Running::is_stop() ) {
                            if ( WPAdm_Running::getCommandResult('db') === false) {

                                if (!class_exists('WPAdm_Mysqldump')) {
                                    require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-mysqldump.php';
                                }
                                $mysql = new WPAdm_Mysqldump();
                                $mysql->host = $wp_mysql_params['host'];
                                $mysql->user = $wp_mysql_params['user']; 
                                $mysql->password = $wp_mysql_params['password'];

                                try {
                                    $mysql->mysqldump($wp_mysql_params['db'], $mysql_dump_file);
                                } catch (Exception $e) {
                                    $this->result->setError( $e->getMessage() );
                                    $this->result->setResult( WPAdm_Result::WPADM_RESULT_ERROR );
                                    return $this->result;
                                } 
                                unset($mysql);

                                if (0 == (int)filesize($mysql_dump_file)) {
                                    $log = str_replace(array('%domain', '%dir'), array(SITE_HOME, DROPBOX_BACKUP_DIR_BACKUP), __('Website "%domain" returned an error during database dump creation: Database-Dump file is emplty. To solve this problem, please check permissions to folder: "%dir".','dropbox-backup') );
                                    $errors[] = $log;
                                    WPAdm_Core::log($log);
                                    $this->result->setError( $log );
                                    $this->result->setResult( WPAdm_Result::WPADM_RESULT_ERROR );
                                    return $this->result;
                                } else {
                                    $size_dump = round( (filesize($mysql_dump_file) / 1024 / 1024) , 2);
                                    $log = str_replace("%size", $size_dump , __('Database Dump was successfully created ( %size Mb) : ','dropbox-backup') ) ;
                                    WPAdm_Core::log($log . $mysql_dump_file);
                                }
                                WPAdm_Running::setCommandResult('db', true);
                            }
                        }
                    }
                }

                if (count($errors) == 0) {
                    if ( WPAdm_Running::is_stop() ) {
                        $command_files_list = WPAdm_Running::getCommandResultData('files');
                        if (in_array('files', $this->params['types']) && empty($command_files_list) ) {
                            $files = $this->createListFilesForArchive();
                            $files = explode('<!>', utf8_encode( implode( '<!>', $files ) ) );
                            WPAdm_Running::setCommandResultData('files', $files);
                        } else {
                            $files = $command_files_list;
                        }
                        if (isset($mysql_dump_file) && file_exists($mysql_dump_file) && filesize($mysql_dump_file) > 0) {
                            $files[] = $mysql_dump_file;
                        }
                        WPAdm_Process::init('archiving', count($files) );
                        if (empty($files)) {
                            $errors[] = str_replace(array('%d'), array(SITE_HOME), __( 'Website "%d" returned an error during creation of the list of files for a backup: list of files for a backup is empty. To solve this problem, please check files and folders permissions for website "%d".' ,'dropbox-backup') );
                        }
                    } else {
                        return true;
                    }

                    // split the file list by 170kbayt lists, To break one big task into smaller
                    if ( WPAdm_Running::is_stop() ) {
                        $files2 = WPAdm_Running::getCommandResultData('files2');
                        if (empty($files2)) {
                            $files2 = array();
                            $files2[0] = array();
                            $i = 0;
                            $size = 0;
                            $chunk_size = 170000; //~170kbyte
                            $targz = WPAdm_Running::getCommandResultData('test_targz_archive');
                            $files_count = 0;
                            if (!empty($targz)) {
                                $chunk_size = 1572864; // ~ 1.5 Mbyte
                            }
                            $size_part = array();
                            foreach($files as $f) {
                                if ($size > $chunk_size) {
                                    $size_part[$i] = $size;
                                    $i ++;
                                    $size = 0;
                                    $files2[$i] = array();
                                }
                                $f_size =(int)@filesize($f);
                                $permission_file = wpadm_class::perm($f);
                                if ( ! version_compare($permission_file, '0400', '>=') ) {
                                    WPAdm_Core::log('Skip file ' . $f . ' Size ' . WPADM_getSize($f_size) . " (" . $permission_file . ")" );
                                    continue;
                                }
                                if ($f_size == 0 || $f_size > 1000000) {
                                    WPAdm_Core::log('File ' . $f . ' Size ' . WPADM_getSize($f_size) . " (" . $permission_file . ")" );
                                }
                                $size += $f_size;
                                $files2[$i][] = utf8_decode( $f );
                                $files_count ++;
                            }
                            WPAdm_Running::setCommandResultData('files2', $files2);
                            WPAdm_Running::setCommandResultData('files2_count', $files_count );
                        }
                    } else {
                        return true;
                    }
                    $files_count = WPAdm_Running::getCommandResultData('files2_count', $files_count );
                    if (!empty($files_count) && $files_count != count($files) ) {
                        WPAdm_Process::init('archiving', $files_count );
                    }

                    WPAdm_Core::log(__('List of backup files was successfully created','dropbox-backup') );
                    if ( !WPAdm_Running::getCommandResult('archive') ) {
                        if ( WPAdm_Running::is_stop() ) {
                            WPAdm_Core::log( __('Backup of files was started','dropbox-backup') );
                            WPAdm_Running::setCommandResult('archive');
                            $files_archive = WPAdm_Running::getCommandResultData('archive'); 
                            require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-archive.php';
                            $to_file = $this->dir . '/' . $this->name;
                            $processing_archive = 0;
                            foreach($files2 as $key => $files) {
                                if (!WPAdm_Running::toEndWork( time()) ) {
                                    // 2 steps of archive less 3MB 
                                    if ($size_part[$key] > 3145728 || $processing_archive >= 2) { // ~ 3 MB
                                        return true;
                                    }
                                    $processing_archive ++;
                                }
                                $md5 = md5( print_r( $files, 1 ) );
                                if ( !isset($files_archive[$md5]) ) {
                                    if ( WPAdm_Running::is_stop() ) {
                                        $af1 = $this->getArchiveName($to_file);
                                        $archive = new WPAdm_Archive($af1, $to_file . '.md5');
                                        $archive->setRemovePath(ABSPATH);
                                        if ( !file_exists( $af1 ) ) {
                                            WPAdm_Core::log( __('Create part ','dropbox-backup') . basename( $af1 ) );
                                        }
                                    }
                                    $targz = WPAdm_Running::getCommandResultData('test_targz_archive');

                                    if ( ( !empty($targz) && $targz === true ) || ( file_exists($af1) && filesize($af1) > 900000 ) ) {
                                        if ( WPAdm_Running::is_stop() ) {
                                            $af2 = $this->getNextArchiveName($to_file);
                                            if ($af1 != $af2) {
                                                unset($archive);
                                                if ( !file_exists( $af2 ) ) {
                                                    WPAdm_Core::log(__('Create part ','dropbox-backup') . basename( $af2 ) );
                                                }
                                                $archive = new WPAdm_Archive($af2, $to_file . '.md5');
                                                $archive->setRemovePath( ABSPATH );
                                            }
                                        } else {
                                            return true;
                                        }
                                    }

                                    if ( WPAdm_Running::is_stop() ) {
                                        $md5 = md5( print_r( $files, 1 ) );
                                        if ( defined('PCLZIP_SEPARATOR') ) {
                                            $files_str = implode( PCLZIP_SEPARATOR , $files);
                                        } else {
                                            $files_str = implode( ',' , $files);
                                        }
                                        $files_archive = WPAdm_Running::getCommandResultData('archive');
                                        if ( WPAdm_Running::is_stop() ) {
                                            if ( !isset($files_archive[$md5]) ) {
                                                if ( WPAdm_Running::is_stop() ) {
                                                    $res = $archive->add($files_str);
                                                    if ($res) {
                                                        $files_archive = WPAdm_Running::getCommandResultData('archive');
                                                        $files_archive[$md5] = 1;
                                                        WPAdm_Running::setCommandResultData('archive', $files_archive);
                                                        WPAdm_Process::setInc( 'archiving', count($files) );
                                                    } else {
                                                        if ($archive->anew) {
                                                            return true;
                                                        } else {
                                                            $this->result->setError( $archive->error );
                                                            $this->result->setResult( WPAdm_Result::WPADM_RESULT_ERROR );
                                                            return $this->result;
                                                        }
                                                    }
                                                } else {
                                                    return true;
                                                }
                                            }
                                        } else {
                                            return true;
                                        }
                                    } 
                                }
                            }

                            WPAdm_Core::log( __('Backup of files was finished','dropbox-backup') );
                            WPAdm_Running::setCommandResult('archive', true); 

                        }
                        if (empty($errors)) {
                            if ( WPAdm_Running::is_stop() ) {
                                $files = glob($this->dir . '/'.$this->name . '*');
                                $urls = array();
                                $totalSize = 0;
                                foreach($files as $file) {
                                    $urls[] = str_replace(ABSPATH, '', $file);
                                    $totalSize += @intval( filesize($file) );
                                }
                                $this->result->setData($urls);
                                $this->result->setSize($totalSize);
                                $this->result->setValue('md5_data', md5 ( print_r($this->result->toArray(), 1 ) ) );
                                $this->result->setValue('name', $this->name );
                                $this->result->setValue('time', $this->time);
                                $this->result->setValue('type', 'local');
                                $this->result->setValue('counts', count($urls) );
                                $size = WPADM_getSize( $totalSize ); /// MByte
                                $log = str_replace("%s", $size , __('Backup size %s','dropbox-backup') ) ;
                                WPAdm_Core::log($log);

                                $remove_from_server = 0;
                                #Removing TMP-files
                                WPAdm_Core::rmdir($mysql_dump_file);

                                #Removind old backups(if limit the number of stored backups)
                                if ($this->params['limit'] != 0) {
                                    WPAdm_Core::log( __('Limits of Backups ','dropbox-backup') . $this->params['limit'] ); 
                                    WPAdm_Core::log( __('Removing of old Backups was started','dropbox-backup') );
                                    $files = glob(DROPBOX_BACKUP_DIR_BACKUP . '/*');
                                    if (count($files) > $this->params['limit']) {
                                        $files2 = array();
                                        foreach($files as $f) {
                                            $fa = explode('-', $f);
                                            if (count($fa) != 3) {
                                                continue;
                                            }
                                            $files2[$fa[2]] = $f;

                                        }
                                        ksort($files2);
                                        $d = count($files2) - $this->params['limit'];
                                        $del = array_slice($files2, 0, $d);
                                        foreach($del as $d) {
                                            WPAdm_Core::rmdir($d);
                                        }
                                    }
                                    WPAdm_Core::log( __('Removing of old Backups was Finished','dropbox-backup') ); 
                                }
                            } else {
                                return true;
                            }
                        }
                    } else {
                        return true;
                    }
                }
                if ( WPAdm_Running::is_stop() ) {
                    wpadm_class::setBackup(1);
                    if (!empty($errors)) {
                        $this->result->setError(implode("\n", $errors));
                        $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
                        WPAdm_Core::rmdir($this->dir);
                        wpadm_class::setStatus(0);
                        wpadm_class::setErrors( implode(", ", $errors) );
                    } else {
                        wpadm_class::setStatus(1);
                        WPAdm_Core::log( __('Backup creation was complete successfully!','dropbox-backup') );
                    }
                    wpadm_class::backupSend();
                } else {
                    return true;
                }
                return $this->result;
            }

        }
        public function createListFilesForArchive() 
        {
            $inludes = get_option(PREFIX_BACKUP_ . "plus-path");
            if($inludes !== false) {        
                $f = explode(',', base64_decode( $inludes ) );
                $files = array();
                $n = count($f);
                $tmp_folder = '';
                for($i = 0; $i < $n; $i++) {
                    if(stripos($f[$i], 'wpadm_backups') !== false || stripos($f[$i], 'Dropbox_Backup') !== false) {
                        continue;
                    }
                    if (!empty($tmp_folder) && strpos($f[$i], $tmp_folder) === false) {
                        $fi = $this->directoryToArray(ABSPATH . $tmp_folder, true );
                        $files = array_merge($files, $fi);
                        $tmp_folder = '';
                    } elseif(!empty($tmp_folder) && strpos($f[$i], $tmp_folder) !== false) {
                        $tmp_folder = '';
                    } 

                    if( is_dir( ABSPATH . $f[$i] ) ) {
                        $fi = $this->directoryToArray(ABSPATH . $f[$i], true );
                        $tmp_folder = $f[$i];
                        $files = array_merge($files, $fi); 
                    } elseif (file_exists(ABSPATH . $f[$i])) {
                        $files[$i] = ABSPATH . $f[$i];
                    }
                    
                }
            } else {
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
                // ABSPATH . 'wp-config-sample.php',
                ABSPATH . 'wp-cron.php',
                ABSPATH . 'wp-links-opml.php',
                ABSPATH . 'wp-load.php',
                ABSPATH . 'wp-login.php',
                ABSPATH . 'wp-mail.php',
                ABSPATH . 'wp-settings.php',
                //ABSPATH . 'wp-signup.php',
                ABSPATH . 'wp-trackback.php',
                ABSPATH . 'xmlrpc.php',
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
                if ( file_exists(ABSPATH . 'wp-signup.php') ) {
                    $files = array_merge( $files, array( ABSPATH . 'wp-signup.php' ) );
                }

                // check files in root directory
                $n = count($files);
                for($i = 0; $i < $n; $i++) {
                    if (!file_exists($files[$i])) {
                        unset($files[$i]);
                    }
                }
                $files = array_values($files);

                if (!empty($this->params['minus-path'])) {
                    $minus_path = explode(",", $this->params['minus-path']);
                    foreach($files as $k => $v) {
                        $v = str_replace(ABSPATH  , '',  $v);
                        if (in_array($v, $minus_path)) {
                            unset($files[$k]);
                            WPAdm_Core::log( __('Skip of File ','dropbox-backup') . $v);
                        }
                    }
                }

                $folders = array_merge(
                $folders,
                array(
                ABSPATH . 'wp-admin',
                ABSPATH . 'wp-content',
                ABSPATH . 'wp-includes',
                )
                );
                if (!empty($this->params['plus-path'])) {
                    $plus_path = explode(",", $this->params['plus-path']);
                    foreach($plus_path as $p) {
                        if (empty($p)) {
                            continue;
                        }
                        $p = ABSPATH . $p;
                        if (file_exists($p)) {
                            if (is_dir($p)) {
                                $folders[] = $p;
                            } else{
                                $files[] = $p;
                            }
                        }
                    }
                }

                $folders = array_unique($folders);
                $files = array_unique($files);

                foreach($folders as $folder) {
                    if (!is_dir($folder)) {
                        continue;
                    }
                    $files = array_merge($files, $this->directoryToArray($folder, true));
                }
            }

            $files = array_values( array_unique($files) );

            return $files;
        }

        public function tryToUtf8( $string, $strip = false ) 
        {
            $string = (string) $string;

            if ( 0 === strlen( $string ) ) {
                return '';
            }
            // preg match invalid UTF8 
            if ( 1 === @preg_match( '/^./us', $string ) ) {
                return $string;
            }

            if ( $strip && function_exists( 'iconv' ) ) {
                return iconv( 'utf-8', 'utf-8', $string );
            }

            return $string;
        }

        private function directoryToArray($directory, $recursive) {
            $array_items = array();

            $d = str_replace(ABSPATH, '', $directory);
            // Skip dirs 
            if (isset($this->params['minus-path'])) {
                $minus_path = explode(",", $this->params['minus-path']);
                if (in_array($d, $minus_path) ) {
                    WPAdm_Core::log(__('Skip of Folder ','dropbox-backup') . $directory);
                    return array();
                }
            } else {
                $minus_path = array();
            }

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

            if (stripos($d2, 'cache') !== false ) {
                WPAdm_Core::log( __('Skip of Cache-Folder ','dropbox-backup') . $directory);
                return array();
            } 

            if (strpos($d2, 'cache') !== false && isset($tmp[0]) && !in_array($tmp[0], array('plugins', 'themes')) ) {
                WPAdm_Core::log(__('Skip of Cache-Folder ','dropbox-backup') . $directory);
                return array();
            }

            if (!empty($d2) && isset( $base_dir ) && BackupsFoldersExclude( basename( $d2 ), $base_dir ) ) {
                WPAdm_Core::log( sprintf(  __('Skip backup folder of another backup plugin "%s" ','dropbox-backup'), basename( $d2 ) ) );
                return array();
            }
            if(stripos($directory, 'wpadm_backups') !== false || stripos($directory, 'Dropbox_Backup') !== false) {
                return array();
            }

            if (stripos($d2, 'backup') !== false ) {
                WPAdm_Core::log( __('Skip of Backup-Folder ','dropbox-backup') . $directory);
                return array();
            }

            if ($handle = opendir($directory)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != ".." && $file != 'tmp') {
                        if (is_dir($directory. "/" . $file) ) {
                            if($recursive) {
                                $array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
                            }

                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH, '', $ff);
                                // skip "minus" dirs
                                if (!in_array($f, $minus_path)) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log(__('Skip of File ','dropbox-backup') . $ff);
                                }
                            }
                        } else {
                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH, '', $ff);
                                // skip "minus" dirs
                                if (!in_array($f, $minus_path)) {
                                    $array_items[] = $this->tryToUtf8( $ff );
                                } else {
                                    WPAdm_Core::log( __('Skip of Folder ','dropbox-backup') . $ff);
                                }
                            }
                        }
                    }
                }
                closedir($handle);
            }
            return $array_items;
        }


        /*
        * returns the elements of access to MySQL from WP options
        * return Array()
        */
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
            return $db_params;
        }


        private function init(array $conf) {
            $this->id = $conf['id'];
            $this->stime = $conf['stime'];
            //$this->queue = new WPAdm_Queue($this->id);
        }

        private function getArchiveName($name)
        {
            //WPAdm_Core::log("{$name}-*.zip");
            $targz = WPAdm_Running::getCommandResultData('test_targz_archive');
            if (!empty($targz) && $targz === true ) {
                $archives = glob("{$name}-*.tar.gz");
            } else {
                $archives = glob("{$name}-*.zip");
            }

            if (empty($archives)) {
                if (!empty($targz) && $targz === true ) {
                    return "{$name}-1.tar.gz";
                } else {
                    return "{$name}-1.zip";
                }
            }
            $n = count($archives);
            if (!empty($targz) && $targz === true ) {
                $f = "{$name}-{$n}.tar.gz";
            } else {
                $f = "{$name}-{$n}.zip";
            }
            return $f;
        }

        private function getNextArchiveName($name)
        {
            $targz = WPAdm_Running::getCommandResultData('test_targz_archive');
            if (!empty($targz) && $targz === true ) {
                $archives = glob("{$name}-*.tar.gz");
            } else {
                $archives = glob("{$name}-*.zip");
            }
            $n = 1 + count($archives);
            if (!empty($targz) && $targz === true ) {
                $a = "{$name}-{$n}.tar.gz";
            } else {
                $a = "{$name}-{$n}.zip";
            }
            //WPAdm_Core::log($a);
            return $a;
        }
    }
}
