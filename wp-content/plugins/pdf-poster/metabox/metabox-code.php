<?php

/**
 * Loads the image management javascript
 */
function prfx_image_enqueue() {
    global $typenow;
    if( $typenow == 'pdfposter' ) {
        wp_enqueue_media();
 
        // Registers and enqueues the required javascript.
        wp_register_script( 'meta-box-image', plugin_dir_url( __FILE__ ) . 'meta-box-image.js', array( 'jquery' ) );
        wp_localize_script( 'meta-box-image', 'meta_image',
            array(
                'title' => __( 'Choose A PDF File', 'prfx-textdomain' ),
                'button' => __( 'Use File', 'prfx-textdomain' ),
            )
        );
        wp_enqueue_script( 'meta-box-image' );
    }
}
add_action( 'admin_enqueue_scripts', 'prfx_image_enqueue' );



/**
 * Adds a meta box to the post editing screen
 */
function prfx_custom_meta() {
    add_meta_box( 'prfx_meta', __( 'Select PDF (Required)', 'prfx-textdomain' ), 'prfx_meta_callback', 'pdfposter','normal','high');
}
add_action( 'add_meta_boxes', 'prfx_custom_meta' );

/**
 * Outputs the content of the meta box
 */
 function prfx_meta_callback( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'prfx_nonce' );
    $prfx_stored_meta = get_post_meta( $post->ID );
    ?>
	
	<p>
		<label for="meta-image" class="prfx-row-title"><?php _e( 'Select a .pdf file', 'prfx-textdomain' )?></label>
		<input type="text" name="meta-image" id="meta-image" value="<?php if ( isset ( $prfx_stored_meta['meta-image'] ) ) echo $prfx_stored_meta['meta-image'][0]; ?>" />
		<input type="button" id="meta-image-button" class="button" value="<?php _e( 'Choose PDF File', 'prfx-textdomain' )?>" />
	</p>
	<p><b>Attention Please !</b></p>
	<dl>
		<dd>- Some user reported us that Internet download manager (IDM) desktop app causes an error <i><a target="_blank" href="<?php echo PDFP_PLUGIN_DIR .'img/error.png'; ?>">Unexpected server response</a></i>. If you encounter the same issue, then try with different PC or uninstall the IDM app. ITS NOT A PLUGIN ISSUE !</dd>
		<dd>- Before using a large pdf file, <a href="https://helpx.adobe.com/acrobat/using/optimizing-pdfs-acrobat-pro.html">Optimize it</a> for fast load.</dd>
	</dl>
	
	

		<style type="text/css">
#prfx_meta{
    overflow: hidden;
    padding-bottom: 1em;
}
 
#prfx_meta p{
    clear: both;
}
 
.prfx-row-title{
    display: block;
    float: left;
    width: 200px;
}
 
.prfx-row-content{
    float: left;
    padding-bottom: 1em;
}
 
.prfx-row-content label{
    display: block;
    line-height: 1.75em;
}		
		
		</style>
 
    <?php
}
/**
 * Saves the custom meta input
 */
function prfx_meta_save( $post_id ) {
 
    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'prfx_nonce' ] ) && wp_verify_nonce( $_POST[ 'prfx_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
    // Exits script depending on save status
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
 

	// Checks for input and saves if needed
	if( isset( $_POST[ 'meta-image' ] ) ) {
		update_post_meta( $post_id, 'meta-image', $_POST[ 'meta-image' ] );
	}
}
add_action( 'save_post', 'prfx_meta_save' );
