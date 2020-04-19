<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-09
 * Time: 15:36
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;

class DetectHeading extends AbstractProcess {
    
    public static function apply( Document $document ): Document {
        return $document;
    }
}