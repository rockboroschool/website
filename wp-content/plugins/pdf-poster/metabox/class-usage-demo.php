<?php
//include the main class file
require_once("meta-box-class/my-meta-box-class.php");
if (is_admin()){
  /* 
   * prefix of meta keys, optional
   * use underscore (_) at the beginning to make keys hidden, for example $prefix = '_ba_';
   *  you also can make prefix empty to disable it
   * 
   */
  $prefix = 'pdfp_';
  /* 
   * configure your meta box
   */
  $config = array(
    'id'             => 'demo_meta_box',          // meta box id, unique per meta box
    'title'          => 'Viewer Settings (Optional)',          // meta box title
    'pages'          => array('pdfposter'),      // post types, accept custom post types as well, default is array('post'); optional
    'context'        => 'normal',            // where the meta box appear: normal (default), advanced, side; optional
    'priority'       => 'high',            // order of meta box: high (default), low; optional
    'fields'         => array(),            // list of meta fields (can be added by field arrays)
    'local_images'   => false,          // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => false          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
  );
  
  
  /*
   * Initiate your meta box
   */
  $my_meta =  new AT_Meta_Box($config);
  
  /*
   * Add fields to your meta box
   */
  //Height field
  $my_meta->addNumber($prefix.'onei_pp_height',array('name'=> 'Height','std' => '1122','desc' =>' Leave blank if you want to use default height (1122 Px)'));
  //width field
  $my_meta->addNumber($prefix.'onei_pp_width',array('name'=> 'Width','std' => '','desc' =>' Leave blank if you want to use default width (100%)')); 
  //Page # field
  $my_meta->addCheckbox($prefix.'onei_pp_print',array('name'=> 'Allow Print','std' => '','desc' =>'Check if you allow visitors to print the pdf file .'));    
  //checkbox field
  $my_meta->addCheckbox($prefix.'onei_pp_pgname',array('name'=> 'Show File Name On top','std' => '','desc' =>'Check if you want to show File name on top.'));
   //checkbox field
  //$my_meta->addCheckbox($prefix.'onei_pp_download_button',array('name'=> 'Enable Download Button','std' => '','desc' =>'Check if you want to show Download Button on top.')); 

  /*
   * Don't Forget to Close up the meta box Declaration 
   */
  //Finish Meta Box Declaration 
  $my_meta->Finish();

}