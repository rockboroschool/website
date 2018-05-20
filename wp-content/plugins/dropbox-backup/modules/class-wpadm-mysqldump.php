<?php


if ( ! defined( 'ABSPATH' ) ) exit;


if (!class_exists('WPAdm_Mysqldump')) {
    class WPAdm_Mysqldump {

        public $charset;
        public $collate;

        public $host = '';
        public $user = '';
        public $password = '';
        public $dbh = null ;
        public $rows = 200;

        private function connect($db = '') {
            //WPAdm_Core::log("----------------------------------------------------");
            //WPAdm_Core::log( __('Connecting to MySQL...' ,'dropbox-backup') );    
            if (! class_exists('wpdb')) {
                require_once ABSPATH . '/' . WPINC . '/wp-db.php';
            }
            if ($this->dbh === null) {
                global $wpdb;
                if (is_object($wpdb)) {
                    $this->dbh = $wpdb;
                } else {
                    $this->dbh = new wpdb( $this->user, $this->password, $db, $this->host );
                    $errors = $this->dbh->last_error;
                    if ($errors) {
                        $this->setError( __('MySQL Connect failed: ' ,'dropbox-backup') . $errors);
                    }
                    if (isset($this->dbh->error->errors) && count($this->dbh->error->errors) > 0 ) {
                        $error = '';
                        foreach($this->dbh->error->errors as $key => $err) {
                            if ($key === 'db_connect_fail') {
                                $error .= "Connect fail: Check the number of connections to the database or \n";
                            }
                            $error .= strip_tags( implode("\n", ($err) ) );
                        }
                        $this->setError( $error );
                    }
                }
            }
            return $this->dbh;      
        }

        public function optimize($db) {
            $proc_data = WPAdm_Running::getCommandResultData('db');
            if (!isset($proc_data['optimize'])) {
                if ( WPAdm_Running::is_stop() ) {
                    $link = $this->connect($db);
                    WPAdm_Core::log( __('Optimization of database tables was started' ,'dropbox-backup') );
                    $n = $link->query('SHOW TABLES');
                    WPAdm_Process::init('optimization', $n); 
                    $result = $link->last_result;
                    if (!empty( $link->last_error ) && $n > 0) {
                        $this->setError($link->last_error);
                    } else {
                        for($i = 0; $i < $n; $i++ ) {
                            $res = array_values( get_object_vars( $result[$i] ) );
                            $proc_data = WPAdm_Running::getCommandResultData('db');
                            if ( WPAdm_Running::is_stop() ) {
                                if (!isset($proc_data['optimize_table'][$res[0]])) { 
                                    $link->query('OPTIMIZE TABLE '. $res[0]);
                                    if (!empty( $link->last_error ) ) {
                                        $tables = isset($proc_data['optimize_table']) ? $proc_data['optimize_table'] : array();
                                        $tables[$res[0]] = 1;
                                        $proc_data['optimize_table'] = $tables;
                                        WPAdm_Running::setCommandResultData('db', $proc_data);
                                        $log = str_replace('%s', $res[0], __('Error during database table optimization: `%s`' ,'dropbox-backup') );
                                        WPAdm_Core::log($log);
                                    } else {
                                        $log = str_replace('%s', $res[0], __('Database table optimization of `%s` was successfully' ,'dropbox-backup') );
                                        WPAdm_Core::log($log);
                                        WPAdm_Process::set('optimization', ( $i + 1 ) );
                                    }
                                }
                            }
                        }
                        if ( WPAdm_Running::is_stop() ) {
                            WPAdm_Core::log( __('Optimization of database tables was Finished' ,'dropbox-backup') );
                            $proc_data = WPAdm_Running::getCommandResultData('db');
                            $proc_data['optimize'] = true;
                            WPAdm_Running::setCommandResultData('db', $proc_data);
                        }
                    }
                }
            }
        }
        public function repair($db)
        {
            $proc_data = WPAdm_Running::getCommandResultData('repair');
            if (!isset($proc_data['work'])) {
                $link = $this->connect($db);
                if ( WPAdm_Running::is_stop() ) {
                    WPAdm_Core::log( __('Repairing of MySQL database was started' ,'dropbox-backup') );
                    $n = $link->query('SHOW TABLE STATUS;');
                    WPAdm_Process::init('repair', $n);
                    $result = $link->last_result;
                    if (!empty( $link->last_error )) {
                        $this->setError($link->last_error);
                        return false;
                    } 
                    if ($link->last_result === null) {     
                        $this->setError(print_r(implode("\n", $link->error->errors), 1));
                        return false;
                    }
                    $tables = array();
                    for($i = 0; $i < $n; $i++ ) {
                        if ( WPAdm_Running::is_stop() ) {
                            $row = get_object_vars( $result[$i] );
                            $tables[] = $row;
                            WPAdm_Core::log('Start repairing of table `' . $row['Name'] . '`' );
                            $res = $link->query("REPAIR TABLE {$row['Name']};");
                            if ($res == 1) {
                                $proc_data = WPAdm_Running::getCommandResultData('repair');
                                $proc_data['repair'][$row['Name']] = 1;
                                WPAdm_Running::setCommandResultData('repair', $proc_data);
                            } else {
                                $this->setError($link->last_error);
                            }
                            WPAdm_Process::set('repair', ($i + 1) );
                            WPAdm_Core::log('Table repairing of `' . $row['Name'] . '` was finished');
                        } 
                    }
                    if ( WPAdm_Running::is_stop() ) {
                        $proc_data = WPAdm_Running::getCommandResultData('repair');
                        $proc_data['work'] = 1;
                        WPAdm_Running::setCommandResultData('repair', $proc_data);
                    }
                }
            }
        }

        public function mysqldump($db, $filename) 
        {
            $proc_data = WPAdm_Running::getCommandResultData('db');
            if (!isset($proc_data['mysqldump'])) {
                $link = $this->connect($db);
                if ( WPAdm_Running::is_stop() ) {
                    WPAdm_Core::log( __('Creating of MySQL dump was started' ,'dropbox-backup') );
                    $tables = array();
                    $n = $link->query('SHOW TABLES');
                    WPAdm_Process::init('mysqldump', $n);
                    $result = $link->last_result;
                    if (!empty( $link->last_error )) {
                        $this->setError($link->last_error);
                        return false;
                    } 
                    if ($link->last_result === null) {     
                        /* foreach($link->error->errors as $key => $errors) {
                        if ($key == db_connect_fail)
                        }*/
                        $this->setError(print_r(implode("\n", $link->error->errors), 1));
                        return false;
                    }
                    if ( WPAdm_Running::is_stop() ) {
                        for($i = 0; $i < $n; $i++ ) {
                            $row = array_values( get_object_vars( $result[$i] ) );
                            $tables[] = $row[0];
                        }
                    }
                    if ( WPAdm_Running::is_stop() ) {
                        foreach($tables as $key_tables => $table) {
                            $return = '';
                            $proc_data = WPAdm_Running::getCommandResultData('db');
                            if ( !isset($proc_data['mysqldump_table'][$table]) ) {

                                $result = $link->last_result;
                                if (!empty( $link->last_error ) && $n > 0) {
                                    $this->setError($link->last_error);
                                }
                                if ( WPAdm_Running::is_stop() ) {
                                    $return.= 'DROP TABLE IF EXISTS ' . $table . ';';

                                    $ress = $link->query('SHOW CREATE TABLE ' . $table);
                                    $result2 = $link->last_result;
                                    if (!empty( $link->last_error ) && $n > 0) {
                                        $this->setError($link->last_error);
                                    }
                                    $row2 = array_values( get_object_vars( $result2[0]  ) );
                                    $return.= "\n\n".$row2[1].";\n\n";
                                } 
                                if ( WPAdm_Running::is_stop() ) {
                                    file_put_contents($filename, $return, FILE_APPEND);
                                    $proc_data = WPAdm_Running::getCommandResultData('db');
                                    $proc_data['mysqldump_table'][$table] = 1;
                                    WPAdm_Running::setCommandResultData('db', $proc_data);
                                    $log = str_replace('%s', $table, __('Add table "%s" to the database dump' ,'dropbox-backup') );
                                    WPAdm_Core::log( $log );
                                }
                            }
                            if (strpos($table, 'mystatdata') !== false) {
                                continue;
                            }
                            if ( WPAdm_Running::is_stop() ) {
                                $while = true;
                                while($while) {
                                    $insert_values = false;
                                    if ( WPAdm_Running::is_stop() ) {
                                        $table_db = WPAdm_Running::getCommandResultData('tabledb');
                                        if (isset($table_db[$table])) {
                                            if (isset($table_db[$table]['work']) && $table_db[$table]['work'] == 1) {
                                                $from = $table_db[$table]['from']; // value from
                                                $to = $table_db[$table]['to']; // value to
                                                $insert_values = true;
                                            }
                                        } else {
                                            $from = 0;
                                            $to = $this->rows;
                                            $insert_values = true;
                                        }
                                    }

                                    if (isset($from) && !empty($to) && $from >= 0 && $to >= 0 && $insert_values === true) {
                                        unset($link);
                                        $link = $this->connect($db);
                                        $num_fields = $link->query( 'SELECT * FROM ' . $table . " LIMIT {$from}, {$to}" );
                                        if ($num_fields > 0) {
                                            WPAdm_Core::log( $link->last_error ) ;
                                            $result2 = $link->last_result;
                                            if ( WPAdm_Running::is_stop() ) {
                                                $log = __('Performing of database query:' ,'dropbox-backup') . ' SELECT * FROM ' . $table . " LIMIT {$from}, {$to}";  
                                                WPAdm_Core::log( $log );
                                            }
                                            for ($i = 0; $i < $num_fields; $i++) {  
                                                if ( WPAdm_Running::is_stop() ) {
                                                    $return = ''; 
                                                    $row = array_values( get_object_vars( $result2[$i] ) );
                                                    $rows_num = count($row);
                                                    if ($rows_num > 0) {
                                                        $return.= 'INSERT INTO ' . $table . ' VALUES(';
                                                        for($j=0; $j < $rows_num; $j++) {
                                                            $row[$j] = addslashes($row[$j]);
                                                            $row[$j] = str_replace("\n","\\n",$row[$j]);
                                                            if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                                                            if ($j<($rows_num-1)) { $return.= ','; }
                                                        }
                                                        $return .= ");\n";
                                                        file_put_contents($filename, $return, FILE_APPEND); 
                                                        $from += 1;
                                                        $table_db = WPAdm_Running::getCommandResultData('tabledb');
                                                        $table_db[$table]['from'] = $from;
                                                        $table_db[$table]['to'] = $to;
                                                        $table_db[$table]['work'] = 1;
                                                        WPAdm_Running::setCommandResultData('tabledb', $table_db);
                                                    }
                                                }
                                            }
                                        } else {
                                            $while = false;
                                            if ( WPAdm_Running::is_stop() ) {
                                                $table_db = WPAdm_Running::getCommandResultData('tabledb');
                                                $table_db[$table]['work'] = 0;
                                                WPAdm_Running::setCommandResultData('tabledb', $table_db); 
                                                WPAdm_Process::set('mysqldump', ( $key_tables + 1 ) );    
                                            }
                                        }
                                    } else {
                                        $while = false;
                                        if ( WPAdm_Running::is_stop() ) {
                                            $table_db = WPAdm_Running::getCommandResultData('tabledb');
                                            $table_db[$table]['work'] = 0;
                                            WPAdm_Running::setCommandResultData('tabledb', $table_db);
                                            WPAdm_Process::set('mysqldump', ( $key_tables + 1 ) ); 
                                        }
                                    }
                                }
                            }
                            if ( WPAdm_Running::is_stop() ) {
                                $proc_data = WPAdm_Running::getCommandResultData('db');
                                if (!isset($proc_data['mysqldump_table'][$table])) {
                                    $return ="\n\n\n";
                                    file_put_contents($filename, $return, FILE_APPEND);
                                }
                            }
                        }
                    }
                    if ( WPAdm_Running::is_stop() ) {
                        unset($link);
                        WPAdm_Core::log( __('Creating of MySQL database dump was finished' ,'dropbox-backup') ); 
                        $proc_data = WPAdm_Running::getCommandResultData('db');
                        $proc_data['mysqldump'] = true;
                        WPAdm_Running::setCommandResultData('db', $proc_data);
                    }
                }
                return true;
            } else {
                return false;
            }
        }

        private function setError($txt)
        {
            throw new Exception($txt);
        }

        public function restore($db, $file)
        {
            $link = $this->connect($db);
            WPAdm_Core::log( __('Database restoring was started' ,'dropbox-backup') );
            $fo = fopen($file, "r");
            if (!$fo) {
                WPAdm_Core::log( __('Error during openening of file dump' ,'dropbox-backup') );
                $this->setError( __('Error during openening of file dump' ,'dropbox-backup') );
                return false;
            }
            $sql = "";
            while(false !== ($char = fgetc($fo))) {
                $sql .= $char;
                if ($char == ";") {
                    $char_new = fgetc($fo);
                    if ($char_new !== false && $char_new != "\n") {
                        $sql .= $char_new;
                    } else {
                        $ress = $link->query($sql);
                        if (!empty( $link->last_error ) && $n > 0) {
                            $this->setError($link->last_error);
                            WPAdm_Core::log(__('MySQL Error: ' ,'dropbox-backup') . $link->last_error);
                            break;
                        };
                        $sql = "";
                    }
                }
            }
            WPAdm_Core::log(__('Database restoring was finished' ,'dropbox-backup'));  
        }
    }
}

