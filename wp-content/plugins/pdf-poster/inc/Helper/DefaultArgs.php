<?php
namespace PDFP\Helper;

class DefaultArgs{

    public static function parseArgs($data){
        $default = self::get();
        $data = wp_parse_args( $data, $default );
        $data['options'] = wp_parse_args( $data['options'], $default['options'] );
        $data['infos'] = wp_parse_args( $data['infos'], $default['infos'] );
        $data['template'] = wp_parse_args( $data['template'], $default['template'] );

		return $data;
    }

    public static function get(){
        $options = [];

        $infos = [
            
        ];

        $template = array(
            'file' => '',
            'height' => '1122px',
            'width' => '100%',
            'classes' => '',
            'showName' => false,
            'print' => false
        );

        $default = [
            'options' => $options,
            'infos' => $infos,
            'template' => $template
        ];
        
        return $default;
    }

    // public static function brandColor(){
    //     $brandColor = get_option('h5vp_option', ['h5vp_player_primary_color' => '#1ABAFF' ]);
    //     if(isset($brandColor['h5vp_player_primary_color']) && !empty($brandColor['h5vp_player_primary_color'])){
    //         return $brandColor['h5vp_player_primary_color'];
    //     }else {
    //         return '#1ABAFF';
    //     }
    // }
}