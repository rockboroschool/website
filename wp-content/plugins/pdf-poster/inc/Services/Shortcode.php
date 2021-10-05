<?php

namespace PDFP\Services;
use PDFP\Model\AdvanceSystem;
use PDFP\Model\AnalogSystem;

class Shortcodes{
  protected static $_instance = null;

  public function __construct(){
    add_shortcode('pdf', [$this, 'pdf'], 10, 2);
  }

  public static function instance(){
    if(self::$_instance === null){
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function pdf($atts, $content){
    extract(shortcode_atts(array(
      'id' => null,
    ), $atts));

    $post_type = get_post_type($id);
    $pluginUpdated = 1630223686;
    $publishDate = get_the_date('U', $id);
    $isGutenberg = get_post_meta($id, 'isGutenberg', true);
    $post = get_post($id);

    
    ob_start(); 
    
    if($post_type !== 'pdfposter'){
      return false;
    }

    if($pluginUpdated < $publishDate && $post->post_content != '' || $isGutenberg){
      echo( AdvanceSystem::html($id));
    }else {
      echo Analogsystem::html($id);
    }
    
    return ob_get_clean(); 
  }
}

Shortcodes::instance();