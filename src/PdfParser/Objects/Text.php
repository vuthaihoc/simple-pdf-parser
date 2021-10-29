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
        $this->bold = strpos( $content, "<b>" ) !== false;
        $this->italic = strpos( $content, "<i>" ) !== false;
        $this->raw = $content;
        $this->text = strip_tags( $content );
    }

    public static function parse($string){
        $string = trim( $string );
        if(preg_match( "/^\<text top=\"(-?\d+)\" left=\"(-?\d+)\" width=\"(-?\d+)\" height=\"(-?\d+)\" font=\"(\d+)\"\s*\>(.*)<\/text>/i", $string, $matches)){
            return new Text($matches[1],$matches[2],$matches[3],$matches[4],$matches[6],$matches[5]);
        }else{
            dump("Can not parse component : " . $string);
        }
        return null;
    }

    public function html($meta_include = false){
        $html = $this->text;
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
        if($this->bold){
            $html = "<b>" . $html . "</b>";
        }
        if($this->italic){
            $html = "<i>" . $html . "</i>";
        }
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
