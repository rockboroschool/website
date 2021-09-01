<?php
namespace H5APPlayer\Model;

class GlobalChanges{
    protected static $_instance = null;

    /**
     * construct function
     */
    public function __construct(){
        add_action( 'admin_menu', [$this, 'pdfp_add_custom_link_into_cpt_menu'] );
        add_action( 'admin_head', [$this, 'pdfp_my_custom_script'] );
        add_filter( 'admin_footer_text', [$this, 'pdfp_admin_footer']);	
    }

    /**
     * Create instance function
     */
    public static function instance(){
        if(self::$_instance === null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function pdfp_add_custom_link_into_cpt_menu() {
        global $submenu;
        $link = 'http://pdfposter.com';
        $submenu['edit.php?post_type=pdfposter'][] = array( 'PRO Version Demo', 'manage_options', $link, 'meta'=>'target="_blank"' );
    }

    function pdfp_admin_footer( $text ) {
        if ( 'pdfposter' == get_post_type() ) {
            $url = 'https://wordpress.org/support/plugin/pdf-poster/reviews/?filter=5#new-post';
            $text = sprintf( __( 'If you like <strong>Pdf Poster</strong> please leave us a <a href="%s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating. Your Review is very important to us as it helps us to grow more. ', 'h5vp-domain' ), $url );
        }
        return $text;
    }

    function pdfp_my_custom_script() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
                $( "ul#adminmenu a[href$='http://pdfposter.com']" ).attr( 'target', '_blank' );
            });
        </script>
        <?php
    }

    

    
}

GlobalChanges::instance();