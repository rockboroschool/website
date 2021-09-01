<?php
namespace PDFP\Model;
class EnqueueAssets{
    protected static $_instance = null;

    public function __construct(){
        add_action("wp_enqueue_scripts", [$this, 'publicAssets']);
        add_action( 'admin_enqueue_scripts', [$this, 'adminAssets'] );
    }

    /**
     * Create Instance
     */
    public static function instance(){
        if(self::$_instance === null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Enqueue public assets
     */
    public function publicAssets(){
        
        // wp_enqueue_script('jquery');
        wp_register_style( 'pdfp-public',  PDFP_PLUGIN_DIR. 'dist/public.css', array(), PDFP_PLUGIN_VERSION );

        // wp_register_script( 'bplugins-plyrio', PDFP_PLUGIN_DIR. 'assets/js/plyr.js', array(), PDFP_PLUGIN_VERSION, true );
    }

    /**
     * enqueue admin assets
     **/    
    function adminAssets($hook) {
        wp_enqueue_style('pdfp-admin', PDFP_PLUGIN_DIR.'dist/admin.css', array(), PDFP_PLUGIN_VERSION);
        wp_enqueue_script('pdfp-admin', PDFP_PLUGIN_DIR.'dist/admin.js', array(), PDFP_PLUGIN_VERSION);
        wp_enqueue_script( 'gum-js', PDFP_PLUGIN_DIR.'js/gumroad-embed.js', array(), PDFP_PLUGIN_VERSION );
    }
}

EnqueueAssets::instance();