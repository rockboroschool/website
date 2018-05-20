<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPAdm_Method_Ping')) {
    class WPAdm_Method_Ping extends WPAdm_Method_Class {
        public function getResult()
        {
            global $wp_version;
            $this->result->setResult(WPAdm_result::WPADM_RESULT_SUCCESS);
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
			$plugin_dir = WPAdm_Core::getPluginDir();
			$plugin_dir_array = explode("/", $plugin_dir );
            $plugin_name = array_pop( $plugin_dir_array );
            $plugin_name2 = str_replace("-", "_", $plugin_name);
            $plugin     = get_plugins("/$plugin_name");
            $pl_version = "";
            if (isset($plugin["$plugin_name.php"])) {
                $pl_version = $plugin["$plugin_name.php"]['Version'];
            }
            if (isset($plugin["$plugin_name2.php"])) {
                $pl_version = $plugin["$plugin_name2.php"]['Version'];
            }
            

            $data_return = array(
                'reply'             => 'pong',
                'date'              => array('time_zone' => date('O'), 'time' => time()),
                'system_version'    => $wp_version,
                'plugin_version'    => $pl_version,
                'system'            => 'wordpress'
            );


            //get info for minimal requirements
            $data_return['php_version']                 = @phpversion();
            $data_return['php_max_execution_time']      = @intval(ini_get('max_execution_time'));
            $data_return['php_memory_limit']            = @ini_get('memory_limit');
            $data_return['php_extensions']              = @implode(',',get_loaded_extensions());
            $data_return['php_disabled_functions']      = @ini_get('disable_functions');
            $data_return['php_max_execution_time_up']   = 0;
            $data_return['php_memory_limit_up']         = 0;
            $data_return['mysql_version']               = '';
            $data_return['suhosin_functions_blacklist'] = '';
            //try set new max time
            $newMaxExecutionTime = 3000;
            @set_time_limit( $newMaxExecutionTime );
            if( @intval(ini_get('max_execution_time')) == $newMaxExecutionTime ){
                $data_return['php_max_execution_time_up'] = 1;
            }
            //try set new memory limit
            $newMemoryLimit = 256;
            @ini_set('memory_limit', $newMemoryLimit.'M');
            if( @intval(ini_get('memory_limit')) == $newMemoryLimit ){
                $data_return['php_memory_limit_up'] = 1;
            }
            //try get mysql version
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);
            if (!mysqli_connect_errno()) {
                $data_return['mysql_version'] = $mysqli->server_info;
            }
            //check suhosin
            if (extension_loaded('suhosin') ) {
                $data_return['suhosin_functions_blacklist'] = @ini_get('suhosin.executor.func.blacklist');
            }

            $this->result->setData(
                $data_return
            );
            return $this->result;
        }
    }
}