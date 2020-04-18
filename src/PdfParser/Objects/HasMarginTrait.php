<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-07
 * Time: 11:10
 */

namespace ThikDev\PdfParser\Objects;


trait HasMarginTrait {
    
    public $margin_top;
    public $margin_right;
    public $margin_bottom;
    public $margin_left;
    
    public function getMargin(){
        return [
            $this->margin_top,
            $this->margin_right,
            $this->margin_bottom,
            $this->margin_left,
        ];
    }
    
}