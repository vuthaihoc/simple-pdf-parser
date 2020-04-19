<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-07
 * Time: 10:49
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Component;
use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Page;

class DetectMargin extends AbstractProcess {
    
    public static function apply( Document $document ): Document {
        foreach ($document->getPages() as $page){
            self::simpleMargin($page);
            $document->margin_top = $document->margin_top ? min($document->margin_top, $page->margin_top) : $page->margin_top;
            $document->margin_right = $document->margin_right ? min($document->margin_right, $page->margin_right) : $page->margin_right;
            $document->margin_bottom = $document->margin_bottom ? min($document->margin_bottom, $page->margin_bottom) : $page->margin_bottom;
            $document->margin_left = $document->margin_left ? min($document->margin_left, $page->margin_left) : $page->margin_left;
        }
        return $document;
    }
    
    protected static function simpleMargin(Page $page){
        $margin_top = $page->bottom;
        $margin_right = $page->right;
        $margin_bottom = $page->right;
        $margin_left = $page->bottom;
        
        /** @var Component $component */
        foreach ($page->components as $component){
            $margin_top = min($component->top, $margin_top);
            $margin_right = min($margin_right, $page->right - $component->left - $component->width);
            $margin_bottom = min($margin_bottom, $page->bottom - $component->top - $component->height);
            $margin_left = min($margin_left, $component->left);
        }
        $page->margin_top = $margin_top;
        $page->margin_right = $margin_right;
        $page->margin_bottom = $margin_bottom;
        $page->margin_left = $margin_left;
        return $page;
    }
}