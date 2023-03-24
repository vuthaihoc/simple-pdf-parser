<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-01
 * Time: 14:37
 */

namespace ThikDev\PdfParser\Objects;


class Text extends Component {

    const ALIGN_UNKNOWN = 'unknown';
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_CENTER = 'center';
    const ALIGN_JUSTIFY = 'justify';

    const V_POS_NORMAL = 0;
    const V_POS_TOP = 1;
    const V_POS_BOTTOM = -1;

    public $font_id;
    public $bold = false;
    public $italic = false;

    public $align = self::ALIGN_UNKNOWN;

    public $v_pos = 0;
    public $heading_level = 0;
    public $font_size = 0;
    public $font_name = '';

    /**
     * Text constructor.
     *
     * @param $top
     * @param $left
     * @param $width
     * @param $height
     * @param $content
     * @param $font_id
     */
    public function __construct( $top, $left, $width, $height, $content, $font_id ) {
        parent::__construct( $top, $left, $width, $height );
        $this->font_id = $font_id;
        $this->bold = str_starts_with($content, "<b>") && str_ends_with($content, "</b>");
        $this->italic = str_starts_with($content, "<i>") && str_ends_with($content, "</i>");
        $this->raw = $content;
        $this->text = strip_tags( $content );
    }

    public static function parse($string){
        $string = trim( $string );
        $attributes = self::preParse($string, 'text');

        if(count($attributes)){
            return new self(
                top: $attributes['top'],
                left: $attributes['left'],
                width: $attributes['width'],
                height: $attributes['height'],
                content: $attributes['text'],
                font_id: $attributes['font'],
            );
        }
        return null;
    }

    public function html($meta_include = false){
        $html = strip_tags($this->raw, ['i','b']);
        if($meta_include){
            $meta = " font='" . $this->font_name . "' size='" . $this->font_size . "' font_id='" . $this->font_id . "' ";
            $html = $html . "<span" . $meta . "></span>";
        }else{
            $meta = "";
        }
        if($this->v_pos == self::V_POS_TOP){
            $html = "<sup>" . $html . "</sup>";
        }elseif($this->v_pos == self::V_POS_BOTTOM){
            $html = "<sub>" . $html . "</sub>";
        }
//        if($this->bold){
//            $html = "<b>" . $html . "</b>";
//        }
//        if($this->italic){
//            $html = "<i>" . $html . "</i>";
//        }
        if($this->heading_level == 1){
            $html = "<h3>" . $html . "</h3>";
        }
        if($this->heading_level == 2){
            $html = "<h2>" . $html . "</h2>";
        }
        if($this->heading_level == -1){
            $html = "<small>" . $html . "</small>";
        }
        return $html;
    }

}
