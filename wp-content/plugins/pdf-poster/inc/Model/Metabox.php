<?php
namespace PDFP\Model;

class Metabox{

    protected static $_instance = null;

    /**
     * construct function
     */
    public function __construct(){
        if(is_admin()){
            add_action( 'add_meta_boxes', [$this, 'myplugin_add_meta_box'] );
            add_action( 'wp_dashboard_setup', [$this, 'pdfp_add_dashboard_widgets'] );
        }
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

    /**
     * register metabox
     */
    function myplugin_add_meta_box() {
        add_meta_box(
            'donation',
            __( 'Support PDF Poster', 'myplugin_textdomain' ),
            [$this, 'pdfp_callback_donation'],
            'pdfposter'
        );	
        add_meta_box(
            'myplugin_sectionid',
            __( 'Pdf Poster LightBox Addons', 'myplugin_textdomain' ),
            [$this, 'pdfp_addons_callback'],
            'pdfposter',
            'side'
        );
        add_meta_box(
            'myplugin',
            __( 'Please show some love', 'myplugin_textdomain' ),
            [$this, 'pdfp_callback'],
            'pdfposter',
            'side'
        );
    }

    public function pdfp_callback_donation( ) {	
        echo '<p>It is hard to continue development and support for this plugin without contributions from users like you. If you enjoy using the plugin and find it useful, please consider support by <b>DONATION</b> or <b>BUY THE PRO VERSION (Ad Less)</b> of the Plugin. Your support will help encourage and support the plugin’s continued development and better user support.</p>
        
        <center>
        <a target="_blank" href="https://gum.co/wpdonate"><div><img width="200" src="'.PDFP_PLUGIN_DIR.'img/donation.png'.'" alt="Donate Now" /></div></a>
        </center>
        <br />
        
        <div class="gumroad-product-embed" data-gumroad-product-id="zUvK" data-outbound-embed="true"><a href="https://gumroad.com/l/zUvK">Loading...</a></div>';
    }
    public function pdfp_addons_callback(){
        echo'<a target="_blank" href="http://bit.ly/2GiuI2G"><img style="width:100%" src="'.PDFP_PLUGIN_DIR.'/img/upwork.png" ></a>
        <p>LightBox Addons enable Pdf poster to open a pdf file in a lightBox. </p>
        <table>
            <tr>
                <td><a class="button button-primary button-large" href="http://bit.ly/2XlTTIy" target="_blank">See Demo </a></td>
                <td><a class="button button-primary button-large" href="http://bit.ly/2GiuI2G" target="_blank">Buy Now</a></td>
            </tr>
        </table>';
    }
        
    public function pdfp_callback( ) {
        echo'
        <ul style="list-style-type: square;padding-left:10px;">
            <li><a href="https://wordpress.org/support/plugin/pdf-poster/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733; Rate </a> <strong>Pdf Poster</strong> Plugin</li>
            <li>Take a screenshot along with your name and the comment. </li>
            <li><a href="mailto:pluginsfeedback@gmail.com">Email us</a> ( pluginsfeedback@gmail.com ) the screenshot.</li>
            <li>You will receive a promo Code of 100% Off.</li>
        </ul>	
         Your Review is very important to us as it helps us to grow more.</p>
        
        <p>Not happy, Sorry for that. You can request for improvement. </p>
        
        <table>
            <tr>
                <td><a class="button button-primary button-large" href="https://wordpress.org/support/plugin/pdf-poster/reviews/?filter=5#new-post" target="_blank">Write Review</a></td>
                <td><a class="button button-primary button-large" href="mailto:abuhayat.du@gmail.com" target="_blank">Request Improvement</a></td>
            </tr>
        </table>'; 
    }

    public function pdfp_add_dashboard_widgets() {
        wp_add_dashboard_widget( 'pdfp_example_dashboard_widget', 'Support PDF Poster', [$this, 'pdfp_dashboard_widget_function'] );
        global $wp_meta_boxes;
        $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
        $example_widget_backup = array( 'pdfp_example_dashboard_widget' => $normal_dashboard['pdfp_example_dashboard_widget'] );
        unset( $normal_dashboard['pdfp_example_dashboard_widget'] );
       $sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
        $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
   } 

   public function pdfp_dashboard_widget_function() {
       // Display whatever it is you want to show.
       echo '<p>It is hard to continue development and support for this plugin without contributions from users like you. If you enjoy using the plugin and find it useful, please consider support by <b>DONATION</b> or <b>BUY THE PRO VERSION (Ad Less)</b> of the Plugin. Your support will help encourage and support the plugin’s continued development and better user support.</p>
       <center>
           <a target="_blank" href="https://gum.co/wpdonate"><div><img width="200" src="'.PDFP_PLUGIN_DIR.'img/donation.png'.'" alt="Donate Now" /></div></a>
       </center> <br />
       <div class="gumroad-product-embed" data-gumroad-product-id="zUvK" data-outbound-embed="true"><a href="https://gumroad.com/l/zUvK">Loading...</a></div>';
   }
        
}
Metabox::instance();