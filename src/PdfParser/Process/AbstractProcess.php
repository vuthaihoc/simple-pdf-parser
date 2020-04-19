<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-19
 * Time: 16:07
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;

abstract class AbstractProcess {
    
    use LoggerTrait;
    
    abstract static function apply(Document $document) : Document;
    
}