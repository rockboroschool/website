<?php
/**
 * Loads the color picker javascript
 */
function prfx_color_enqueue() {
    global $typenow;
    if( $typenow == 'pdfposter' ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'metabox/meta-box-color-js', plugin_dir_url( __FILE__ ) . 'meta-box-color.js', array( 'wp-color-picker' ) );
    }
}
add_action( 'admin_enqueue_scripts', 'prfx_color_enqueue' );

/**
 * Adds the meta box stylesheet when appropriate
 */
function prfx_admin_styles(){
    global $typenow;
    if( $typenow == 'pdfposter' ) {
        wp_enqueue_style( 'prfx_meta_box_styles', plugin_dir_url( __FILE__ ) . 'meta-box-styles.css' );
    }
}
add_action( 'admin_print_styles', 'prfx_admin_styles' );
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
                'title' => __( 'Choose or Upload an Image', 'prfx-textdomain' ),
                'button' => __( 'Use this image', 'prfx-textdomain' ),
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
    add_meta_box( 'prfx_meta', __( 'Set up skype button', 'prfx-textdomain' ), 'prfx_meta_callback', 'pdfposter','normal','high');
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
        <label for="skype_id" class="prfx-row-title"><?php _e( 'Skype ID*', 'prfx-textdomain' )?></label>
        <input type="text" name="skype_id" id="skype_id" value="<?php if ( isset ( $prfx_stored_meta['skype_id'] ) ) echo $prfx_stored_meta['skype_id'][0]; ?>" />
		<code style="font-style:italic;">Enter skype Id here here.</code>
    </p>
	
	
	<p>
		<label for="button_size" class="prfx-row-title"><?php _e( 'Button Size', 'prfx-textdomain' )?></label>
		<select name="button_size" id="button_size">
			<option value="10" <?php if ( isset ( $prfx_stored_meta['button_size'] ) ) selected( $prfx_stored_meta['button_size'][0], '10' ); ?>><?php _e( '10 Px', 'prfx-textdomain' )?></option>';
			<option value="12" <?php if ( isset ( $prfx_stored_meta['button_size'] ) ) selected( $prfx_stored_meta['button_size'][0], '12' ); ?>><?php _e( '12 Px', 'prfx-textdomain' )?></option>';
			<option value="14" <?php if ( isset ( $prfx_stored_meta['button_size'] ) ) selected( $prfx_stored_meta['button_size'][0], '14' ); ?>><?php _e( '14 Px', 'prfx-textdomain' )?></option>';
			<option value="16" <?php if ( isset ( $prfx_stored_meta['button_size'] ) ) selected( $prfx_stored_meta['button_size'][0], '16' ); ?><?php if (!isset($prfx_stored_meta['button_size'])){echo'selected="selected"';}//default value is 16 ?>><?php _e( '16 Px', 'prfx-textdomain' )?></option>';
			<option value="24" <?php if ( isset ( $prfx_stored_meta['button_size'] ) ) selected( $prfx_stored_meta['button_size'][0], '24' ); ?>><?php _e( '24 Px', 'prfx-textdomain' )?></option>';
			<option value="32" <?php if ( isset ( $prfx_stored_meta['button_size'] ) ) selected( $prfx_stored_meta['button_size'][0], '32' ); ?>><?php _e( '32 Px', 'prfx-textdomain' )?></option>';
		</select>
	</p>
	<?php // Radio Buttons----------  ?>
	<p>
    <span class="prfx-row-title"><?php _e( 'Button color', 'prfx-textdomain' )?></span>
    <div class="prfx-row-content">
        <label for="button_color">
            <input type="checkbox" name="button_color" id="button_color" value="white" <?php if ( isset ( $prfx_stored_meta['button_color'] ) ) checked( $prfx_stored_meta['button_color'][0], 'white' ); ?> />
            <?php _e( 'Display White Color Button', 'prfx-textdomain' )?>
        </label>
    </div>
	</p>
	
	
	<?php //End of  Radio Buttons----------  ?>
	<?php //  Select list----------  ?>
	<p>
		<label for="button_type" class="prfx-row-title"><?php _e( 'Button Type', 'prfx-textdomain' )?></label>
		<select name="button_type" id="button_type">
			<option value="call" <?php if ( isset ( $prfx_stored_meta['button_type'] ) ) selected( $prfx_stored_meta['button_type'][0], 'call' ); ?>><?php _e( 'Call', 'prfx-textdomain' )?></option>';
			<option value="chat" <?php if ( isset ( $prfx_stored_meta['button_type'] ) ) selected( $prfx_stored_meta['button_type'][0], 'chat' ); ?>><?php _e( 'Chat', 'prfx-textdomain' )?></option>';
			<option value="dropdown" <?php if ( isset ( $prfx_stored_meta['button_type'] ) ) selected( $prfx_stored_meta['button_type'][0], 'dropdown' ); ?>><?php _e( 'Call+Chat Dropdown', 'prfx-textdomain' )?></option>';
		</select>
	</p>
<center><h5><u>Button Position Setting</u></h5></center>
	
	<?php //End  Select list----------  ?>
    <p>
        <label for="padding_left" class="prfx-row-title"><?php _e( 'Padding Left', 'padding_left' )?></label>
        <input type="number" name="padding_left" id="padding_left" value="<?php if ( isset ( $prfx_stored_meta['padding_left'] ) ) echo $prfx_stored_meta['padding_left'][0]; ?>" /> Px. 
		<code style="font-style:italic;">Enter a integer number</code>
    </p>	
    <p>
        <label for="padding_top" class="prfx-row-title"><?php _e( 'Padding Top', 'prfx-textdomain' )?></label>
        <input type="number" name="padding_top" id="padding_top" value="<?php if ( isset ( $prfx_stored_meta['padding_top'] ) ) echo $prfx_stored_meta['padding_top'][0]; ?>" /> Px. 
		<code style="font-style:italic;">Enter a integer number</code>
    </p>		
	
 
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
 
    // Checks for input and sanitizes/saves if needed
    if( isset( $_POST[ 'skype_id' ] ) ) {
        update_post_meta( $post_id, 'skype_id', sanitize_text_field( $_POST[ 'skype_id' ] ) );
    }
    // Checks for input and sanitizes/saves if needed
    if( isset( $_POST[ 'padding_left' ] ) ) {
        update_post_meta( $post_id, 'padding_left', sanitize_text_field( $_POST[ 'padding_left' ] ) );
    }
    if( isset( $_POST[ 'padding_top' ] ) ) {
        update_post_meta( $post_id, 'padding_top', sanitize_text_field( $_POST[ 'padding_top' ] ) );
    }	
// Select List---------------------------------------------------------------
// Checks for input and saves Button Size
if( isset( $_POST[ 'button_size' ] ) ) {
    update_post_meta( $post_id, 'button_size', $_POST[ 'button_size' ] );
}

// Select List---------------------------------------------------------------
// Checks for input and saves Button Size
if( isset( $_POST[ 'button_type' ] ) ) {
    update_post_meta( $post_id, 'button_type', $_POST[ 'button_type' ] );
}

//Color picker------------------------------------------------------
// Checks for input and saves if needed
if( isset( $_POST[ 'meta-color' ] ) ) {
    update_post_meta( $post_id, 'meta-color', $_POST[ 'meta-color' ] );
}	
// Checks for input and saves
if( isset( $_POST[ 'button_color' ] ) ) {
    update_post_meta( $post_id, 'button_color', 'white' );
} else {
    update_post_meta( $post_id, 'button_color', '' );
}
 
}


add_action( 'save_post', 'prfx_meta_save' );