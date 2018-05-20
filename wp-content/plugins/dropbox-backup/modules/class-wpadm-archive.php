<?php
if (!defined('PCLZIP_TEMPORARY_DIR')) {
    define('PCLZIP_TEMPORARY_DIR', WPAdm_Core::getTmpDir() . '/');
}

if (!defined('PCLZIP_SEPARATOR')) {
    define('PCLZIP_SEPARATOR', '<|>');
}
if ( !class_exists("PclZip") ) {
    require_once dirname(__FILE__) . '/pclzip.lib.php';
}
if (!class_exists('WPAdm_Archive')) {
    class WPAdm_Archive {
        private $remove_path = '';
        private $files = array();
        public $file_zip = '';
        private $type_backup = array();
        /**
        * @var PclZip
        */
        private $archive;
        private $md5_file = '';
        public $error = '';
        
        public $anew = false;


        private $method = '';

        public function __construct($file, $md5_file = '') { 
            if (class_exists('wpadm_wp_full_backup_dropbox')) {
                $this->type_backup = wpadm_wp_full_backup_dropbox::getTypeBackup();
            } 
            $this->file_zip = $file;
            $this->archive = new PclZip($file);      
            $this->files[] = $file;
            $this->md5_file = $md5_file;
        }

        public function zipArhive($file_to_arhive = array())
        {
            if ( isset( $this->type_backup['zip_archive'] ) && $this->type_backup['zip_archive'] == 1 && !empty($file_to_arhive) ) {
                $command = $this->getCommandToArchive('zip_archive', $file_to_arhive);
                if (!empty($command)) {
                    $command_return = array();
                    $result_command = @exec($command, $command_return);

                    $res = $this->parseResultZip($command_return);

                    if ($res['add'] == count($file_to_arhive)) {
                        $files = implode(PCLZIP_SEPARATOR, $file_to_arhive);
                        $this->saveMd5($files);
                        return true;
                    } 
                    if ( file_exists( $this->file_zip ) && $res['error'] === 0 ) {
                        $files = implode(PCLZIP_SEPARATOR, $file_to_arhive);
                        $this->saveMd5($files);
                        return true;
                    }
                }
            }
            return false;
        }

        public function targzArchive( $file_to_arhive = array() )
        {
            if ( isset( $this->type_backup['targz_archive'] ) && $this->type_backup['targz_archive'] == 1 && !empty($file_to_arhive) ) {

                if ( $this->tarGzCommandArhive($file_to_arhive) ) {
                    return true;
                }
                if ( !function_exists( 'gzencode' ) ) {
                    $this->error = __( 'Functions for gz compression not available', 'dropbox-backup' );
                    return false;
                }

                if ( strpos($this->file_zip, '.zip') !== false ) {
                    $this->file_zip = str_replace('.zip', '.tar.gz', $this->file_zip);
                }
                $this->method = 'targz';

                $this->archive = fopen( $this->file_zip, 'ab' );

                $n = count($file_to_arhive);  
                for($i = 0; $i < $n; $i++) {
                    $this->addToTargz($file_to_arhive[$i], '');
                    $this->saveMd5( $file_to_arhive[$i] );
                }
                $this->close();
                /*  include_once  dirname(__FILE__) . '/archive.php'; 
                $gz = new wpadm_gzip_file($this->file_zip);
                $gz->set_options( array('basedir' => ABSPATH, 'delete_path_in_archive' => $this->remove_path ) );
                $gz->add_files( $file_to_arhive );
                $gz->create_archive();
                if (!empty( $gz->error ) ) {
                $this->error = implode(" ", $gz->error );
                WPAdm_Core::log( $this->error );
                return false;
                }
                $this->saveMd5( implode( PCLZIP_SEPARATOR, $file_to_arhive) );
                */

                /*if ( strpos($this->file_zip, '.tar.gz') !== false ) {
                $this->file_zip = str_replace('.tar.gz', '.zip', $this->file_zip);
                } */

                if ( file_exists($this->file_zip) ) {
                    return true;
                }
            }
            return false;
        }

        private function addToTargz($file, $file_in)
        {
            $file = str_replace('\\', '/', $file);
            if ( empty( $file_in ) ) {
                $serach  = str_replace('\\', '/', ABSPATH);
                $file_in = str_replace($serach, '', $file);
            }

            $file_in = str_replace( array( "?", "<", ">", ":", "%","\"", "*", "|", chr(0) ) , '', $file_in );

            if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
                clearstatcache(true, $file);
            }

            if ( ! is_readable( $file ) ) {
                $this->error =  sprintf( __( 'File %s is not readable or does not exist', 'dropbox-backup' ), $file );
                return false;
            }
            $this->add_tar_file( $file, $file_in );

        }

        private function add_tar_file($file, $file_in)
        {
            if ( ! $this->check_archive( $file ) ) {
                return false;
            }
            $chunk_size = 1024 * 1024 * 4;

            //Limit string of file name in tar archive
            if ( strlen( $file_in ) <= 100 ) {
                $filename        = $file_in;
                $filename_prefix = "";
            } else {
                $filename_offset = strlen( $file_in ) - 100;
                $split_pos       = strpos( $file_in, '/', $filename_offset );
                if ( $split_pos === FALSE ) {
                    $split_pos = strrpos( $file_in, '/' );
                }
                $filename        = substr( $file_in, $split_pos + 1 );
                $filename_prefix = substr( $file_in, 0, $split_pos );
                if ( strlen( $filename ) > 100 ) {
                    $filename = substr( $filename, -100 );
                    WPAdm_Core::log( sprintf( __( 'File name "%1$s" is too long to be saved correctly in archive!', 'dropbox-backup' ), $file_in ) );
                }
                if ( strlen( $filename_prefix ) > 155 ) {
                    WPAdm_Core::log( sprintf( __( 'File path "%1$s" is too long to be saved correctly in archive!', 'dropbox-backup' ), $file_in) );
                }
            }
            $file_stat = stat( $file );
            if ( ! $file_stat ) {
                return false;
            }
            $file_stat[ 'size' ] = abs( (int) $file_stat[ 'size' ] );
            //open file
            if ( $file_stat[ 'size' ] > 0 ) {
                if ( ! ( $fd = fopen( $file, 'rb' ) ) ) {
                    $this->error = sprintf( __( 'Cannot open source file %s for archiving', 'dropbox-backup' ), $file );
                    return false;
                }
            }
            $fileowner = __( "Unknown", "dropbox-backup" );
            $filegroup = __( "Unknown", "dropbox-backup" );
            if ( function_exists( 'posix_getpwuid' ) ) {
                $info      = posix_getpwuid( $file_stat[ 'uid' ] );
                $fileowner = $info[ 'name' ];
                $info      = posix_getgrgid( $file_stat[ 'gid' ] );
                $filegroup = $info[ 'name' ];
            }
            // Generate the TAR header for this file
            $chunk = pack( "a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
            $filename, //name of file  100
            sprintf( "%07o", $file_stat[ 'mode' ] ), //file mode  8
            sprintf( "%07o", $file_stat[ 'uid' ] ), //owner user ID  8
            sprintf( "%07o", $file_stat[ 'gid' ] ), //owner group ID  8
            sprintf( "%011o", $file_stat[ 'size' ] ), //length of file in bytes  12
            sprintf( "%011o", $file_stat[ 'mtime' ] ), //modify time of file  12
            "        ", //checksum for header  8
            0, //type of file  0 or null = File, 5=Dir
            "", //name of linked file  100
            "ustar", //USTAR indicator  6
            "00", //USTAR version  2
            $fileowner, //owner user name 32
            $filegroup, //owner group name 32
            "", //device major number 8
            "", //device minor number 8
            $filename_prefix, //prefix for file name 155
            "" ); //fill block 12

            $checksum = 0;
            for ( $i = 0; $i < 512; $i ++ ) {
                $checksum += ord( substr( $chunk, $i, 1 ) );
            }

            $checksum = pack( "a8", sprintf( "%07o", $checksum ) );
            $chunk    = substr_replace( $chunk, $checksum, 148, 8 );

            if ( isset( $fd ) && is_resource( $fd ) ) {
                // read/write files in 512 bite Blocks
                while ( ( $content = fread( $fd, 512 ) ) != '' ) {
                    $chunk .= pack( "a512", $content );
                    if ( strlen( $chunk ) >= $chunk_size ) {
                        if ( WPAdm_Running::is_stop() ) {
                            if ( $this->method == 'targz' ) {
                                $chunk = gzencode( $chunk );
                            }
                            if ( $this->method == 'tarbz2' ) {
                                $chunk = bzcompress( $chunk );
                            }
                            fwrite( $this->archive, $chunk );
                            $chunk = '';
                        }
                    }
                }
                fclose( $fd );
            }

            if ( ! empty( $chunk ) ) {
                if ( $this->method == 'targz' ) {
                    $chunk = gzencode( $chunk );
                }
                if ( $this->method == 'tarbz2' ) {
                    $chunk = bzcompress( $chunk );
                }
                fwrite( $this->archive, $chunk );
            }

            return true;
        }

        private function check_archive( $file = '' ) {

            $file_size = 0;
            if ( ! empty( $file ) ) {
                $file_size = filesize( $file );
                if ( $file_size === FALSE ) {
                    $file_size = 0;
                }
            }

            if ( is_resource( $this->archive ) ) {
                $info_archive = fstat( $this->archive );
                $archive_size = $info_archive[ 'size' ];
            } else {
                $archive_size = filesize( $this->file_zip );
                if ( $archive_size === FALSE ) {
                    $archive_size = PHP_INT_MAX;
                }
            }

            $archive_size = $archive_size + $file_size;
            if ( $archive_size >= PHP_INT_MAX ) {
                $this->error = sprintf( __( 'If %s will be added to your backup archive, the archive will be too large for operations with this PHP Version. You might want to consider splitting the backup job in multiple jobs with less files each.', 'dropbox-backup' ), $file_to_add );
                return false;
            }

            return true;
        }

        public function close()
        {
            if ($this->method == 'targz') {
                $end = pack( "a1024", "" );
                if ( $this->method === 'targz' ) {
                    $end = gzencode( $end );
                }
                if ( $this->method === 'tarbz2' ) {
                    $end = bzcompress( $end );
                }
                fwrite( $this->archive, $end );
            }
        }

        public function parseResultZip($command_return)
        {
            $add = 0;
            $error = 0;
            if (!empty( $command_return) ) {
                $n = count($command_return);
                for($i = 0; $i < $n; $i++) {
                    if (strpos($command_return[$i], 'add') !== false || strpos($command_return[$i], 'updating') !== false) {
                        $add ++;
                    } elseif (strpos($command_return[$i], 'error') !== false || strpos($command_return[$i], 'warning') !== false ) {
                        $error++;
                        $this->error .= " " . $command_return[$i];
                    }
                }
            }
            return array( 'add' => $add, 'error' => $error );

        }

        public function tarGzCommandArhive($file_to_arhive = array())
        {

            $command = $this->getCommandToArchive('tar_archive', $file_to_arhive);
            if (!empty($command)) {
                $command_return = array();
                $result_command = @exec ($command, $command_return);
                if (count($file_to_arhive) == count($command_return)) {
                    $files = implode(PCLZIP_SEPARATOR, $file_to_arhive);
                    $this->saveMd5($files);
                    return true;
                }
                if (count($command_return) > 0) {
                    $files = implode(PCLZIP_SEPARATOR, $file_to_arhive);
                    $this->saveMd5($files);
                    return true;
                }
                if (file_exists($this->file_zip)) {
                    $files = implode(PCLZIP_SEPARATOR, $file_to_arhive);
                    $this->saveMd5($files);
                    return true;
                }
                $this->error = "Files not Adding to arhive"; 
                if ( strpos($this->file_zip, '.tar.gz') !== false ) {
                    $this->file_zip = str_replace('.tar.gz', '.zip', $this->file_zip);
                }
            }
            return false;
        }

        public function getCommandToArchive($type = 'zip_archive', $files = array() )
        {
            $return = '';
            $remove_dir = '';
            switch($type) {
                case 'zip_archive':
                    if (!empty( $this->remove_path ) ) {
                        $remove_dir = 'cd ' . $this->remove_path . ' &&';
                        $files_str = '"' . implode('" "', $files) . '"';
                        $files_str = str_replace($this->remove_path, './', $files_str);
                        $zip = str_replace($this->remove_path, './', $this->file_zip);
                    } else {
                        $files_str = '"' . implode('" "', $files) . '"';
                        $zip = $this->file_zip;
                    }

                    $return .= trim( "$remove_dir zip {$zip} " . $files_str );
                    break;
                case 'tar_archive':
                    if ( strpos($this->file_zip, '.zip') !== false ) {
                        $this->file_zip = str_replace('.zip', '.tar.gz', $this->file_zip);
                    }

                    if (!empty( $this->remove_path) ) {
                        $remove_dir = '-C ' . $this->remove_path . ' ';
                    } 
                    $files_str = '"' . implode('" "', $files) . '"';
                    $files_str = str_replace($this->remove_path, './', $files_str);
                    $u = 'c';
                    if (file_exists($this->file_zip)) {
                        $u = 'r';
                    }
                    $return = trim( "tar  -{$u}zvf {$this->file_zip} " . $remove_dir . $files_str );
                    break;
            }
            return $return;

        }

        public function clearBackupDirectory($type = '')
        {
            if (!empty( $type ) ) {
                $dir = substr($this->file_zip, 0, strlen($this->file_zip) - strlen( basename( $this->file_zip ) ) ); 
                $open_dir = opendir( $dir );
                if ($open_dir) {
                    while($d = readdir($open_dir)) {
                        if ($d != '.' && $d != '..') {
                            if ( substr($d, $type) !== false ) {
                                @unlink($dir . '/' . $d);
                            }
                        }
                    }
                }
            }
        }

        public function add($file) 
        {                    
            return $this->packed($file);    
        }
        public function packed($file)
        {
            @ini_set("memory_limit", "256M");
            if ( WPAdm_Running::is_stop() ) {
                $files = explode(PCLZIP_SEPARATOR, $file);
                $n = count($files);       
                $this->setToLogArhive( __('Add to archive: ', 'dropbox-backup') . $this->file_zip );
                for($i = 0; $i < $n; $i++) {

                    $this->setToLogArhive(__("Add File: ", 'dropbox-backup' ) . $files[$i] . ' [' . WPADM_getSize( filesize($files[$i]) ) . ']' . '[' . wpadm_class::perm($files[$i]) . ']' );
                }
                $file = implode(PCLZIP_SEPARATOR, $files);
                $command_targz_test = WPAdm_Running::getCommandResultData('test_targz_archive');
                if( !empty($command_targz_test) && $command_targz_test === true ) {
                    if ( WPAdm_Running::is_stop() ) {
                        WPAdm_Core::log(__('Trying to add files to archive using Tar shell or tar class', 'dropbox-backup') );
                        $tarGz = $this->targzArchive($files); // .tar.gz archive
                        if ($tarGz) {
                            WPAdm_Core::log(__('Trying to add files to archive using Tar shell or tar class was successful', 'dropbox-backup') );
                            return true;
                        }
                        $this->anew = true;
                        $this->clearBackupDirectory('.tar.gz');
                        $this->clearBackupDirectory('.md5');
                        $this->error = '';
                        WPAdm_Core::log(__('Add files to archive using Tar shell or tar class wasn\'t successful', 'dropbox-backup') );
                        WPAdm_Running::setCommandResultData('test_targz_archive', false);
                        return false;
                    }
                } 
                $command_zip_test = WPAdm_Running::getCommandResultData('test_zip_archive');
                if ( !empty($command_zip_test) && $command_zip_test === true ) {
                    if ( WPAdm_Running::is_stop() ) {
                        WPAdm_Core::log(__('Trying to add files to archive using Zip shell', 'dropbox-backup') );
                        $zip_shell = $this->zipArhive($files); // command zip
                        if ($zip_shell) {
                            WPAdm_Core::log(__('Add files to archive using Zip shell was successful', 'dropbox-backup') );
                            return true;
                        }
                        $this->anew = true;
                        WPAdm_Core::log(__('Add files to archive using Zip shell wasn\'t successful', 'dropbox-backup') );
                        WPAdm_Running::setCommandResultData('test_zip_archive', false);
                        return false;
                    }
                }
                if (empty($this->remove_path)) {
                    if ( WPAdm_Running::is_stop() ) {
                        $res = $this->archive->add($file);
                    }
                } else {
                    if ( WPAdm_Running::is_stop() ) {
                        $res = $this->archive->add($file, PCLZIP_OPT_REMOVE_PATH, $this->remove_path);
                    }
                }    
                if ( WPAdm_Running::is_stop() ) {
                    if ($res == 0) {
                        $this->checkError($file);
                        WPAdm_Core::log( $this->archive->errorInfo(true) ); 
                        if (file_exists($this->md5_file)) {
                            unset($this->md5_file);
                        }
                        $this->error = $this->archive->errorInfo(true);
                        return false;
                    } 
                    $this->saveMd5($file);
                }
            }
            return true;
        }

        protected function checkError($file)
        {         
            $count = WPAdm_Running::getCommandResultData('count_error_zip'); 
            if ( empty($count) || $count == 0 ) {
                if ( $this->archive->errorCode() == -10 ) { // Unable to find End of Central Dir Record signature
                    WPAdm_Core::rmdir($this->file_zip);
                    WPAdm_Running::getCommandResultData('count_error_zip_signature', 1); 
                    $this->packed($file);
                    return true;
                }
            }
            return false;
        }

        protected function saveMd5($file) {
            if ($this->md5_file) {
                $files = explode(PCLZIP_SEPARATOR, $file); {
                    foreach($files as $f) {
                        file_put_contents($this->md5_file, $f . "\t" . @md5_file($f) . "\t" . basename($this->file_zip) . "\n", FILE_APPEND);
                    }
                }
            }
        }

        public function setRemovePath($remove_path) {
            $this->remove_path = $remove_path;
        }

        public function setToLogArhive($msg) 
        {
            $file_log = WPADM_Core::getTmpDir() . '/log-archive.log';
            file_put_contents( $file_log, date("Y-m-d H:i:s") . "\t{$msg}\n", FILE_APPEND );
        }
    }
}