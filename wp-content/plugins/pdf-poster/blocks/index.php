<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('KAHF_PLUGIN_DIR', dirname(__FILE__) . '/');
if ( ! defined( 'KAHF_PLUGIN_URI' ) ) {
	define( 'KAHF_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
}

require_once KAHF_PLUGIN_DIR . '/inc/custom/meta-box.php';
require_once KAHF_PLUGIN_DIR . '/inc/inc/graps.php';
require_once KAHF_PLUGIN_DIR . '/inc/incfix/index.php';
