<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-09
 * Time: 14:52
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;

class DetectTable extends AbstractProcess {
    
    public static function apply( Document $document ): Document {
        return $document;
    }
}