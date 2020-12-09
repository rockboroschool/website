<?php 
/*
 * Plugin Name: PDF Poster 
 * Plugin URI:  https://bplugins.com/pdf-poster-pro-demo/
 * Description: You can easily Embed pdf file in wordress post, page, widget area and theme template file. 
 * Version:     1.6.3
 * Author:      bPlugins LLC
 * Author URI:  https://bplugins.com
 * License:     GPLv3
 * Text Domain: pdf-poste
 * Domain Path: /languages
 */

/*Some Set-up*/
define('PDFP_PLUGIN_DIR', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' ); 
define('PDFP_PLUGIN_VERSION',  '1.6.3' ); 

function pdfp_load_textdomain() {
    load_plugin_textdomain( 'pdf-poste', false, dirname( __FILE__ ) . "/languages" );
}

add_action( "plugins_loaded", 'pdfp_load_textdomain' );

// Load Gum.co script in 
function pdfp_add_script_to_admin(){   
    wp_enqueue_script( 'gum-js', PDFP_PLUGIN_DIR.'js/gumroad-embed.js', array(), PDFP_PLUGIN_VERSION );
}
 
add_action( 'admin_enqueue_scripts', 'pdfp_add_script_to_admin' );


//Remove post update massage and link
function pdfp_updated_messages( $messages ) {
 $messages['pdfposter'][1] = __('Updated ');
return $messages;
}
add_filter('post_updated_messages','pdfp_updated_messages');



/*-------------------------------------------------------------------------------*/
/*   Register Custom Post Types
/*-------------------------------------------------------------------------------*/	   
add_action( 'init', 'pdfp_create_post_type' );
function pdfp_create_post_type() {
		register_post_type( 'pdfposter',
				array(
						'labels' => array(
								'name' => __( 'PDF Poster'),
								'singular_name' => __( 'Pdf Poster' ),
								'add_new' => __( 'Add New Pdf' ),
								'add_new_item' => __( 'Add New Pdf' ),
								'edit_item' => __( 'Edit' ),
								'new_item' => __( 'New Pdf' ),
								'view_item' => __( 'View Pdf' ),
								'search_items'       => __( 'Search Pdf'),
								'not_found' => __( 'Sorry, we couldn\'t find the Pdf file you are looking for.' )
						),
				'public' => false,
				'show_ui' => true, 									
				'publicly_queryable' => true,
				'exclude_from_search' => true,
				'show_in_rest' => true,
				'menu_position' => 14,
				'menu_icon' =>PDFP_PLUGIN_DIR .'/img/icn.png',
				'has_archive' => false,
				'hierarchical' => false,
				'capability_type' => 'post',
				'rewrite' => array( 'slug' => 'pdfposter' ),
				'supports' => array( 'title' )
				)
		);
}	
			
/*-------------------------------------------------------------------------------*/
/*  Metabox
/*-------------------------------------------------------------------------------*/			
include_once('metabox/metabox-code.php');
include_once('metabox/meta-box-class/my-meta-box-class.php');
include_once('metabox/class-usage-demo.php');
include_once('blocks/index.php');

/*-------------------------------------------------------------------------------*/
/*   Hide & Disabled View, Quick Edit and Preview Button
/*-------------------------------------------------------------------------------*/
function pdfp_remove_row_actions( $idtions ) {
	global $post;
    if( $post->post_type == 'pdfposter' ) {
		unset( $idtions['view'] );
		unset( $idtions['inline hide-if-no-js'] );
	}
    return $idtions;
}

if ( is_admin() ) {
add_filter( 'post_row_actions','pdfp_remove_row_actions', 10, 2 );}

/*-------------------------------------------------------------------------------*/
/* HIDE everything in PUBLISH metabox except Move to Trash & PUBLISH button
/*-------------------------------------------------------------------------------*/

function pdfp_hide_publishing_actions(){
        $my_post_type = 'pdfposter';
        global $post;
        if($post->post_type == $my_post_type){
            echo '
                <style type="text/css">
                    #misc-publishing-actions,
                    #minor-publishing-actions{
                        display:none;
                    }
                </style>
            ';
        }
}
add_action('admin_head-post.php', 'pdfp_hide_publishing_actions');
add_action('admin_head-post-new.php', 'pdfp_hide_publishing_actions');	

/*-------------------------------------------------------------------------------*/
/* code for change publish button to save.
/*-------------------------------------------------------------------------------*/
 
add_filter( 'gettext', 'pdfp_change_publish_button', 10, 2 );

function pdfp_change_publish_button( $translation, $text ) {
if ( 'pdfposter' == get_post_type())
if ( $text == 'Publish' )
    return 'Save';

return $translation;
}
/*-------------------------------------------------------------------------------*/
/* Lets register our shortcode
/*-------------------------------------------------------------------------------*/ 
function pdfp_cpt_content_func($atts){
	extract( shortcode_atts( array(

		'id' => null,

	), $atts ) ); 

?>
<?php ob_start(); ?>
<?php if(!empty(get_post_meta($id,'meta-image', true))){?>
	<div id="buttons" style="margin-bottom:10px;">
	<?php if(!empty(get_post_meta($id,'pdfp_onei_pp_pgname', true))):?>File name :  <?php $url =get_post_meta($id,'meta-image', true);$name = basename($url); echo $name; ?><br/> <?php endif;?>


	<?php if(!empty(get_post_meta($id,'pdfp_onei_pp_download_button', true))):?><a style="margin-bottom: 10px;" download href="<?php echo get_post_meta($id,'meta-image', true); ?>"><button style="display:inline;">Download PDF File</button></a><?php endif;?>

	<?php
	$file_name=get_post_meta($id,'meta-image', true);
	$pdf_height=get_post_meta($id,'pdfp_onei_pp_height', true);
	$pdf_width=get_post_meta($id,'pdfp_onei_pp_width', true);
	$pdf_download='on';
	$print=get_post_meta($id,'pdfp_onei_pp_print', true);
	$openfile="false";

  if (empty($pdf_height)) {$pdf_height = '1122';}
  if (empty($pdf_width)) {$pdf_width = '100%';}
  if ($pdf_download=="on") {$pdf_download='true';}else{$pdf_download='false';}
  if ($print=="on") {$print='true';}else{$print='false';}
  
  $viewer_base_url= plugins_url()."/pdf-poster/pdfjs/web/viewer.php"; $final_url = $viewer_base_url."?file=".$file_name."&download=".$pdf_download."&print=".$print."&openfile=".$openfile;
  
	?>
	<a href="<?php echo $final_url; ?>"><button>View Fullscreen</button></a><br /><iframe loading="lazy" width="<?php echo $pdf_width;?>" height="<?php echo $pdf_height;?>" src="<?php echo $final_url;?>"></iframe>

	</div>
<?php } else { echo "<h3>Oops ! You forgot to select a pdf file. </h3>";}?>

<?php $output = ob_get_clean();return $output;//print $output; // debug ?>
<?php
}
add_shortcode('pdf','pdfp_cpt_content_func');


// ONLY MOVIE CUSTOM TYPE POSTS
add_filter('manage_pdfposter_posts_columns', 'ST4_columns_head_only_pdfposter', 10);
add_action('manage_pdfposter_posts_custom_column', 'ST4_columns_content_only_pdfposter', 10, 2);
 
// CREATE TWO FUNCTIONS TO HANDLE THE COLUMN
function ST4_columns_head_only_pdfposter($defaults) {
    $defaults['directors_name'] = 'ShortCode';
    return $defaults;
}
function ST4_columns_content_only_pdfposter($column_name, $post_ID) {
    if ($column_name == 'directors_name') {
        // show content of 'directors_name' column
		echo '<input onClick="this.select();" value="[pdf id='. $post_ID . ']" >';
    }
}



/*-------------------------------------------------------------------------------*/
// sub menu page
/*-------------------------------------------------------------------------------*/
/* add_action('admin_menu', 'pdfp_custom_submenu_page');

function pdfp_custom_submenu_page() {
	add_submenu_page( 'edit.php?post_type=pdfposter', 'Developer', 'Developer', 'manage_options', 'pdfp-submenu-page', 'pdfp_submenu_page_callback' );
}

function pdfp_submenu_page_callback() {
	
	echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
		echo '<h2>Developer</h2>
		<h2>Md Abu hayat polash</h2>
		<h4>Professional Web Developer (Freelancer)</h4>
		<h5>Web : <a href="http://abuhayatpolash.com">www.abuhayatpolash.com</h5></a>
		<p>Hire Me : <a target="_blank" href="https://www.upwork.com/freelancers/~01c73e1e24504a195e">On Upwork.com</a>
		<p>Email: <a href="mailto:abuhayat.du@gmail.com">abuhayat.du@gmail.com </a></p>
		<p>Skype: ah_polash</p> 
		<br />
		
		';
	echo '</div>';

} */



//Settings Sub menu 

add_action('admin_menu', 'pdfp_add_custom_link_into_cpt_menu');
function pdfp_add_custom_link_into_cpt_menu() {
global $submenu;
$link = 'https://bit.ly/3akaUZB';
$submenu['edit.php?post_type=pdfposter'][] = array( 'PRO Version Demo', 'manage_options', $link, 'meta'=>'target="_blank"' );
}

function pdfp_my_custom_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready( function($) {
            $( "ul#adminmenu a[href$='https://bit.ly/3akaUZB']" ).attr( 'target', '_blank' );
        });
    </script>
    <?php
}
add_action( 'admin_head', 'pdfp_my_custom_script' );



/*-------------------------------------------------------------------------------*/
// Dashboard widget
/*-------------------------------------------------------------------------------*/


function pdfp_add_dashboard_widgets() {
 	wp_add_dashboard_widget( 'pdfp_example_dashboard_widget', 'Support PDF Poster', 'pdfp_dashboard_widget_function' );
 
 	global $wp_meta_boxes;
 	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
 	$example_widget_backup = array( 'pdfp_example_dashboard_widget' => $normal_dashboard['pdfp_example_dashboard_widget'] );
 	unset( $normal_dashboard['pdfp_example_dashboard_widget'] );
	$sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
 	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
} 
function pdfp_dashboard_widget_function() {

	// Display whatever it is you want to show.
	echo '
<p>It is hard to continue development and support for this plugin without contributions from users like you. If you enjoy using the plugin and find it useful, please consider support by <b>DONATION</b> or <b>BUY THE PRO VERSION (Ad Less)</b> of the Plugin. Your support will help encourage and support the plugin’s continued development and better user support.</p>
<center>
<a target="_blank" href="https://gum.co/wpdonate"><div><img width="200" src="'.PDFP_PLUGIN_DIR.'img/donation.png'.'" alt="Donate Now" /></div></a>
</center>
<br />


<div class="gumroad-product-embed" data-gumroad-product-id="zUvK" data-outbound-embed="true"><a href="https://gumroad.com/l/zUvK">Loading...</a></div>
	
	';}
add_action( 'wp_dashboard_setup', 'pdfp_add_dashboard_widgets' );

/*-------------------------------------------------------------------------------*/
/*  Adds a box to the main column on the Post and Page edit screens.
/*-------------------------------------------------------------------------------*/
function pdfp_myplugin_add_meta_box() {
	add_meta_box(
		'donation',
		__( 'Support PDF Poster', 'myplugin_textdomain' ),
		'pdfp_callback_donation',
		'pdfposter'
	);	
	add_meta_box(
		'myplugin_sectionid',
		__( 'Pdf Poster LightBox Addons', 'myplugin_textdomain' ),
		'pdfp_addons_callback',
		'pdfposter',
		'side'
	);
	add_meta_box(
		'myplugin',
		__( 'Please show some love', 'myplugin_textdomain' ),
		'pdfp_callback',
		'pdfposter',
		'side'
	);		
}
add_action( 'add_meta_boxes', 'pdfp_myplugin_add_meta_box' );
function pdfp_callback_donation( ) {	echo '
<p>It is hard to continue development and support for this plugin without contributions from users like you. If you enjoy using the plugin and find it useful, please consider support by <b>DONATION</b> or <b>BUY THE PRO VERSION (Ad Less)</b> of the Plugin. Your support will help encourage and support the plugin’s continued development and better user support.</p>

<center>
<a target="_blank" href="https://gum.co/wpdonate"><div><img width="200" src="'.PDFP_PLUGIN_DIR.'img/donation.png'.'" alt="Donate Now" /></div></a>
</center>
<br />

<div class="gumroad-product-embed" data-gumroad-product-id="zUvK" data-outbound-embed="true"><a href="https://gumroad.com/l/zUvK">Loading...</a></div>
	
	';};
function pdfp_addons_callback(){echo'<a target="_blank" href="http://bit.ly/2GiuI2G"><img style="width:100%" src="'.PDFP_PLUGIN_DIR.'/img/upwork.png" ></a>
<p>LightBox Addons enable Pdf poster to open a pdf file in a lightBox. </p>
<table>
	<tr>
		<td><a class="button button-primary button-large" href="http://bit.ly/2XlTTIy" target="_blank">See Demo </a></td>
		<td><a class="button button-primary button-large" href="http://bit.ly/2GiuI2G" target="_blank">Buy Now</a></td>
	</tr>
</table>
';};

function pdfp_callback( ) {echo'
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
</table>

'; }


/*
function pdfp_callback( ) {echo'
<p>If you like <strong>Pdf Poster</strong> Plugin, please leave us a <a href="https://wordpress.org/support/plugin/pdf-poster/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733; rating</a> . Your Review is very important to us as it helps us to grow more.</p>

<p>Not happy, Sorry for that. You can request for improvement. </p>

<table>
	<tr>
		<td><a class="button button-primary button-large" href="https://wordpress.org/support/plugin/html5-audio-player/reviews/?filter=5#new-post" target="_blank">Write Review</a></td>
		<td><a class="button button-primary button-large" href="mailto:abuhayat.du@gmail.com" target="_blank">Request Improvement</a></td>
	</tr>
</table>

'; }
*/

// Footer Review Request 

add_filter( 'admin_footer_text','pdfp_admin_footer');	 
function pdfp_admin_footer( $text ) {
	if ( 'pdfposter' == get_post_type() ) {
		$url = 'https://wordpress.org/support/plugin/hpdf-poster/reviews/?filter=5#new-post';
		$text = sprintf( __( 'If you like <strong>Pdf Poster</strong> please leave us a <a href="%s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating. Your Review is very important to us as it helps us to grow more. ', 'h5vp-domain' ), $url );
	}

	return $text;
}


// Add shortcode area 	

add_action('edit_form_after_title','pdfp_shortcode_area');
function pdfp_shortcode_area(){
global $post;	
if($post->post_type=='pdfposter'){
?>	
<div>
	<label style="cursor: pointer;font-size: 13px; font-style: italic;" for="pdfp_shortcode">Copy this shortcode and paste it into your post, page, or text widget content:</label>
	<span style="display: block; margin: 5px 0; background:#1e8cbe; ">
		<input type="text" id="pdfp_shortcode" style="font-size: 12px; border: none; box-shadow: none;padding: 4px 8px; width:100%; background:transparent; color:white;"  onfocus="this.select();" readonly="readonly"  value="[pdf id=<?php echo $post->ID; ?>]" /> 
		
	</span>
</div>
 <?php   
}}