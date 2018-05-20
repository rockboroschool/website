<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPadm_Command_Mysqldump')) {
    class WPadm_Command_Mysqldump extends WPAdm_Сommand{
        public function execute(WPAdm_Command_Context $context)
        {
            if ( WPAdm_Running::is_stop() ) {
                //WPAdm_Core::log(print_r($context, true));
                require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-mysqldump.php';
                $mysqldump = new WPAdm_Mysqldump();
                $mysqldump->host = $context->get('host');
                $mysqldump->user = $context->get('user');
                $mysqldump->password = $context->get('password');

                try {
                    $mysqldump->mysqldump($context->get('db'), $context->get('to_file'));
                } catch (Exception $e) {
                    $context->setError($e->getMessage());
                    return false;
                }
            }
            return true;
        }
    }
}