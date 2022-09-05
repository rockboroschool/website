<?php
/**
* Slider Model / Views / Helpers Class
*
*/
if ( ! class_exists( 'CZR_slider' ) ) :
class CZR_slider {

  static $instance;
  private static $sliders_model;
  static $rendered_sliders;

  function __construct () {
    self::$instance =& $this;
    add_action( 'tc_set_slider_hooks_done' , array( $this, 'czr_fn_maybe_setup_parallax' ) );
    add_action( 'template_redirect'        , array( $this, 'czr_fn_set_slider_hooks' ) );
    //set user customizer options. @since v3.2.0
    add_filter( 'tc_slider_layout_class'   , array( $this , 'czr_fn_set_slider_wrapper_class' ) );
    //! tc_user_options_style filter is shared by several classes => must always check the local context inside the callback before appending new css
    //fired on hook : wp_enqueue_scripts
    //Set thumbnail specific design based on user options
    //Set user defined height
    add_filter( 'tc_user_options_style'    , array( $this , 'czr_fn_write_slider_inline_css' ) );
    //tc_slider_height is fired in CZR_slider::czr_fn_write_slider_inline_css()
    add_filter( 'tc_slider_height'         , array( $this, 'czr_fn_set_demo_slider_height') );
  }//end of construct




  /******************************
  * HOOK SETUP
  *******************************/
  /**
  * callback of template_redirect
  * Set slider hooks
  * @return  void
  */
  function czr_fn_set_slider_hooks() {
    //get slides model
    //extract $slider_name_id, $slides, $layout_class, $img_size
    extract( $this -> czr_fn_get_slider_model() );
    //returns nothing if no slides to display
    if ( ! isset($slides) || ! $slides )
      return;

    add_action( '__after_header'            , array( $this , 'czr_fn_slider_display' ) );
    add_action( '__after_carousel_inner'    , array( $this , 'czr_fn_slider_control_view' ) );

    //adds the center-slides-enabled css class
    add_filter( 'tc_carousel_inner_classes' , array( $this, 'czr_fn_set_inner_class') );

    //adds infos in the caption data of the demo slider
    add_filter( 'tc_slide_caption_data'     , array( $this, 'czr_fn_set_demo_slide_data'), 10, 3 );

    //wrap the slide into a link
    add_filter( 'tc_slide_background'       , array( $this, 'czr_fn_link_whole_slide'), 5, 5 );

    //display an edit deep link to the Slider section in the Customize or post/page
    add_action( '__after_carousel_inner'    , array( $this, 'czr_fn_render_slider_edit_link_view'), 10, 2 );

    //fire event when all the hooks have been set
    do_action( 'tc_set_slider_hooks_done' );
  }

  /******************************
  * MODELS
  *******************************/
  /**
  * Return a single slide model
  * Returns and array of slides with data
  *
  * @package Customizr
  * @since Customizr 3.0.15
  *
  */
  private function czr_fn_get_single_slide_model( $slider_name_id, $_loop_index , $id , $img_size ) {
    //check if slider enabled for this attachment and go to next slide if not
    $slider_checked         = esc_attr(get_post_meta( $id, $key = 'slider_check_key' , $single = true ));
    if ( ! isset( $slider_checked) || $slider_checked != 1 )
      return;

    //title
    $title                  = esc_attr(get_post_meta( $id, $key = 'slide_title_key' , $single = true ));
    $default_title_length   = apply_filters( 'tc_slide_title_length', 80 );
    $title                  = czr_fn_text_truncate( $title, $default_title_length, '...' );

    //lead text
    $text                   = get_post_meta( $id, $key = 'slide_text_key' , $single = true );
    $default_text_length    = apply_filters( 'tc_slide_text_length', 250 );
    $text                   = czr_fn_text_truncate( $text, $default_text_length, '...' );

    //button text
    $button_text            = esc_attr(get_post_meta( $id, $key = 'slide_button_key' , $single = true ));
    $default_button_length  = apply_filters( 'tc_slide_button_length', 80 );
    $button_text            = czr_fn_text_truncate( $button_text, $default_button_length, '...' );

    //link post id
    $link_id                = apply_filters( 'tc_slide_link_id', esc_attr(get_post_meta( $id, $key = 'slide_link_key' , $single = true )), $id, $slider_name_id );
    //link
    $link_url               = esc_url( get_post_meta( $id, $key = 'slide_custom_link_key', $single = true ) );

    if ( ! $link_url )
      $link_url = $link_id ? get_permalink( $link_id ) : $link_url;

    $link_url               = apply_filters( 'tc_slide_link_url', $link_url, $id, $slider_name_id );

    //link target
    $link_target_bool       = esc_attr(get_post_meta( $id, $key= 'slide_link_target_key', $single = true ));
    $link_target            = apply_filters( 'tc_slide_link_target', $link_target_bool ? '_blank' : '_self', $id, $slider_name_id );

    //link the whole slide?
    $link_whole_slide       = apply_filters( 'tc_slide_link_whole_slide', esc_attr(get_post_meta( $id, $key= 'slide_link_whole_slide_key', $single = true )), $id, $slider_name_id );

    //checks if $text_color is set and create an html style attribute
    $text_color             = esc_attr(get_post_meta( $id, $key = 'slide_color_key' , $single = true ));
    $color_style            = ( $text_color != null) ? 'style="color:'.$text_color.'"' : '';

    //attachment image
    $alt                    = apply_filters( 'tc_slide_background_alt' , trim(strip_tags(get_post_meta( $id, '_wp_attachment_image_alt' , true))) );

    $slide_background_attr  = array( 'class' => 'slide' , 'alt' => $alt );

    //allow responsive images?
    if ( version_compare( $GLOBALS['wp_version'], '4.4', '>=' ) )
      if ( 0 == esc_attr( czr_fn_opt('tc_resp_slider_img') ) ) {
        $slide_background_attr['srcset'] = " ";
        //trick, => will produce an empty attr srcset as in wp-includes/media.php the srcset is calculated and added
        //only when the passed srcset attr is not empty. This will avoid us to:
        //a) add a filter to get rid of already computed srcset
        // or
        //b) use preg_replace to get rid of srcset and sizes attributes from the generated html
        //Side effect:
        //we'll see an empty ( or " " depending on the browser ) srcset attribute in the html
        //to avoid this we filter the attributes getting rid of the srcset if any.
        //Basically this trick, even if ugly, will avoid the srcset attr computation
        add_filter( 'wp_get_attachment_image_attributes', array( CZR_post_thumbnails::$instance, 'czr_fn_remove_srcset_attr' ) );
      }
    $slide_background       = wp_get_attachment_image( $id, $img_size, false, $slide_background_attr );

    if ( czr_fn_is_checked( 'tc_slider_img_smart_load' ) ) {
        $slide_background = czr_fn_parse_imgs( $slide_background ); //<- to prepare the img smartload
    }

    //adds all values to the slide array only if the content exists (=> handle the case when an attachment has been deleted for example). Otherwise go to next slide.
    if ( !isset($slide_background) || empty($slide_background) )
      return;

    return array(
      'title'               =>  $title,
      'text'                =>  $text,
      'button_text'         =>  $button_text,
      'link_id'             =>  $link_id,
      'link_url'            =>  $link_url,
      'link_target'         =>  $link_target,
      'link_whole_slide'    =>  $link_whole_slide,
      'active'              =>  ( 0 == $_loop_index ) ? 'active' : '',
      'color_style'         =>  $color_style,
      'slide_background'    =>  $slide_background
    );
  }



  /**
  * Return a single post slide pre model
  * Returns and array of pre slides with data
  *
  * This method will build up the single object model which will be
  * the base for the actual post slide model
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  function czr_fn_get_single_post_slide_pre_model( $_post , $img_size, $args ){
      $ID                     = $_post->ID;

      //attachment image
      $thumb                  = CZR_post_thumbnails::$instance -> czr_fn_get_thumbnail_model(
          $img_size,//$requested_size
          $ID,//post ID
          null,//$_custom_thumb_id
          isset($args['slider_responsive_images']) ? $args['slider_responsive_images'] : null//$_enable_wp_responsive_imgs
      );


      $slide_background       = isset($thumb) && isset($thumb['tc_thumb']) ? $thumb['tc_thumb'] : null;

      // we assign a default thumbnail if needed.
      if ( ! $slide_background ) {
          if ( file_exists( TC_BASE_CHILD . 'inc/assets/img/slide-placeholder.png' ) ) {
              $slide_background = sprintf('<img width="1200" height="500" src="%1$s" class="attachment-slider-full tc-thumb-type-thumb wp-post-image wp-post-image" alt="">',
                  TC_BASE_URL_CHILD . 'inc/assets/img/slide-placeholder.png'
              );
          } else {
              return false;
          }
      }

      //title
      $title                  = ( isset( $args['show_title'] ) && $args['show_title'] ) ? $this -> czr_fn_get_post_slide_title( $_post, $ID) : '';

      //lead text
      $text                   = ( isset( $args['show_excerpt'] ) && $args['show_excerpt'] ) ? $this -> czr_fn_get_post_slide_excerpt( $_post, $ID) : '';

      return compact( 'ID', 'title', 'text', 'slide_background' );
  }


  /**
  * Return a single post slide model
  * Returns and array of slides with data
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  function czr_fn_get_single_post_slide_model( $slider_name_id, $_loop_index , $_post_slide , $common, $img_size ){
    extract( $_post_slide );
    extract( $common );

    //background image
    $slide_background       = apply_filters( 'tc_posts_slide_background', $slide_background, $ID );
    if ( czr_fn_is_checked( 'tc_slider_img_smart_load' ) ) {
        $slide_background = czr_fn_parse_imgs( $slide_background ); //<- to prepare the img smartload
    }


    // we don't want to show slides with no image
    if ( ! $slide_background )
      return false;

    $title                  = apply_filters('tc_posts_slider_title', $title, $ID );
    //lead text
    $text                   = apply_filters('tc_posts_slider_text', $text, $ID );

    //button
    $button_text            = apply_filters('tc_posts_slider_button_text', $button_text, $ID );

    //link
    $link_id                = apply_filters( 'tc_posts_slide_link_id', $ID );

    $link_url               = apply_filters( 'tc_posts_slide_link_url', $link_id ? get_permalink( $link_id ) : '', $ID );

    $link_target            = apply_filters( 'tc_posts_slide_link_target', '_self', $ID );

    $link_whole_slide       = apply_filters( 'tc_posts_slide_link_whole_slide', $link_whole_slide, $ID );

    $active                 = ( 0 == $_loop_index ) ? 'active' : '';
    $color_style            = apply_filters( 'tc_posts_slide_color_style', '', $ID );

    return apply_filters( 'tc_single_post_slide_model', compact(
        'title',
        'text',
        'button_text',
        'link_id',
        'link_url',
        'link_target',
        'link_whole_slide',
        'active',
        'color_style',
        'slide_background'
    ), $ID );
  }




  /**
  * Helper
  * Return an array of the slide models from option or default
  * Returns and array of slides with data
  *
  * @package Customizr
  * @since Customizr 3.0.15
  *
  */
  private function czr_fn_get_the_slides( $slider_name_id, $img_size ) {
    //returns the default slider if requested
    if ( 'demo' == $slider_name_id )
      return apply_filters( 'tc_default_slides', CZR___::$instance -> default_slides );
    else if ( 'tc_posts_slider' == $slider_name_id ) {
      return $this -> czr_fn_get_the_posts_slides( $slider_name_id, $img_size );
    }
    //if not demo or tc_posts_slider, we get slides from options
    $all_sliders    = czr_fn_opt( 'tc_sliders');
    $saved_slides   = ( isset($all_sliders[$slider_name_id]) ) ? $all_sliders[$slider_name_id] : false;

    //if the slider not longer exists or exists but is empty, return false
    if ( ! $this -> czr_fn_slider_exists( $saved_slides) )
      return;

    //inititalize the slides array
    $slides   = array();

    //init slide active state index
    $_loop_index        = 0;

    //GENERATE SLIDES ARRAY
    foreach ( $saved_slides as $s ) {
      $slide_object           = get_post($s);
      //next loop if attachment does not exist anymore (has been deleted for example)
      if ( ! isset( $slide_object) )
        continue;

      $id                     = $slide_object -> ID;

      $slide_model = $this -> czr_fn_get_single_slide_model( $slider_name_id, $_loop_index, $id, $img_size);

      if ( ! $slide_model )
        continue;

      $slides[$id] = $slide_model;

      $_loop_index++;
    }//end of slides loop

    //returns the slides or false if nothing
    return apply_filters('tc_the_slides', ! empty($slides) ? $slides : false );
  }



  /**
  * Helper
  * Return an array of the post slide models
  * Returns and array of slides with data
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  /* Steps;
  * 1) get the pre slides model
  * 2) get the actual model from the pre_model
  *
  *
  *
  * The difference between the pre_model and the actual model is because in the
  * transient we do not store the whole slide model, some info are not needed.
  * Also the actual model can be filtered, allowing user's filter and to translate it
  * mostly with qtranslate (polylang will force us, most likely if I don't find any
  * other suitable solution, to not use the transient).
  */
  private function czr_fn_get_the_posts_slides( $slider_name_id, $img_size ) {


    $pre_slides      = $this -> czr_fn_get_pre_posts_slides( array( 'img_size' => $img_size ) );

    //filter the pre_model
    $pre_slides      = apply_filters( 'tc_posts_slider_pre_model', $pre_slides );

    //if the slider no longer exists or exists but is empty, return false
    if ( ! $this -> czr_fn_slider_exists( $pre_slides ) )
      return false;

    //extract pre_slides model
    extract($pre_slides);

    //inititalize the slides array
    $slides      = array();

    $_loop_index = 0;

    //GENERATE SLIDES ARRAY
    foreach ( $posts as $_post_slide ) {
      $slide_model = $this -> czr_fn_get_single_post_slide_model( $slider_name_id, $_loop_index, $_post_slide, $common, $img_size);

      if ( ! $slide_model )
        continue;

      $slides[ $_post_slide['ID'] ] = $slide_model;

      $_loop_index++;
    }//end of slides loop

    //returns the slides or false if nothing
    return apply_filters('tc_the_posts_slides', ! empty($slides) ? $slides : false );

  }



  /**
  * Helper
  * Return an as array of 'posts'=> array of the post slide pre models 'common' => common properties
  *
  *
  * - eventually store the transient
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  private function czr_fn_get_pre_posts_slides( $args ){

    $defaults       = array(
      'img_size'            => null,
      //options
      'stickies_only'       => esc_attr( czr_fn_opt( 'tc_posts_slider_stickies' ) ),
      'show_title'          => esc_attr( czr_fn_opt( 'tc_posts_slider_title' ) ),
      'show_excerpt'        => esc_attr( czr_fn_opt( 'tc_posts_slider_text' ) ),
      'button_text'         => esc_attr( czr_fn_opt( 'tc_posts_slider_button_text' ) ),
      'posts_per_page'      => esc_attr( czr_fn_opt( 'tc_posts_slider_number' ) ), //limit
      'link_type'           => esc_attr( czr_fn_opt( 'tc_posts_slider_link') ),
    );

    $args         = apply_filters( 'tc_get_pre_posts_slides_args', wp_parse_args( $args, $defaults ) );
    extract( $args );

    //retrieve posts from the db
    $queried_posts    = $this -> czr_fn_query_posts_slider( $args );

    if ( empty ( $queried_posts ) )
      return array();

    /*** tc_thumb setup filters ***/
    // remove smart load img parsing if any
    $smart_load_enabled = 1 == esc_attr( czr_fn_opt( 'tc_img_smart_load' ) );
    if ( $smart_load_enabled )
      remove_filter( 'tc_thumb_html', 'czr_fn_parse_imgs' );

    // prevent adding thumb inline style when no center img is added
    add_filter( 'tc_post_thumb_inline_style', '__return_empty_string', 100 );

    //Allow retrieving first attachment as thumb
    add_filter( 'tc_use_attachment_as_thumb', '__return_true', 100 );
    /*** end tc_thumb setup ***/

    //allow responsive images?
    if ( version_compare( $GLOBALS['wp_version'], '4.4', '>=' ) )
      $args['slider_responsive_images'] = 0 == czr_fn_opt('tc_resp_slider_img') ? false : true;

    /* Get the pre_model */
    $pre_slides = $pre_slides_posts = array();

    foreach ( $queried_posts as $_post ) {
      $pre_slide_model = $this ->  czr_fn_get_single_post_slide_pre_model( $_post , $img_size, $args );

      if ( ! $pre_slide_model )
        continue;

      $pre_slides_posts[] = $pre_slide_model;
    }

    /* tc_thumb reset filters */
    // re-add smart load parsing if removed
    if ( $smart_load_enabled )
      add_filter('tc_thumb_html', 'czr_fn_parse_imgs' );

    // remove thumb style reset
    remove_filter( 'tc_post_thumb_inline_style', '__return_empty_string', 100 );

    // remove forced retrieval first attachment as thumb;
    remove_filter( 'tc_use_attachment_as_thumb', '__return_true', 100 );
    /* end tc_thumb reset filters */

    if ( ! empty( $pre_slides_posts ) ) {
      /*** Setup shared properties ***/
      /* Shared by all post slides, stored in the "common" field */
      // button and link whole slide
      // has button to be displayed?
      if ( strstr($link_type, 'cta') )
        $button_text            = $this -> czr_fn_get_post_slide_button_text( $button_text );
      else
        $button_text            = '';

      //link the whole slide?
      $link_whole_slide       = strstr( $link_type, 'slide') ? true : false;
      /*** end Setup shared properties ***/


      /* Add common and posts to the actual pre_slides array */
      $pre_slides['common']  = compact( 'button_text', 'link_whole_slide');
      $pre_slides['posts']   = $pre_slides_posts;

    }

    return $pre_slides;
  }



  /**
  * return the slider block model
  * @return  array($slider_name_id, $slides, $layout_class)
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  private function czr_fn_get_slider_model() {
    //Do we have a slider to display in this context ?
    if ( ! $this -> czr_fn_is_slider_possible() )
      return array();

    //gets the actual page id if we are displaying the posts page
    $queried_id                   = $this -> czr_fn_get_real_id();

    $slider_name_id               = $this -> czr_fn_get_current_slider( $queried_id );

    if ( ! $this -> czr_fn_is_slider_active( $queried_id) )
      return array();

    if ( ! empty( self::$sliders_model ) && is_array( self::$sliders_model ) && array_key_exists( $slider_name_id, self::$sliders_model ) )
      return self::$sliders_model[ $slider_name_id ];

    //gets slider options if any
    $layout_value                 = czr_fn__f('__is_home') ? czr_fn_opt( 'tc_slider_width' ) : esc_attr(get_post_meta( $queried_id, $key = 'slider_layout_key' , $single = true ));
    $layout_value                 = apply_filters( 'tc_slider_layout', $layout_value, $queried_id );

    //declares the layout vars
    $layout_class                 = ( 0 == $layout_value ) ? array('container', 'carousel', 'customizr-slide', $slider_name_id ) : array('carousel', 'customizr-slide', $slider_name_id);
    $img_size                     = apply_filters( 'tc_slider_img_size' , ( 0 == $layout_value ) ? 'slider' : 'slider-full');

    //get slides
    $slides                       = $this -> czr_fn_get_the_slides( $slider_name_id , $img_size );

    //store the model per slider_name_id
    self::$sliders_model[ $slider_name_id ] = compact( "slider_name_id", "slides", "layout_class" , "img_size" );

    return self::$sliders_model[ $slider_name_id ];
  }




  /**
  * Helper
  * Returns the array of eligible posts for the slider of posts
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  private function czr_fn_query_posts_slider( $args = array() ) {

    $defaults       = array(
      'stickies_only'   => 0,
      'post_status'     => 'publish',
      'post_type'       => 'post',
      'orderby'         => 'date',
      'order'           => 'DESC',
      'posts_per_page'  => 5,
      'offset'          => 0,
      'suppress_filters' => false, // <- for language plugins
    );

    $args           = apply_filters( 'tc_query_posts_slider_args', wp_parse_args( $args, $defaults ) );
    $_posts         = false;

    if ( is_array($args) && !empty($args) && array_key_exists( 'posts_per_page', $args) && $args['posts_per_page'] > 0 ) {

      // Do we have to show only sticky posts?
      if ( array_key_exists( 'stickies_only', $args) && $args['stickies_only'] ) {
        // Are there sticky posts?
        $_sticky_posts  = get_option('sticky_posts');
        if ( ! empty( $_sticky_posts ) ) {
          $args = array_merge( $args, array( 'post__in' => $_sticky_posts  ) );
        }
        else {
          $args = false;
        }
      }

      if ( !empty($args) )
        $_posts = get_posts( $args );
    }

    return apply_filters( 'tc_query_posts_slider', $_posts, $args );
  }



  /******************************
  * VIEWS
  *******************************/
  /**
  * Slider View
  * Displays the slider based on the context : home, post/page.
  * hook : __after_header
  * @package Customizr
  * @since Customizr 1.0
  *
  */
  function czr_fn_slider_display() {
    //get slides model
    //extract $slider_name_id, $slides, $layout_class, $img_size
    extract( $this -> czr_fn_get_slider_model() );
    //returns nothing if no slides to display
    if ( ! isset($slides) || ! $slides )
      return;

    self::$rendered_sliders++ ;

    //define carousel inner classes and attributes
    $_inner_classes    = implode( ' ' , apply_filters( 'tc_carousel_inner_classes' , array( 'carousel-inner' ) ) );
    $_layout_classes   = implode( ' ' , apply_filters( 'tc_slider_layout_class' , $layout_class ) );
    $_inner_attributes = implode( ' ' , apply_filters( 'tc_carousel_inner_attributes' , array() ) );

    ob_start();
    ?>
    <div id="customizr-slider-<?php echo self::$rendered_sliders ?>" class="<?php echo $_layout_classes ?>">

      <?php $this -> czr_fn_render_slider_loader_view( $slider_name_id ); ?>

      <?php do_action( '__before_carousel_inner' , $slides, $slider_name_id )  ?>

      <div class="<?php echo $_inner_classes?>" <?php echo $_inner_attributes ?>>
        <?php
          foreach ($slides as $id => $data) {
            $_view_model = compact( "id", "data" , "slider_name_id", "img_size" );
            $this -> czr_fn_render_single_slide_view( $_view_model );
          }
        ?>
      </div><!-- /.carousel-inner -->

      <?php  do_action( '__after_carousel_inner' , $slides, $slider_name_id )  ?>

    </div><!-- /#customizr-slider -->

    <?php
    $html = ob_get_contents();
    if ($html) ob_end_clean();
    echo apply_filters( 'tc_slider_display', $html, $slider_name_id );
  }



  /**
  * Single slide view
  * Renders a single slide
  * @param $_view_model = array( $id, $data , $slider_name_id, $img_size )
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  function czr_fn_render_single_slide_view( $_view_model ) {
    //extract $_view_model = array( $id, $data , $slider_name_id, $img_size )
    extract( $_view_model );

    $slide_classes = implode( ' ', apply_filters( 'tc_single_slide_item_classes', array( 'czr-item', $data['active'], "slide-{$id}" ) ) );
    ?>
    <div class="<?php echo $slide_classes; ?>">
      <?php
        $this -> czr_fn_render_slide_background_view( $_view_model );
        $this -> czr_fn_render_slide_caption_view( $_view_model );
        $this -> czr_fn_render_slide_edit_link_view( $_view_model );
      ?>
    </div><!-- /.czr-item -->
    <?php
  }


  /**
  * Slider loader view
  * This feature is only fired in browser with js enabled => cf the embedded script
  * @param (string) $slider_name_id
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  function czr_fn_render_slider_loader_view( $slider_name_id ) {
    if ( ! $this -> czr_fn_is_slider_loader_active( $slider_name_id ) )
      return;

    if ( ! apply_filters( 'tc_slider_loader_gif_only', false ) )
      $_pure_css_loader = sprintf( '<div class="tc-css-loader %1$s">%2$s</div>',
            implode( ' ', apply_filters( 'tc_pure_css_loader_add_classes', array( 'tc-mr-loader') ) ),
            apply_filters( 'tc_pure_css_loader_inner', '<div></div><div></div><div></div>')
      );
    else
      $_pure_css_loader = '';
    ?>
      <div id="tc-slider-loader-wrapper-<?php echo self::$rendered_sliders ?>" class="tc-slider-loader-wrapper">
        <div class="tc-img-gif-loader"></div>
        <?php echo $_pure_css_loader; ?>
      </div>
    <?php
  }



  /**
  * Slide Background subview
  * @param $_view_model = array( $id, $data , $slider_name_id, $img_size )
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  function czr_fn_render_slide_background_view( $_view_model ) {
    //extract $_view_model = array( $id, $data , $slider_name_id, $img_size )
    extract( $_view_model );
    ?>
    <div class="<?php echo apply_filters( 'tc_slide_content_class', sprintf('carousel-image %1$s' , $img_size ) ); ?>">
      <?php
        do_action('__before_all_slides');
        do_action_ref_array ("__before_slide_{$id}" , array( $data['slide_background'], $data['link_url'], $id, $slider_name_id, $data ) );

          echo apply_filters( 'tc_slide_background', $data['slide_background'], $data['link_url'], $id, $slider_name_id, $data );

        do_action_ref_array ("__after_slide_{$id}" , array( $data['slide_background'], $data['link_url'], $id, $slider_name_id, $data ) );
        do_action('__after_all_slides');
      ?>
    </div> <!-- .carousel-image -->
    <?php
  }

  /**
  *
  * Link whole slide
  * hook: tc_slide_background
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  function czr_fn_link_whole_slide( $slide_background, $link_url, $id, $slider_name_id, $data ) {
    if ( isset( $data['link_whole_slide'] )  && $data['link_whole_slide'] && $link_url )
      $slide_background = sprintf('<a href="%1$s" class="tc-slide-link" target="%2$s"></a>%3$s',
                                $link_url,
                                $data['link_target'],
                                $slide_background
      );
    return $slide_background;
  }

  /**
  * Slide caption subview
  * @param $_view_model = array( $id, $data , $slider_name_id, $img_size )
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  function czr_fn_render_slide_caption_view( $_view_model ) {
    //extract $_view_model = array( $id, $data , $slider_name_id, $img_size )
    extract( $_view_model );
    //filters the data before (=> used for demo for example )
    $data                   = apply_filters( 'tc_slide_caption_data', $data, $slider_name_id, $id );

    $show_caption           = ! ( $data['title'] == null && $data['text'] == null && $data['button_text'] == null ) ;
    if ( ! apply_filters( 'tc_slide_show_caption', $show_caption , $slider_name_id ) )
      return;

    //apply filters first
    $data['title']          = isset($data['title']) ? apply_filters( 'tc_slide_title', $data['title'] , $id, $slider_name_id ) : '';
    $data['text']           = isset($data['text']) ? esc_html( apply_filters( 'tc_slide_text', $data['text'], $id, $slider_name_id ) ) : '';
    $data['color_style']    = apply_filters( 'tc_slide_color', $data['color_style'], $id, $slider_name_id );


    $data['button_text']    = isset($data['button_text']) ? apply_filters( 'tc_slide_button_text', $data['button_text'], $id, $slider_name_id ) : '';

    //computes the link
    $button_link            = apply_filters( 'tc_slide_button_link', $data['link_url'] ? $data['link_url'] : 'javascript:void(0)', $id, $slider_name_id );

    printf('<div class="%1$s">%2$s %3$s %4$s</div>',
      //class
      implode( ' ', apply_filters( 'tc_slide_caption_class', array( 'carousel-caption' ), $show_caption, $slider_name_id ) ),
      //title
      ( apply_filters( 'tc_slide_show_title', $data['title'] != null, $slider_name_id ) ) ? sprintf('<%1$s class="%2$s" %3$s>%4$s</%1$s>',
                            apply_filters( 'tc_slide_title_tag', 'h1', $slider_name_id ),
                            implode( ' ', apply_filters( 'tc_slide_title_class', array( 'slide-title' ), $data['title'], $slider_name_id ) ),
                            $data['color_style'],
                            $data['title']
                          ) : '',
      //lead text
      ( apply_filters( 'tc_slide_show_text', $data['text'] != null, $slider_name_id ) ) ? sprintf('<p class="%1$s" %2$s>%3$s</p>',
                            implode( ' ', apply_filters( 'tc_slide_text_class', array( 'lead' ), $data['text'], $slider_name_id ) ),
                            $data['color_style'],
                            $data['text']
                          ) : '',
      //button call to action
      ( apply_filters( 'tc_slide_show_button', $data['button_text'] != null, $slider_name_id ) ) ? sprintf('<a class="%1$s" href="%2$s" target="%3$s">%4$s</a>',
                            implode( ' ', apply_filters( 'tc_slide_button_class', array( 'btn', 'btn-large', 'btn-primary' ), $data['button_text'], $slider_name_id ) ),
                            $button_link,
                            $data['link_target'],
                            $data['button_text']
                          ) : ''
    );
  }



  /**
  * Slide edit link subview
  * @param $_view_model = array( $id, $data , $slider_name_id, $img_size )
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  function czr_fn_render_slide_edit_link_view( $_view_model ) {
    //never display when customizing
    if ( czr_fn_is_customizing() )
      return;
    //extract $_view_model = array( $id, $data , $slider_name_id, $img_size )
    extract( $_view_model );

    //display edit link for logged in users with  edit_post capabilities
    //upload_files cap isn't a good lower limit 'cause for example and Author can upload_files but actually cannot edit medias he/she hasn't uploaded

    $show_slide_edit_link  = ( is_user_logged_in() && current_user_can( 'edit_post', $id ) ) ? true : false;
    $show_slide_edit_link  = apply_filters('tc_show_slide_edit_link' , $show_slide_edit_link && ! is_null($data['link_id']), $id );

    if ( ! $show_slide_edit_link )
      return;

    $_edit_link_suffix     = 'tc_posts_slider' == $slider_name_id ? '' : '#slider_sectionid';
    //in case of tc_posts_slider the $id is the *post* id, otherwise it's the attachment id
    $_edit_link            = get_edit_post_link($id) . $_edit_link_suffix;

    printf('<span class="slider edit-link btn btn-inverse"><a class="post-edit-link" href="%1$s" title="%2$s" target="_blank">%2$s</a></span>',
      $_edit_link,
      __( 'Edit' , 'customizr' )
    );
  }

  /**
  * Slider Edit deeplink
  * @param $slides, array of slides
  * @param $slider_name_id string, the name of the current slider
  *
  * hook : __after_carousel_inner
  * @since v3.4.9
  */

  function czr_fn_render_slider_edit_link_view( $slides, $slider_name_id ) {
    //never display when customizing
    if ( czr_fn_is_customizing() )
      return;
    if ( 'demo' == $slider_name_id )
      return;

    $show_slider_edit_link    = false;

    //We have to show the slider edit link to
    //a) users who can edit theme options for the slider in home -> deep link in the customizer
    //b) users who can edit the post/page where the slider is displayed for users who can edit the post/page -> deep link in the post/page slider section
    if ( czr_fn__f('__is_home') ){
      $show_slider_edit_link = ( is_user_logged_in() && current_user_can('edit_theme_options') ) ? true : false;
      $_edit_link            = czr_fn_get_customizer_url( array( 'control' => 'tc_front_slider', 'section' => 'frontpage_sec') );
    }else if ( is_singular() ){ // we have a snippet to display sliders in categories, we don't want the slider edit link displayed there
      global $post;
      $show_slider_edit_link = ( is_user_logged_in() && ( current_user_can('edit_pages') || current_user_can( 'edit_posts', $post -> ID ) ) ) ? true : false;
      $_edit_link            = get_edit_post_link( $post -> ID ) . '#slider_sectionid';
    }

    $show_slider_edit_link = apply_filters( 'tc_show_slider_edit_link' , $show_slider_edit_link, $slider_name_id );
    if ( ! $show_slider_edit_link )
      return;

    // The posts slider shows a different text
    $_text                   = sprintf( __( 'Customize or remove %s' , 'customizr' ),
                                  ( 'tc_posts_slider' == $slider_name_id ) ?__('the posts slider', 'customizr') : __('this slider', 'customizr' )
                              );
    printf('<span class="slider deep-edit-link edit-link btn btn-inverse"><a class="slider-edit-link" href="%1$s" title="%2$s" target="_blank">%2$s</a></span>',
      $_edit_link,
      $_text
    );
  }


  /*
  * Slider controls view
  * @param slides
  * @hook : __after_carousel_inner
  * @since v3.2.0
  *
  */
  function czr_fn_slider_control_view( $_slides ) {
    if ( count( $_slides ) <= 1 )
      return;

    if ( ! apply_filters('tc_show_slider_controls' , ! wp_is_mobile() ) )
      return;

    $_html = '';
    $_html .= sprintf('<div class="tc-slider-controls %1$s">%2$s</div>',
      ! is_rtl() ? 'left' : 'right',
      sprintf('<a class="tc-carousel-control" href="#customizr-slider-%2$s" data-slide="prev">%1$s</a>',
        apply_filters( 'tc_slide_left_control', '&lsaquo;' ),
        self::$rendered_sliders
      )
    );
    $_html .= sprintf('<div class="tc-slider-controls %1$s">%2$s</div>',
      ! is_rtl() ? 'right' : 'left',
      sprintf('<a class="tc-carousel-control" href="#customizr-slider-%2$s" data-slide="next">%1$s</a>',
        apply_filters( 'tc_slide_right_control', '&rsaquo;' ),
        self::$rendered_sliders
      )
    );
    echo apply_filters( 'tc_slider_control_view', $_html );
  }







  /******************************
  * PARALLAX
  *******************************/
  //hook : wp
  //introduced in v3.4.23
  function czr_fn_maybe_setup_parallax() {
    if ( 1 != esc_attr( czr_fn_opt( 'tc_slider_parallax') ) )
      return;

    add_filter( 'tc_slider_layout_class'       , array( $this, 'czr_fn_add_parallax_wrapper_class' ) );
    add_filter( 'tc_carousel_inner_classes'    , array( $this, 'czr_fn_add_parallax_item_class' ) );
    add_filter( 'tc_carousel_inner_attributes' , array( $this, 'czr_fn_add_parallax_item_data_attributes' ) );
  }

  //hook : tc_carousel_inner_classes
  function czr_fn_add_parallax_item_class ( $classes ) {
    array_push($classes, 'czr-parallax-slider' );
    return $classes;
  }

  //hook : tc_slider_layout_class
  function czr_fn_add_parallax_wrapper_class( $classes ) {
    array_push($classes, 'parallax-wrapper' );
    return $classes;
  }


  function czr_fn_add_parallax_item_data_attributes ( $attributes ) {
    array_push( $attributes, sprintf( 'data-parallax-ratio="%s"', apply_filters('tc_parallax_speed', 0.55 ) ) );
    return $attributes;
  }


  /******************************
  * HELPERS / SETTERS / CALLBACKS
  *******************************/
  /**
  * Returns the modified caption data array with a link to the doc
  * Only displayed for the demo slider and logged in users
  * hook : tc_slide_caption_data
  *
  * @package Customizr
  * @since Customizr 3.3.+
  *
  */
  function czr_fn_set_demo_slide_data( $data, $slider_name_id, $id ) {
    if ( 'demo' != $slider_name_id || ! is_user_logged_in() )
      return $data;

    switch ( $id ) {
      case 1 :
        //$data['title']        = __( 'Discover how to replace or remove this demo slider.', 'customizr' );
        $data['link_url']     = esc_url( 'docs.presscustomizr.com/article/175-first-steps-with-the-customizr-wordpress-theme' );
        $data['button_text']  = __( 'Discover the Customizr WordPress theme &raquo;' , 'customizr');
      break;

      case 2 :
        $data['title']        = __( 'Discover how to replace or remove this demo slider.', 'customizr' );
        $data['link_url']     = esc_url( 'docs.presscustomizr.com/article/102-customizr-theme-options-front-page#front-page-slider' );
        $data['button_text']  = __( 'Check the slider doc now &raquo;' , 'customizr');
      break;
    };

    $data['link_target'] = '_blank';
    return $data;
  }



  /**
  * Helper
  * @return  boolean
  *
  * @package Customizr
  * @since Customizr 3.3+
  *
  */
  private function czr_fn_is_slider_possible() {
    //gets the front slider if any
    $tc_front_slider              = esc_attr(czr_fn_opt( 'tc_front_slider' ) );
    //when do we display a slider? By default only for home (if a slider is defined), pages and posts (including custom post types)
    $_show_slider = czr_fn__f('__is_home') ? ! empty( $tc_front_slider ) : ! is_404() && ! is_archive() && ! is_search();

    return apply_filters( 'tc_show_slider' , $_show_slider );
  }


  /**
  * helper
  * @return  boolean
  *
  * @package Customizr
  * @since Customizr 3.4.9
  */
  function czr_fn_slider_exists( $slider ){
    //if the slider not longer exists or exists but is empty, return false
    return ! ( !isset($slider) || !is_array($slider) || empty($slider) );
  }


  /**
  * helper
  * returns the slider name id
  * @return  string
  *
  */
  private function czr_fn_get_current_slider($queried_id) {
    //gets the current slider id
    $_home_slider     = czr_fn_opt( 'tc_front_slider' );
    $slider_name_id   = ( czr_fn__f('__is_home') && $_home_slider ) ? $_home_slider : esc_attr( get_post_meta( $queried_id, $key = 'post_slider_key' , $single = true ) );
    return apply_filters( 'tc_slider_name_id', $slider_name_id , $queried_id);
  }


  /**
  * helper
  * returns the actual page id if we are displaying the posts page
  * @return  number
  *
  */
  private function czr_fn_get_real_id() {
    global $wp_query;
    $queried_id                   = czr_fn_get_id();

    return apply_filters( 'tc_slider_get_real_id', ( ! czr_fn__f('__is_home') && ! empty($queried_id) ) ?  $queried_id : get_the_ID() );
  }


  /**
  * helper
  * returns the actual page id if we are displaying the posts page
  * @return  boolean
  *
  */
  private function czr_fn_is_slider_active( $queried_id ) {
    //is the slider set to on for the queried id?
    if ( czr_fn__f('__is_home') && czr_fn_opt( 'tc_front_slider' ) )
      return apply_filters( 'tc_slider_active_status', true , $queried_id );

    $_slider_on = esc_attr( get_post_meta( $queried_id, $key = 'post_slider_check_key' , $single = true ) );
    if ( ! empty( $_slider_on ) && $_slider_on )
      return apply_filters( 'tc_slider_active_status', true , $queried_id );

    return apply_filters( 'tc_slider_active_status', false , $queried_id );
  }

  /**
  * helper
  * returns whether or not the slider loading icon must be displayed
  * @return  boolean
  *
  */
  private function czr_fn_is_slider_loader_active( $slider_name_id ) {
    //The slider loader must be printed when
    //a) we have to render the demo slider
    //b) display slider loading option is enabled (can be filtered)
    return ( 'demo' == $slider_name_id
        || apply_filters( 'tc_display_slider_loader', 1 == esc_attr( czr_fn_opt( 'tc_display_slide_loader') ), $slider_name_id )
    );
  }


  /**
  * hook : tc_slider_height, fired in tc_user_options_style
  * @return number height value
  *
  * @package Customizr
  * @since Customizr 3.3+
  */
  function czr_fn_set_demo_slider_height( $_h ) {
    //this custom demo height is applied when :
    //1) current slider is demo
    if ( 'demo' != $this -> czr_fn_get_current_slider( $this -> czr_fn_get_real_id() ) )
      return $_h;

    //2) height option has not been changed by user yet
    //the possible customization context must be taken into account here
    if ( czr_fn_is_customizing() ) {
      if ( 500 != esc_attr( czr_fn_opt( 'tc_slider_default_height') ) )
        return $_h;
    } else {
      if ( false !== (bool) esc_attr( czr_fn_opt( 'tc_slider_default_height', CZR___::$tc_option_group, $use_default = false ) ) )
        return $_h;
    }
    return apply_filters( 'tc_set_demo_slider_height' , 750 );
  }


  /**
  * Callback of tc_user_options_style hook
  * @return css string
  *
  * @package Customizr
  * @since Customizr 3.2.6
  */
  function czr_fn_write_slider_inline_css( $_css ) {
    //custom css for the slider loader
    if ( $this -> czr_fn_is_slider_loader_active( $this -> czr_fn_get_current_slider( $this -> czr_fn_get_real_id() ) ) ) {
      $_slider_loader_visibility_css = ".tc-slider-loader-wrapper{ display:none }\nhtml.js .tc-slider-loader-wrapper { display: block }";

      $_slider_loader_src = apply_filters( 'tc_slider_loader_src' , sprintf( '%1$s%2$s' , TC_BASE_URL , 'assets/front/img/slider-loader.gif') );
      //we can load only the gif, or use it as fallback for old browsers (.no-csstransforms3d)
      if ( ! apply_filters( 'tc_slider_loader_gif_only', false ) ) {
        $_slider_loader_gif_class  = '.no-csstransforms3d';
        // The pure css loader color depends on the skin. Why can we do this here without caring of the live preview?
        // Basically 'cause the loader is something we see when the page "loads" then it disappears so a live change of the skin
        // will still have no visive impact on it. This will avoid us to rebuild the custom skins.
        $_current_skin_colors      = CZR_utils::$inst -> czr_fn_get_skin_color( 'pair' );
        $_pure_css_loader_css      = apply_filters( 'tc_slider_loader_css', sprintf(
            '.tc-slider-loader-wrapper .tc-css-loader > div { border-color:%s; }',
            //we can use the primary or the secondary skin color
            'primary' == apply_filters( 'tc_slider_loader_color', 'primary') ? $_current_skin_colors[0] : $_current_skin_colors[1]
        ));
      }else {
        $_slider_loader_gif_class = '';
        $_pure_css_loader_css     = '';
      }

      $_slider_loader_gif_css     = $_slider_loader_src ? sprintf(
                                        '%1$s .tc-slider-loader-wrapper .tc-img-gif-loader {
                                                background: url(\'%2$s\') no-repeat center center;
                                         }',
                                         $_slider_loader_gif_class,
                                         $_slider_loader_src
                                     ) : '';
      $_css = sprintf( "$_css\n%s%s%s",
                          $_slider_loader_visibility_css,
                          $_slider_loader_gif_css,
                          $_pure_css_loader_css
      );
    }//end custom css for the slider loader

    // 1) Do we have a custom height ?
    // 2) check if the setting must be applied to all context
    $_custom_height     = apply_filters( 'tc_slider_height' , esc_attr( czr_fn_opt( 'tc_slider_default_height') ) );
    $_slider_inline_css = "";

    //When shall we append custom slider style to the global custom inline stylesheet?
    $_bool = 500 != $_custom_height;
    $_bool = $_bool && ( czr_fn__f('__is_home') || czr_fn_is_checked( 'tc_slider_default_height_apply_all' ) );

    if ( ! apply_filters( 'tc_print_slider_inline_css' , $_bool ) )
      return $_css;

    $_resp_shrink_ratios = apply_filters( 'tc_slider_resp_shrink_ratios',
      array('1200' => 0.77 , '979' => 0.618, '480' => 0.38 , '320' => 0.28 )
    );

    $_slider_inline_css = "
      .carousel .czr-item {
        line-height: {$_custom_height}px;
        min-height:{$_custom_height}px;
        max-height:{$_custom_height}px;
      }
      .tc-slider-loader-wrapper {
        line-height: {$_custom_height}px;
        height:{$_custom_height}px;
      }
      .carousel .tc-slider-controls {
        line-height: {$_custom_height}px;
        max-height:{$_custom_height}px;
      }\n";

    foreach ( $_resp_shrink_ratios as $_w => $_ratio) {
      if ( ! is_numeric($_ratio) )
        continue;
      $_item_dyn_height     = $_custom_height * $_ratio;
      $_caption_dyn_height  = $_custom_height * ( $_ratio - 0.1 );
      $_slider_inline_css .= "
        @media (max-width: {$_w}px) {
          .carousel .czr-item {
            line-height: {$_item_dyn_height}px;
            max-height:{$_item_dyn_height}px;
            min-height:{$_item_dyn_height}px;
          }
          .czr-item .carousel-caption {
            max-height: {$_caption_dyn_height}px;
            overflow: hidden;
          }
          .carousel .tc-slider-loader-wrapper {
            line-height: {$_item_dyn_height}px;
            height:{$_item_dyn_height}px;
          }
        }\n";
    }//end foreach

    return sprintf("%s\n%s", $_css, $_slider_inline_css);
  }



  /**
  * Set slider wrapper class
  * hook : tc_slider_layout_class filter
  *
  * @package Customizr
  * @since Customizr 3.2.0
  *
  */
  function czr_fn_set_slider_wrapper_class($_classes) {
    if ( ! is_array($_classes) || 500 == esc_attr( czr_fn_opt( 'tc_slider_default_height') ) )
      return $_classes;

    return array_merge( $_classes , array('custom-slider-height') );
  }


  /**
  * hook : tc_carousel_inner_classes fired in the slider view
  * @return  array of css classes
  *
  * @package Customizr
  * @since Customizr 3.3+
  */
  function czr_fn_set_inner_class( $_classes ) {
    if( ! (bool) esc_attr( czr_fn_opt( 'tc_center_slider_img') ) || ! is_array($_classes) )
      return $_classes;
    array_push( $_classes, 'center-slides-enabled' );
    return $_classes;
  }



  /**
  * Getter
  * Returns the trimmed post slide title
  *
  * @return string
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  function czr_fn_get_post_slide_title( $_post, $ID ) {
    $title_length   = apply_filters('tc_post_slide_title_length', 80, $ID );
    $more           = apply_filters('tc_post_slide_more', '...', $ID );
    return $this -> czr_fn_get_post_title( $_post, $title_length, $more );
  }


  /**
  * Getter
  * Returns the trimmed post slide excerpt
  *
  * @return string
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  function czr_fn_get_post_slide_excerpt( $_post, $ID ) {
    $excerpt_length  = apply_filters( 'tc_post_slide_text_length', 80, $ID );
    $more            = apply_filters( 'tc_post_slide_more', '...', $ID );
    return $this -> czr_fn_get_post_excerpt( $_post, $excerpt_length, $more );
  }

  /**
  * Getter
  * Returns the trimmed posts slider button text
  *
  * @return string
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  function czr_fn_get_post_slide_button_text( $button_text ) {
    $button_text_length  = apply_filters( 'tc_posts_slider_button_text_length', 80 );
    $more                = apply_filters( 'tc_post_slide_more', '...');
    $button_text         = apply_filters( 'tc_posts_slider_button_text_pre_trim' , $button_text );
    return czr_fn_text_truncate( $button_text, $button_text_length, $more );
  }

  /**
  * Helper
  * Returns the trimmed post title
  *
  * @return string
  *
  * Slightly different and simplified version of get_the_title to avoid conflicts with plugins filtering the_title
  * and custom trimming.
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  // move this into CZR_utils?
  function czr_fn_get_post_title( $_post, $default_title_length, $more ) {
    $title = $_post->post_title;
    if ( ! empty( $_post->post_password ) ) {
      $protected_title_format = apply_filters( 'protected_title_format', __( 'Protected: %s', 'customizr' ), $_post);
      $title = sprintf( $protected_title_format, $title );
    }

    $title = apply_filters( 'tc_post_title_pre_trim' , $title );
    return czr_fn_text_truncate( $title, $default_title_length, $more);
  }


  /**
  * Helper
  * Returns the trimmed post excerpt
  *
  * @return string
  *
  * Slightly different and simplified version of wp_trim_excerpt to avoid conflicts with plugins filtering get_excerpt and the_content
  * and custom trimming.
  *
  * @package Customizr
  * @since Customizr 3.4.9
  *
  */
  // move this into CZR_utils?
  function czr_fn_get_post_excerpt( $_post, $default_text_length, $more ) {
    if ( ! empty( $_post->post_password) )
      return __( 'There is no excerpt because this is a protected post.', 'customizr' );

    $excerpt = '' != $_post->post_excerpt ? $_post->post_excerpt : $_post->post_content;

    $excerpt = apply_filters( 'tc_post_excerpt_pre_sanitize' , $excerpt );
    // below some function applied to the_content & the_excerpt filters
    // we cannot use those filters 'cause some plugins, e.g. qtranslate
    // filter those as well invalidating our transient
    $excerpt = strip_shortcodes( $excerpt );
    $excerpt = wptexturize( $excerpt );
    $excerpt = convert_chars( $excerpt );
    $excerpt = wpautop( $excerpt );
    $excerpt = shortcode_unautop( $excerpt );
    $excerpt = str_replace(']]>', ']]&gt;', $excerpt );

    $excerpt = apply_filters( 'tc_post_excerpt_pre_trim' , $excerpt );
    return czr_fn_text_truncate( $excerpt, $default_text_length, $more);
  }

} //end of class
endif;

?>