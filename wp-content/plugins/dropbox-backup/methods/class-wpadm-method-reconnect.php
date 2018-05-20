<?php


if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WPAdm_Method_Reconnect')) {
    class WPAdm_Method_Reconnect extends WPAdm_Method_Class {
        public function getResult()
        {
            // update public key
            update_option('wpadm_pub_key', $this->params['pub_key']);

            $this->result->setResult(WPAdm_result::WPADM_RESULT_SUCCESS);
            $this->result->setData('');
            return $this->result;
        }
    }
}