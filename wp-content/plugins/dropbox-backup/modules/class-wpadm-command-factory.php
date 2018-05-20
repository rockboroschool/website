<?php


if ( ! defined( 'ABSPATH' ) ) exit;


/**
* Class WPAdm_Command_Factory
*/
if (!class_exists('WPAdm_Command_Factory')) {
    class WPAdm_Command_Factory {
        /**
        * @param string $com
        * @return command
        */
        static function getCommand($com = '') {
            if (!preg_match("|[a-zA-Z0-9_]|", $com)) {
                return null;
            }
            if (function_exists('mb_strtolower')) {
                $com = mb_strtolower($com);
            } else {
                $com = strtolower($com);
            }
            $tmp = explode('_', $com);
            $class_file = WPAdm_Core::getPluginDir() . "/commands/class-wpadm-command-" . str_replace('_', '-', $com) . ".php";

            if (file_exists($class_file)) {
                require_once $class_file;
                foreach($tmp as $k=>$v) {
                    $tmp[$k] = ucfirst($v);
                }
                $com = implode('_', $tmp);

                $class_name = "WPAdm_Command_{$com}";
                return new $class_name();
            }

            return null;
        }
    }
}