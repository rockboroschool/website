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

        $infos = [
            
        ];

        $template = array(
            'file' => self::get_post_meta($id, 'meta-image', ''),
            'height' => self::get_post_meta($id, 'pdfp_onei_pp_height', 1122).'px',
            'width' => self::get_post_meta($id, 'pdfp_onei_pp_width', false) ? self::get_post_meta($id, 'pdfp_onei_pp_width', false).'px' : '100%',
            'classes' => '',
            'showName' => self::get_post_meta($id, 'pdfp_onei_pp_pgname', false),
            'print' => self::get_post_meta($id, 'pdfp_onei_pp_print', false),
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
        $meta = metadata_exists( 'post', $id, 'h5vp_playlist_options' ) ? get_post_meta($id, 'h5vp_playlist_options', true) : '';
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

    private static function get_videos($id, $key, $default = null, $true = false){
        $meta = metadata_exists( 'post', $id, 'h5vp_playlist' ) ? get_post_meta( $id, 'h5vp_playlist', true ) : '';
        if(isset($meta[$key]) && $meta[$key] != '' && $true == true){
            return true;
        }elseif(isset($meta[$key]) && $meta[$key] != '') {
            return $meta[$key];
        }else {
            return $default;
        }
    }

    public static function getQuickPlayerData(){
        
    }
}