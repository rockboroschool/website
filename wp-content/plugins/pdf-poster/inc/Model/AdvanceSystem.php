<?php
namespace PDFP\Model;
use PDFP\Model\Block;
use PDFP\Helper\DefaultArgs;
use PDFP\Services\PDFTemplate;

class AdvanceSystem{

    public static function html($id){
        $blocks =  Block::getBlock($id);
        $output = '';
        if(is_array($blocks)){
            foreach($blocks as $block){
                if(isset($block['attrs'])){
                    $output .= render_block($block);
                }else {
                    $data = DefaultArgs::parseArgs(self::getData($block));
                    $output .= PDFTemplate::html($data);
                }
            }
        }
        return $output;
    }

    public static function getData($block){
        
        $options = [];

        $infos = [
            
        ];

        $template = array(
            'file' => self::i($block, 'file'),
            'height' => self::i($block, 'height', '', '1122px'),
            'width' => self::i($block, 'width', '', '100%'),
            'classes' => '',
            'showName' => self::i($block, 'showName', '', false),
            'print' => self::i($block, 'print', '', false) == true ? 'true' : 'false'
        );

        $result = [
            'options' => $options,
            'infos' => $infos,
            'template' => $template
        ];

        return $result;
    }

    public static function i($array, $key1, $key2 = '', $default = false){
        if(isset($array[$key1][$key2])){
            return $array[$key1][$key2];
        }else if (isset($array[$key1])){
            return $array[$key1];
        }
        return $default;
    }



    public static function parseControls($controls){
        $newControls = [];
        if(!is_array($controls)){
            return ['play','progress', 'current-time', 'mute', 'volume', 'settings', 'download'];
        }
        foreach($controls as $key => $value){
            if($value == 1){
                array_push($newControls, $key);
            }
        }
        return $newControls;
    }
}