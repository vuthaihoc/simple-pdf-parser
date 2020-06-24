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
        if(preg_match( "/^\<image top=\"(\d+)\" left=\"(-?\d+)\" width=\"(-?\d+)\" height=\"(-?\d+)\" src=\".*\"(.*)\/>/ui", $string, $matches)){
            return new Image($matches[1],$matches[2],$matches[3],$matches[4]);
        }else{
            throw  new \Exception("Can not parse image component : " . $string);
        }
    }

    public function getHtml(){
        $html = "<img src='https://via.placeholder.com/{$this->width}x{$this->height}' width='{$this->width}' height='{$this->height}'>";

        return $html;
    }


}
