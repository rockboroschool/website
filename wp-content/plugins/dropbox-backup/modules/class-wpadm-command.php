<?php

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class WPAdm_Сommand
 */
if (!class_exists('WPAdm_Сommand')) {
    abstract class WPAdm_Сommand {
        /**
         * @param WPAdm_Command_Context $context
         * @return boolean
         */
        abstract function execute(WPAdm_Command_Context $context);
                                                                   
    }
}