<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Class WPAdm_Сommand
*/
if (!class_exists('WPAdm_Process')) {

    class WPAdm_Process {

        public static $processes = array('repair', 'optimization', 'mysqldump', 'archiving', 'dropbox');

        private static $file_name = 'processes.data';

        private static $file = '';

        private static $data = array();

        public static function includeCore()
        {
            if (!class_exists('WPAdm_Core')) {
                include_once 'class-wpadm-core.php';
                WPAdm_Core::$pl_dir = DRBBACKUP_BASE_DIR;
            }
            self::$file = WPAdm_Core::getTmpDir() . '/' . self::$file_name;
        }

        public static function init($process, $all)
        {
            self::includeCore();

            if (file_exists(self::$file)) {
                self::$data = wpadm_unpack( file_get_contents( self::$file ) );
            }
            self::$data[$process]['all'] = $all;
            file_put_contents(self::$file, wpadm_pack( self::$data ) );
        }



        public static function clear()
        {
            self::includeCore();
            if ( file_exists( self::$file ) ) {
                unlink(self::$file);
            }
        }

        public static function set($process, $count = 0)
        {
            self::includeCore();
            if (file_exists(self::$file)) {
                self::$data = wpadm_unpack( file_get_contents( self::$file ) ); 
            }
            self::$data[$process]['count'] = $count;
            file_put_contents(self::$file, wpadm_pack( self::$data ) );
        }
        
        public static function setInc($process, $count = 0)
        {
            self::includeCore();
            if (file_exists(self::$file)) {
                self::$data = wpadm_unpack( file_get_contents( self::$file ) ); 
            }
            if (isset(self::$data[$process]['count'])) {
                self::set($process, self::$data[$process]['count'] + $count);
            } else {
                self::set($process, $count);
            }
        }

        public static function get($process)
        {
            self::includeCore();
            if (file_exists(self::$file)) {
                self::$data = wpadm_unpack( file_get_contents( self::$file ) ); 
            }
            $count = $procent = $all = 0;
            if ( isset( self::$data[$process]['all'] ) && self::$data[$process]['all'] > 0 ) {
                $all = self::$data[$process]['all'];
                $count = isset(self::$data[$process]['count']) ? self::$data[$process]['count'] : 0;
                $procent = round( ( ( $count / self::$data[$process]['all'] ) * 100 ) ) ;
            }
            return array('all' => $all, 'count' => $count, 'procent' => $procent);
        }

        public static function getAll()
        {
            $result = array();
            $n = count(self::$processes);
            for($i = 0; $i < $n; $i++) {
                $result[self::$processes[$i]] = self::get( self::$processes[$i] );
            }
            return $result;
        }

    }

}