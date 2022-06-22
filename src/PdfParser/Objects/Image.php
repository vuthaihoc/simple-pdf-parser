<?php


namespace ThikDev\PdfParser\Objects;


class Image  extends Component
{
    public $height;
    public $width;
    public $top;
    public $left;

    /**
     * Image constructor.
     * @param $height
     * @param $width
     * @param $top
     * @param $left
     */
    public function __construct($top, $left, $width, $height)
    {
        $this->top = $top;
        $this->left = $left;
        $this->width = $width;
        $this->height = $height;

    }

    public static function parse($string){
        $string = trim( $string );
        $attributes = self::preParse($string, 'image', false);
        if(count($attributes)){
            return new Image(
                top: $attributes['top'],
                left: $attributes['left'],
                width: $attributes['width'],
                height: $attributes['height'],
            );
        }
        dump("Can not parse image component : " . $string);
        return null;
    }

    public function getHtml(){
        $html = "<img src='https://via.placeholder.com/{$this->width}x{$this->height}' width='{$this->width}' height='{$this->height}'>";

        return $html;
    }


}
