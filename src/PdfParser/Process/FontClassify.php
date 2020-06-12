<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-09
 * Time: 14:51
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;

class FontClassify extends AbstractProcess {
    
    public static function apply( Document $document ): Document {
        $document->arrangeFont();
        $fonts = $document->getAllFonts();
        foreach ($document->getPages() as $page){
            foreach ($page->components as $object){
                foreach ($fonts as $font) {
                    if( (int) $font->id == (int) $object->font_id)
                        $object->heading_level = $font->level;
                }
            }
        }
        return $document;
    }


}
