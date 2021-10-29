<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-09
 * Time: 14:51
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Component;
use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Font;

class FontClassify extends AbstractProcess {
    
    public static function apply( Document $document ): Document {
        $document->arrangeFont();
        $fonts = $document->getAllFonts();
        foreach ($document->getPages() as $page){
            /** @var Component $object */
            foreach ($page->components as $object){
                if(isset($fonts[$object->font_id])){
                    $object->heading_level = $fonts[$object->font_id]->level;
                    $object->font_size = $fonts[$object->font_id]->size;
                    $object->font_name = $fonts[$object->font_id]->name;
                }
            }
        }
        return $document;
    }


}
