<?php
// Control core classes for avoid errors
if (class_exists('CSF'))
{

  //-------------------------------------------------------------------------
  //   Player type
  // ------------------------------------------------------------------------
  $prefix = '_h5ap_plyr';


  CSF::createMetabox($prefix, array(
    'title' => 'PDF Configuration',
    'post_type' => 'pdfposter',
    'data_type' => 'unserialize'
  ));

  CSF::createSection($prefix, array(
    'fields' => array(
      array(
        'id' => 'meta-image',
        'type' => 'upload',
        'title' => 'Upload or Paste PDF URL',
        // 'library' => 'application/pdf',
        'placeholder' => 'http://',
        'button_title' => 'Add PDF',
        'remove_title' => 'Remove PDF',
      ),
      array(
        'id' => 'pdfp_onei_pp_height',
        'title' => __("Height", "pdfp"),
        'type' => 'number',
        'default' => 842
      ),
      array(
        'id' => 'pdfp_onei_pp_width',
        'title' => __("Width", "pdfp"),
        'type' => 'number',
      ),
      // array(
      //   'id' => 'height',
      //   'title' => 'Height',
      //   'type' => 'dimensions',
      //   'width' => false,
      //   'default' => [
      //     'height' => 842,
      //     'unit' => 'px'
      //   ]
      // ),
      // array(
      //     'id' => 'width',
      //     'title' => 'Width',
      //     'type' => 'dimensions',
      //     'height' => false,
      //     'default' => [
      //         'width' => '100',
      //         'unit' => '%'
      //     ]
      // ),
      array(
        'id' => 'pdfp_onei_pp_print',
        'type' => 'switcher',
        'title' => 'Allow Print',
        'desc' => 'Enable if you allow visitors to print the pdf file .',
        'default' => false,
      ),
      array(
        'id' => 'pdfp_onei_pp_pgname',
        'type' => 'switcher',
        'title' => esc_html__('Show File Name On top', 'pdfp'),
        'desc' => 'Enable if you want to show File name on top.',
        'default' => false,
      ),
    )
  ));
}

