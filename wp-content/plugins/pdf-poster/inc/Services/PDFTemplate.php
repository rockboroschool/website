<?php
namespace PDFP\Services;
use PDFP\Helper\DefaultArgs;
// use PDFP\Helper\Functions;
use PDFP\Services\AnalogSystem;

class PDFTemplate{
    public static $uniqid = null;
    protected static $styles = [];
    protected static $mediaQuery = [];

    public static function html($data){
        self::createId();
        self::enqueueEssentialAssets();
        
        $iid = self::$uniqid;
        $t = $data['template'];
        // $settings = $data;
        // unset($settings['template']);
		
        ob_start();
        // echo "<pre>";
        // print_r($data['template']);
        // echo "</pre>";
		?>
		<style>
            <?php echo esc_html(self::renderStyle($data['template'])); ?>
        </style>
        <?php if($t['file']){ ?>
	    <div id="<?php echo esc_attr($iid); ?>" class="pdfp_wrapper">
            <?php
                $viewer_base_url= plugins_url()."/pdf-poster/pdfjs/web/viewer.html"; 
                $final_url = $viewer_base_url."?file=".$t['file']."&download=true&print=".$t['print']."&openfile=false";
            ?>
            <div class="cta_wrapper">
                <?php if($t['showName']): ?>
                    <p>File name :  <?php echo esc_html(basename($t['file']));?></p>
                <?php endif;?>
	            <p><a href="<?php echo esc_url($final_url); ?>"><button>View Fullscreen</button></a></p>
            </div>
            <div class="iframe_wrapper">
                <iframe loading="lazy" width="<?php echo esc_attr($t['width']) ;?>" height="<?php echo esc_attr($t['height']) ;?>" src="<?php echo esc_url($final_url);?>"></iframe>
            </div>
	    </div>
        <?php } else { 
            echo "<h3>Oops! You forgot to select a pdf file. </h3>";
        }

        return ob_get_clean();
    }

    public static function enqueueEssentialAssets(){
        wp_enqueue_style( 'pdfp-public');
        // wp_enqueue_script( 'pdp-public');
    }

    public static function renderStyle($template){
        $id = self::$uniqid;
        // self::addStyle("#$id", ['margin-bottom' => '25px']);      

        $output = '';
        foreach(self::$styles as $selector => $style){
            $new = '';
            foreach($style as $property => $value){
                if($value == ''){
                    $new .= $property;
                }else {
                    $new .= " $property: $value;";
                }
            }
            $output .= "$selector { $new }";
        }
        
        foreach(self::$mediaQuery as $query => $styles){
            $output .= $query."{";
            foreach($styles as $selector => $style){
                $new = '';
                foreach($style as $property => $value){
                    if($value == ''){
                        $new .= $property;
                    }else {
                        $new .= " $property: $value;";
                    }
                }
                $output .= "$selector { $new }";
            }
            $output .= "}";
        }



        return $output;
    }

    public static function addStyle($selector, $styles, $mediaQuery = false){
        if($mediaQuery){
            if(array_key_exists($mediaQuery, self::$mediaQuery)){
                if(array_key_exists($selector, self::$mediaQuery[$mediaQuery])){
                    self::$mediaQuery[$mediaQuery][$selector] = wp_parse_args(self::$mediaQuery[$mediaQuery][$selector], $styles);
                }else {
                    self::$mediaQuery[$mediaQuery] = wp_parse_args(self::$mediaQuery[$mediaQuery], [$selector => $styles]);
                }
             }else {
                 self::$mediaQuery[$mediaQuery] = [$selector => $styles];
             }
        }else {
            if(array_key_exists($selector, self::$styles)){
                self::$styles[$selector] = wp_parse_args(self::$styles[$selector], $styles);
             }else {
                 self::$styles[$selector] = $styles;
             }
        }
        
    }


    public static function createId(){
        self::$uniqid = "h5vp".uniqid();
    }

    public static function splice($string){
        if(strlen($string) < 45){
            return $string;
        }
        return substr($string, 0, 40)."...";
    }



}