<?php
namespace PDFP\Model;
use PDFP\Helper\DefaultArgs;
use PDFP\Services\PDFTemplate;

class AnalogSystem{

    public static function html($id){
        $data  = DefaultArgs::parseArgs(self::getData($id));
        return PDFTemplate::html($data);
    }


    public static function parseArgs($data){
        $default = DefaultArgs::get();
        $data['options'] = wp_parse_args( $data['options'], $default['options'] );
        $data['infos'] = wp_parse_args( $data['infos'], $default['infos'] );
        $data['template'] = wp_parse_args( $data['template'], $default['template'] );

        return wp_parse_args( $data, $default );
    }

    public static function getData($id){
        $options = [];

        $infos = [];

        $height = self::GPM($id, 'height', ['height' => 1122, 'unit' => 'px']);
        $width = self::GPM($id, 'width', ['width' => 100, 'unit' => '%']);
        $template = array(
            'file' => self::GPM($id, 'source', ''),
            'height' => $height['height'].$height['unit'],
            'width' => $width['width'].$width['unit'],
            'classes' => '',
            'showName' => self::GPM($id, 'show_filename', false, true),
            'print' => self::GPM($id, 'print', false, 'false') == '1' ? 'vera' : false
        );

        return [
            'options' => $options,
            'infos' => $infos,
            'template' => $template
        ];

    }


    public static function get_post_meta($id, $key, $default = false){
        if (metadata_exists('post', $id, $key)) {
            $value = get_post_meta($id, $key, true);
            if ($value != '') {
                return $value;
            } else {
                return $default;
            }
        } else {
            return $default;
        }
    }

    public static function GPM($id, $key, $default = false, $true = false){
        $meta = metadata_exists( 'post', $id, '_fpdf' ) ? get_post_meta($id, '_fpdf', true) : '';
        if(isset($meta[$key]) && $meta != ''){
            if($true == true){
                if($meta[$key] == '1'){
                    return true;
                }else if($meta[$key] == '0'){
                    return false;
                }
            }else {
                return $meta[$key];
            }
            
        }

        return $default;
    }

}