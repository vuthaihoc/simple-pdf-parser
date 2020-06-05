<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-01
 * Time: 14:45
 */

namespace ThikDev\PdfParser\Objects;


class Font {
    public $name;
    public $size;
    public $color;
    public $id;
    
    public $line_height;// chiều cao dòng
    public $chars = 0;// tổng số từ loại font này
    public $distribution = 0;// số phần trăm font so với các loại khác
    public $char_width = 5;
    
    public $level = null; // Heading level, null là chưa tính toàn, 0 là default,
    
    /**
     * Font constructor.
     *
     * @param $name
     * @param $size
     * @param $color
     * @param $id
     */
    public function __construct( $id, $size, $name, $color ) {
        $this->name = $name;
        $this->size = $size;
        $this->color = $color;
        $this->id = $id;
        $this->line_height = (int)ceil($size * 1.2);
        
    }
    
    public static function parse($string){
        $string = trim( $string );
        if(preg_match( "/^\<fontspec\sid\=\"(\d+)\"\ssize\=\"(-?\d+)\"\sfamily\=\"([^\"]*)\"\scolor\=\"(\S+)\"\/\>$/", $string, $matches)){
            return new self($matches[1],$matches[2],$matches[3],$matches[4]);
        }else{
            throw new \Exception("Can not parse font : " . $string);
        }
        return null;
    }
    
    /**
     * Chiều rộng của chuỗi tuong ung font hien tai
     * @param $string
     *
     * @return int
     */
    public function widthOfString($string) : int{
        return $this->widthOfChars( mb_strlen( $string ) );
    }
    
    /**
     * Chiều rộng của $charCount ky tu tuong ung font hien tai
     * @param int $charCount
     *
     * @return int
     */
    public function widthOfChars(int $charCount) : int{
        return (int)($charCount * $this->char_width);
    }
    
    /**
     * So luong ky tu de lap day chieu rong cho truoc
     * @param int $width
     *
     * @return int
     */
    public function charsToFit(int $width) : int{
        return (int)($width / $this->char_width);
    }
    
}