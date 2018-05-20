<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
* Бэкап сайта
* Class WPadm_Method_Backup
*/
if (!class_exists('WPadm_Method_Backup')) {
    class WPadm_Method_Backup extends WPAdm_Method_Class {
        /**
        * Уникальный идентификатор текущего объекта
        * @var String
        */
        private $id;

        /**
        * Unixtimestamp, когда был запущен метод
        * @var Int
        */
        private $stime;

        /**
        * @var WPAdm_Queue
        */
        private $queue;

        /**
        * @var string
        */
        private $dir;

        /**
        * @var string
        */
        private $tmp_dir;

        /**
        * Тип бэкапа
        * @var string [full|db]
        */
        private $type = 'full';

        private $name = '';

        public function __construct($params) {
            parent::__construct($params);
            $this->init(
            array(
            'id' => uniqid('wpadm_method_backup__'),
            'stime' => time(),
            'type' => $params['type'],
            )
            );

            $name = get_option('siteurl');

            $name = str_replace("http://", '', $name);
            $name = str_replace("https://", '', $name);
            $name = preg_replace("|\W|", "_", $name);
            $name = str_ireplace( array( 'Ä',  'ä',  'Ö',  'ö', 'ß',  'Ü',  'ü', 'å'), 
            array('ae', 'ae', 'oe', 'oe', 's', 'ue', 'ue', 'a'), 
            $name );
            $name .= '-' . $this->type . '-' . date("Y_m_d_H_i");
            $this->name = $name;

            // папка для бэкапа
            $this->dir = DROPBOX_BACKUP_DIR_BACKUP . '/' . $this->name;
            $error = WPAdm_Core::mkdir(DROPBOX_BACKUP_DIR_BACKUP);
            if (!empty($error)) {
                $this->result->setError($error);
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            }
            $error = WPAdm_Core::mkdir($this->dir);
            if (!empty($error)) {
                $this->result->setError($error);
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            }
        }

        public function getResult()
        {
            $errors = array();

            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
            $this->result->setError('');

            @unlink(dirname(__FILE__) . '/../tmp/log.log');

            WPAdm_Core::log('Start backup create');
            WPAdm_Core::log('Create dump Data Base');

            $mysql_dump_file = DROPBOX_BACKUP_DIR_BACKUP . '/mysqldump.sql';
            if (file_exists($mysql_dump_file)) {
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

            if (isset($this->params['optimize']) && ($this->params['optimize']==1)) {
                WPAdm_Core::log(__('Optimize Database Tables','dropbox-backup'));
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


            WPAdm_Core::log('Start Created List Files');
            if ($this->type == 'full') {
                $files = $this->createListFilesForArchive();
            } else {
                $files = array();
            }
            if (file_exists($mysql_dump_file) && filesize($mysql_dump_file) > 0) {
                $files[] = $mysql_dump_file;
            }

            if (empty($files)) {
                $errors[] = 'Empty list files';
            }

            $files2 = array();
            $files2[0] = array();
            $i = 0;
            $size = 0;
            foreach($files as $f) {
                if ($size > 170000) {//~170kbyte
                    $i ++;
                    $size = 0;
                    $files2[$i] = array();
                }
                $f_size =(int)filesize($f);
                if ($f_size == 0 || $f_size > 1000000) {
                    WPAdm_Core::log('file '. $f .' size ' . $f_size);
                }
                $size += $f_size;
                $files2[$i][] = $f;
            }

            WPAdm_Core::log('Сreated List Files is successfully');
            //$this->queue->clear();
            WPAdm_Core::log('Start archived files');
            if (!class_exists('WPAdm_Archive')) {
                require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-archive.php';
            }
            foreach($files2 as $files) {
                $af = $this->getArchiveName($to_file);
                $archive = new WPAdm_Archive($af, $to_file . '.md5');
                $archive->setRemovePath(ABSPATH);
                if ( !file_exists( $af ) ) {
                    WPAdm_Core::log(__('Create part ','dropbox-backup') . basename( $af ) );
                }

                if (file_exists($af) && filesize($af) > 900000) {
                    $af = $this->getNextArchiveName($to_file);
                    unset($archive);
                    if ( !file_exists( $af ) ) {
                        WPAdm_Core::log(__('Create part ','dropbox-backup') . basename( $af ) );
                    }
                    $archive = new WPAdm_Archive($af, $to_file . '.md5');
                    $archive->setRemovePath( ABSPATH );
                }
                $files_str = implode(',', $files);
                $res = $archive->add($files_str);
                if ($res) {

                } else {
                    $this->result->setError( $archive->error );
                    $this->result->setResult( WPAdm_Result::WPADM_RESULT_ERROR );
                    return $this->result;
                }
            }
            WPAdm_Core::log('End archived files');

            $files = glob($this->dir . '/'.$this->name . '*');
            $urls = array();
            foreach($files as $file) {
                $urls[] = str_replace(ABSPATH, '', $file);
            }
            $this->result->setData($urls);

            WPAdm_Core::rmdir(DROPBOX_BACKUP_DIR_BACKUP . '/mysqldump.sql');

            if ($this->params['limit'] != 0) {
                WPAdm_Core::log('Start deleted old backup');
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
                WPAdm_Core::log('Finish deleted old backups');
            }
            WPAdm_Core::log('Finish create');

            if (!empty($errors)) {
                $this->result->setError(implode("\n", $errors));
                $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            }

            return $this->result;


        }

        private function getArchiveName($name)
        {
            //WPAdm_Core::log("{$name}-*.zip");
            $archives = glob("{$name}-*.zip");
            //WPAdm_Core::log( print_r($archives, 1) );
            if (empty($archives)) {
                return "{$name}-1.zip";
            }
            $n = count($archives);
            $f = "{$name}-{$n}.zip";
            return $f;
        }

        private function getNextArchiveName($name)
        {
            //WPAdm_Core::log("{$name}-*.zip");
            $archives = glob("{$name}-*.zip");
            $n = 1 + count($archives);
            $a = "{$name}-{$n}.zip";
            //WPAdm_Core::log($a);
            return $a;
        }



        public function createListFilesForArchive() {
            $folders = array();
            $files = array();

            $files = array_merge(
            $files,
            array(
            // ABSPATH .'/.htaccess',
            ABSPATH .'/index.php',
            //ABSPATH .'/license.txt',
            //ABSPATH .'/readme.html',
            ABSPATH .'/wp-activate.php',
            ABSPATH .'/wp-blog-header.php',
            ABSPATH .'/wp-comments-post.php',
            ABSPATH .'/wp-config.php',
            //ABSPATH .'/wp-config-sample.php',
            ABSPATH .'/wp-cron.php',
            ABSPATH .'/wp-links-opml.php',
            ABSPATH .'/wp-load.php',
            ABSPATH .'/wp-login.php',
            ABSPATH .'/wp-mail.php',
            ABSPATH .'/wp-settings.php',
            ABSPATH .'/wp-signup.php',
            ABSPATH .'/wp-trackback.php',
            ABSPATH .'/xmlrpc.php',
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
            if ( file_exists(ABSPATH . '.htaccess') ) {
                $files = array_merge( $files, array( ABSPATH . 'wp-config-sample.php' ) );
            }

            if (!empty($this->params['minus-path'])) {
                foreach($files as $k=>$v) {
                    $v = str_replace(ABSPATH .'/' , '',  $v);
                    if (in_array($v, $this->params['minus-path'])) {
                        unset($files[$k]);
                        WPAdm_Core::log('Skip of File ' . $v);
                    }
                }
            }

            $folders = array_merge(
            $folders,
            array(
            ABSPATH .'/wp-admin',
            ABSPATH .'/wp-content',
            ABSPATH .'/wp-includes',
            )
            );
            if (!empty($this->params['plus-path'])) {
                foreach($this->params['plus-path'] as $p) {
                    if (empty($p)) {
                        continue;
                    }
                    $p = ABSPATH .'/' . $p;
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
            return $files;
        }


        private function directoryToArray($directory, $recursive) {
            $array_items = array();

            $d = str_replace(ABSPATH . '/', '', $directory);
            // пропускаем ненужные директории

            if (
            in_array($d, $this->params['minus-path']) 
            ) {
                WPAdm_Core::log('Skip of Folder ' . $directory);
                return array();
            }

            $d = str_replace('\\', '/', $d);
            $tmp = explode('/', $d);
            if (function_exists('mb_strtolower')) {
                $d1 = mb_strtolower($tmp[0]);
            } else {
                $d1 = strtolower($tmp[0]);
            }
            
            unset($tmp[0]);
            if (function_exists('mb_strtolower')) {
                $d2 = mb_strtolower(implode('/', $tmp));
            } else {
                $d2 = strtolower(implode('/', $tmp));
            }
            //        if (strpos($d1, 'cache') !== false || ($d1 == 'wp-includes' && strpos($d2, 'cache') !== false)) {
            //        if (($d1 == 'wp-includes' && strpos($d2, 'cache') !== false)
            //           || ($d1 == 'wp-content' || !in_array($tmp[0], array('plugins', 'themes'))) 
            if (strpos($d2, 'cache') !== false
            && !in_array($tmp[0], array('plugins', 'themes')) 
            ) {
                WPAdm_Core::log('Skip of Cache-Folder ' . $directory);
                return array();
            }
            if(strpos($directory, 'wpadm_backups') !== false) {
                return array();
            }

            if ($handle = opendir($directory)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($directory. "/" . $file)) {
                            if($recursive) {
                                $array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
                            }

                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH . '/', '', $ff);
                                // Skip files
                                if (!in_array($f, $this->params['minus-path'])) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log('Skip of File ' . $ff);
                                }
                            }
                        } else {
                            $file = $directory . "/" . $file;
                            if (!is_dir($file)) {
                                $ff = preg_replace("/\/\//si", "/", $file);
                                $f = str_replace(ABSPATH . '/', '', $ff);
                                // skip folders
                                if (!in_array($f, $this->params['minus-path'])) {
                                    $array_items[] = $ff;
                                } else {
                                    WPAdm_Core::log('Skip of Folder ' . $ff);
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
        * get access to mysql from params WP
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
            //todo: norm
            $this->id = $conf['id'];
            $this->stime = $conf['stime'];
            //$this->queue = new WPAdm_Queue($this->id);
            $this->type = $conf['type'];
        }
    }
}