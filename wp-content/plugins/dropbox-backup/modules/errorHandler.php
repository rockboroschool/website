<?php
if (!function_exists('myErrorHandler')) {
    function myErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return;
        }
    
        switch ($errno) {
            case E_USER_ERROR:
                myErrorHandlerEcho( " $errfile($errline)\t[$errno] $errstr", 'ERROR');
                myErrorHandlerEcho( "  Fatal error in line  $errline file $errfile");
                myErrorHandlerEcho( ", PHP " . PHP_VERSION . " (" . PHP_OS . ")");
                exit(1);
                break;
    
            case E_USER_WARNING:
                myErrorHandlerEcho( " $errfile($errline)\t[$errno] $errstr",  'WARNING');
                break;
    
            case E_USER_NOTICE:
                myErrorHandlerEcho( " $errfile($errline)\t[$errno] $errstr",  'WARNING');
                break;
    
            default:
                myErrorHandlerEcho( " $errfile($errline)\tUknown error: [$errno] $errstr");
                break;
        }
    
        return true;
    }
}

if (!function_exists('myErrorHandlerEcho')) {
    function myErrorHandlerEcho($txt, $type = '') {
        $dt = date("Y-m-d H:i:s");
        file_put_contents(dirname(__FILE__) . '/error.log', $dt . "\t{$type}\n{$txt}\n\n", FILE_APPEND);
    }
    $old_error_handler = set_error_handler("myErrorHandler");
}