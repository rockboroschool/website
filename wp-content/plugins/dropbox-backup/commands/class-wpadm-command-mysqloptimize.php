<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPadm_Command_Mysqloptimize')) {
    class WPadm_Command_Mysqloptimize extends WPAdm_Сommand{
        public function execute(WPAdm_Command_Context $context)
        {
            //WPAdm_Core::log(print_r($context, true));
            require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-mysqldump.php';
            $mysqldump = new WPAdm_Mysqldump();
            $mysqldump->host = $context->get('host');
            $mysqldump->user = $context->get('user');
            $mysqldump->password = $context->get('password');
    
            try {
                $mysqldump->optimize($context->get('db'));
            } catch (Exception $e) {
                $context->setError($e->getMessage());
                return false;
            }
            return true;
        }
    }
}
