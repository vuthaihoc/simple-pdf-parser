<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-29
 * Time: 13:45
 */

namespace ThikDev\PdfParser\Helpers;


class Text {
    
    public static function removeExcessSpaces($string){
        $string = trim( $string );
        return preg_replace( "/\s{2,}/ui", " ", $string);
    }
    
}