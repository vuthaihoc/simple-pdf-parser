<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-01
 * Time: 14:38
 */

namespace ThikDev\PdfParser\Objects;


class Component {

    use ParseTrait;

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

    public function bottom(){
        return $this->top + $this->height;
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

    public function lastNormalText(): ?Component {
        if ( $count = count( $this->components ) ) {
            for ( $i = $count - 1; $i >= 0; $i -- ) {
                if ( $this->components[ $i ]->v_pos == Text::V_POS_NORMAL ) {
                    return $this->components[ $i ];
                }
            }

            return $this->components[ $count - 1 ];
        }
    }

    public function firstComponent(): ?Component {
        if ( $count = count( $this->components ) ) {
            return $this->components[0];
        }
    }



}