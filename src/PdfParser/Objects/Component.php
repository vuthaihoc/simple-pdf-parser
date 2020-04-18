<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-01
 * Time: 14:38
 */

namespace ThikDev\PdfParser\Objects;


class Component {
    
    public $top;
    public $left;
    public $width;
    public $height;
    
    /** @var array Children components */
    public $components = [];
    
    /** @var Page */
    public $page;
    
    /** @var string raw content */
    public $raw;
    
    /** @var string text content */
    public $text;
    
    /**
     * Component constructor.
     *
     * @param $top
     * @param $left
     * @param $width
     * @param $height
     */
    public function __construct( $top, $left, $width, $height ) {
        $this->top = (int)$top;
        $this->left = (int)$left;
        $this->width = (int)$width;
        $this->height = (int)$height;
    }
    
    public function dumpIfContains($string, ...$var){
        if(mb_strpos( $this->text, $string) !== false){
            dump( ...$var );
        }
    }
    
    public function dumpIfStarts($string, ...$var){
        if(mb_strpos( $this->text, $string) === 0){
            dump( ...$var );
        }
    }
    
    public function ddIfContains($string, ...$var){
        if(mb_strpos( $this->text, $string) !== false){
            dd( ...$var );
        }
    }
    
    public function ddIfStarts($string, ...$var){
        if(mb_strpos( $this->text, $string) === 0){
            dd( ...$var );
        }
    }
}