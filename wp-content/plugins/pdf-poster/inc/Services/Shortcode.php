<?php

namespace PDFP\Services;
use PDFP\Model\AdvanceSystem;

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

    ob_start(); 
    
    if($post_type !== 'pdfposter'){
      return false;
    }

    echo( AdvanceSystem::html($id));
    
    return ob_get_clean(); 
  }
}

Shortcodes::instance();