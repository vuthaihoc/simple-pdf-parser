<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-01
 * Time: 14:30
 */

namespace ThikDev\PdfParser\Objects;


use ThikDev\PdfParser\Exceptions\ParseException;

class Page {
    public $top;
    public $left;
    public $right;
    public $bottom;
    
    public $width;
    public $height;
    
    public $number;
    /**
     * @var Component[] Các line được merge cơ bản từ các component,
     *             sau khi có line thì làm việc chủ yếu qua các Line và thuộc tính của nó
     * @todo nên đổi tên thành object để mang đúng nghĩa hơn, sau sẽ chứa các cùng noise, table, ...
     */
    public $objects=[];
    public $images = [];

    public $header = 0;
    public $footer = 0;
    
    /**
     * @var int Số cột tối đa trong trang hiện tại, vẫn có thể chứa dòng tràn các cột
     *  Hiện tại chỉ xét trang có tối đa 2 cột.
     */
    public $columns = 0;/** @todo detect column of page */
    
    public $components=[];
    
    use HasMarginTrait;
    
    /** @var $document Document */
    public $document;
    
    public $main_left;
    
    public $char_count = 0;
    
    public $is_table_content = false;
    
    const DENY_PATTERN_1 = '/\-{3,}|\_{3,}|\.{3,}/u';
    const DENY_PATTERN_2 = '/(?<![,\s])…{1,}/u';
    const END_PATTERN = '/[\.:”]\s*$/u';
    const LIST_SIGN_PATTERN = '/^\s*(|||\*||(&bull;)|(&ndash;)|-|\+)+\s*$/u';
    
    public function __construct($top, $left, $height, $width) {
        $this->width = (int)$width;
        $this->height = (int)$height;
        
        $this->top = (int)$top;
        $this->right = (int)$left + (int)$width;
        $this->bottom = (int)$top + (int)$height;
        $this->left = (int)$left;
    }
    
    public static function parse($string, $default_width = 0, $default_height = 0){
        $string = trim( $string );
        if(preg_match( "/^\<page\snumber=\"(\d+)\"\sposition=\"(\S+)\"\stop=\"(\d+)\" left=\"(\d+)\" height=\"(-?\d+)\" width=\"(-?\d+)\"\>$/", $string, $matches)){
            if ((int)$matches[5] < 1) {
                $matches[5] = $default_height;
            }
            if ((int)$matches[6] < 1) {
                $matches[6] = $default_width;
            }
            return new Page($matches[3],$matches[4],$matches[5],$matches[6]);
        }else{
            throw new \Exception("Can not parse page : " . $string);
        }
        return null;
    }
    
    public function cropText($top, $right, $bottom, $left){
        $text = [];
        $max_height = 0;
        $error = 2;
        /** @var Component $component */
        foreach ($this->components as $component){
            if($component->top > $top - $error
               && $component->left > $left - $error
               && $component->top + $component->height < $bottom + $error
               && $component->left + $component->width < $right + $error
            ){
                $text[] = trim( $component->text );
                $max_height = max( $max_height, $component->height );
            }
        }
        return [$max_height, implode( " ", $text)];
    }
    
    public function cropTextTop($top){
        $header_box = [
            $this->margin_top,
            $this->width - $this->margin_right,
            $this->margin_top + $top,
            $this->margin_left,
        ];
        return $this->cropText( ...$header_box );
    }
    
    public function cropTextBottom($bottom){
        $header_box = [
            $this->bottom - $this->margin_bottom - $bottom,
            $this->width - $this->margin_right,
            $this->bottom - $this->margin_bottom,
            $this->margin_left,
        ];
        return $this->cropText( ...$header_box );
    }
    
    public function getHeader(){
        if(!$this->header){
            return '';
        }
        
        $box = [
            $this->margin_top,
            $this->width - $this->margin_right,
            $this->margin_top + $this->header,
            $this->margin_left,
        ];
        $header = $this->cropText( ...$box );
        return $header[1];
    }
    
    public function getFooter(){
        if(!$this->footer){
            return '';
        }
    
        $box = [
            $this->bottom - $this->margin_bottom - $this->footer,
            $this->width - $this->margin_right,
            $this->bottom - $this->margin_bottom,
            $this->margin_left,
        ];
        $footer = $this->cropText( ...$box );
        return $footer[1];
    }
    
    public function getText($raw = false){
        $text = '';
        if($raw){
            foreach ($this->components as $component){
                $text .= $component->text . "\n";
            }
        }else{
            foreach ($this->objects as $object){
                if($object instanceof Text || $object instanceof Line){
                    $text .= ($object->merge_up ? " " : "\n" )
                        . preg_replace( "/(\.s*|\-s*|\s\s|…s*|·\s*)\g{1}{4,}/u", "$1$1$1", $object->text);
                }
            }
        }
        return $text;
    }
    
    public function getHtml(){
        $html = "";
        $paragraph_buffer = [];
        foreach ($this->objects as $k => $object){
            if($object instanceof Text || $object instanceof Line){
                if(count($paragraph_buffer) == 0){
                    $paragraph_buffer[] = $object->getHtml() . "\n";
                    continue;
                }

                if($object->merge_up){
                    $paragraph_buffer[] = $object->getHtml() . "\n";
                }else{
                        $html .= "<p>" . implode( "", $paragraph_buffer) . "</p>\n";
                    $paragraph_buffer = [$object->getHtml() . "\n"];
                }
            }
            if($object instanceof Image){
                $html .= $object->getHtml() . "\n";
            }

        }
        if(count( $paragraph_buffer )){
            $html .= "<p>" . implode( "", $paragraph_buffer) . "</p>\n";
        }
        return $html;
    }
    
    public function inHeader(Component $component){
        return $component->top >= $this->margin_top
//               && $component->left >= $this->left
               && $component->top + $component->height <= $this->margin_top + $this->header
//               && $component->left + $component->width <= $this->right - $this->margin_right
            ;
    }
    
    public function inFooter(Component $component){
        return $component->top >= $this->bottom - $this->margin_bottom - $this->footer
//               && $component->left >= $this->left
               && $component->top + $component->height <= $this->bottom - $this->margin_bottom
//               && $component->left + $component->width <= $this->right - $this->margin_right
            ;
    }
    
    public function countObjects(){
        return count( $this->objects );
    }
    
    public function getObject($index ): Line {
        if ( isset( $this->objects[ $index ] ) ) {
            return $this->objects[ $index ];
        } else {
            throw new ParseException( "Not found Line at index " . $index );
        }
    }
    
    public function getObjects($indexes = []) {
        if(empty( $indexes )){
            foreach ($this->objects as $k => $object ) {
                yield $k => $object;
            }
        }else{
            foreach ($indexes as $index){
                yield $index => $this->getObject($index);
            }
        }
    }
    
    /**
     * @return array các dòng thuộc phần nội dung, không phải header/footer/chú thích
     * @todo phát hiện chú thích
     */
    public function getMainLines() : array {
        $main_lines = [];
        foreach ($this->objects as $object){
            if($this->inFooter( $object ) || $this->inHeader( $object )){
                continue;
            }
            $main_lines[] = $object;
        }
        return $main_lines;
    }
    
}
