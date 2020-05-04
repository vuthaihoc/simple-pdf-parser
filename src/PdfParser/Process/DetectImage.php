<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-09
 * Time: 14:53
 */

namespace ThikDev\PdfParser\Process;

use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Page;

class DetectImage extends AbstractProcess
{
    public static function apply(Document $document): Document
    {
        $process = new self();
        foreach ($document->getPages() as $k => $page) {
            $process->insertImage($page);
        }
        return $document;
    }

    public function insertImage(Page $page)
    {
        $new_object = $page->objects;
        foreach ($page->images as $key => $image) {
            $new_object[] =$image;
        }
        usort($new_object, function ($item_1, $item_2){
           return $item_1->top >= $item_2->top;
        });
        $page->objects = $new_object;
    }
}
