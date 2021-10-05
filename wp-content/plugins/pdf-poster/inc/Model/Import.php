<?php
namespace PDFP\Model;

class Import{

    public static function importMeta(){
        $pdfs = self::createBlock();
        foreach($pdfs as $pdf){
            $content_post = get_post($pdf['ID']);
            $content = $content_post->post_content;
            if($content == ''){
                wp_update_post($pdf);
            }
        }
    }

    public static function createBlock(){
        $query = new \WP_Query(array(
            'post_type' => 'pdfposter',
            'post_status' => 'any',
            'posts_per_page' => -1
        ));

        $output = [];
        while($query->have_posts()): $query->the_post();
            $id = \get_the_ID();
            $output[] = [
                'ID' => $id,
                'post_content' => '<!-- wp:pdfp/pdfposter '.json_encode([
                    'file' => get_post_meta($id, 'meta-image', true),
                    'title' => basename(get_post_meta($id, 'meta-image', true)),
                    'height' => get_post_meta($id, 'pdfp_onei_pp_height', true) == '' ? '1122px' : get_post_meta($id, 'pdfp_onei_pp_height', true).'px',
                    'width' => get_post_meta($id, 'pdfp_onei_pp_width', true) == '' ? '100%' : get_post_meta($id, 'pdfp_onei_pp_width', true).'px',
                    'print' => get_post_meta($id, 'pdfp_onei_pp_print', true) == 'on' ? true : false,
                    'showName' => get_post_meta($id, 'pdfp_onei_pp_pgname', true) == 'on' ? true : false
                ]).' /-->'
            ];
        endwhile;

        return $output;
    }
}