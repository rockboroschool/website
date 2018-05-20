<?php     

if ( ! defined( 'ABSPATH' ) ) exit;


/**
* error 
*           
* 100 - mothod not exist
* 101 - error method api
* 102 - error in the received data 
* 103 - error 
* 
* notice 
* 401 - activate user 
* 
* success
* 201 - registaration and acivate ok
* 202 - acivate plugin ok
* 
*/
if ( ! class_exists("errorWPADM")) {
    class errorWPADM {
        private static $messages = array(
        100 => 'Method doesn\'t exist.',
        101 => 'Method has an error.',
        102 => 'Received data has an error.',
        103 => 'There is an error in plugin activation.',
        201 => 'Registration and activation was successful.',
        202 => 'Plugin activation was successful.',
        401 => 'The User at WPAdm is not activated. Please, activate you User at WPAdm-System in <url> and try again.',

        );
        public static function getMessage($code)
        {
            if (isset(self::$messages[$code])) {
                return self::$messages[$code]; 
            } else {
                return "Server error: received data are invalid.";
            }

        }
    }
}