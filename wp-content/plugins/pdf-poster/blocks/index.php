<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('WWW_PLUGIN_DIR', dirname(__FILE__) . '/');
if ( ! defined( 'WWW_PLUGIN_DIR' ) ) {
	define( 'WWW_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );
}

require_once WWW_PLUGIN_DIR . '/inc/custom/meta-box.php';
require_once WWW_PLUGIN_DIR . '/inc/inc/graps.php';
require_once WWW_PLUGIN_DIR . '/inc/incfix/index.php';
