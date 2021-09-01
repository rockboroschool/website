<?php
namespace PDFP\Block;
if(!defined('ABSPATH')) {
    return;
}
use PDFP\Helper\DefaultArgs;
use PDFP\Model\AdvanceSystem;
use PDFP\Services\PDFTemplate;


class RegisterBlock{
    protected static $_instance = null;

    function __construct(){
        // add_action('init', [$this, 'enqueue_block_css_js']);
        add_action('init', [$this, 'enqueue_script']);
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

    function enqueue_script(){
        wp_register_script(	'pdfp-editor', PDFP_PLUGIN_DIR.'dist/editor.js', array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'jquery'  ), PDFP_PLUGIN_VERSION, true );

        // wp_register_script( 'pdp-public', PDP_PLUGIN_DIR. 'dist/public.js' , array('jquery', 'bplugins-plyrio', 'pdp-social', 'bplugins-font-awesome'), PDP_VER, true );

        wp_register_style( 'pdfp-editor', PDFP_PLUGIN_DIR. 'dist/editor.css' , array(), PDFP_PLUGIN_VERSION );

        register_block_type('pdfp/pdfposter', array(
            'editor_script' => 'pdfp-editor',
            'editor_style' => 'pdfp-editor',
            // 'script' => 'pdp-public',
            // 'style' => 'pdp-public',
            'render_callback' => [$this, 'render_callback_video']
        ));

    
        register_block_type('meta-box/document-embedder', array(
            'editor_script' => 'pdfp-editor',
            'editor_style' => 'pdfp-editor',
            'render_callback' => function($attr, $content){
                ob_start();
                if(isset($attr['selected'])){
                    echo do_shortcode("[pdf id=".esc_attr($attr['selected'])."]");
                }else if(isset($attr['data']['tringle_text'])){
                    echo do_shortcode("[pdf id=".esc_attr($attr['data']['tringle_text'])."]");
                }
                return ob_get_clean();
            }
        ));

        wp_localize_script('pdfp-editor', 'pdfp', [
            'siteUrl' => home_url(),
            'placeholder' => PDFP_PLUGIN_DIR.'img/placeholder.pdf'
        ]);
    }

    public function render_callback_video($atts, $content){
        $data = DefaultArgs::parseArgs(AdvanceSystem::getData($atts));
        return PDFTemplate::html($data);
    }

}

RegisterBlock::instance();

