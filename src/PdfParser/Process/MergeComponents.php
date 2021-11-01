<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-05
 * Time: 16:59
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Component;
use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;
use ThikDev\PdfParser\Objects\Page;
use ThikDev\PdfParser\Objects\Text;

class MergeComponents extends AbstractProcess {
    
    /** @var Document */
    protected $document;
    
    protected function __construct(Document $document) {
        $this->document = $document;
    }
    
    public static function apply(Document $document) : Document{
        $process = new self($document);
        
        foreach ($document->getPages() as $page){
            $process->mergeTooNearComponents( $page );
            $process->mergeComponentsInPage($page);
        }

        return $document;
    }
    
    protected function mergeComponentsInPage(Page $page){
        // reset lines
        $page->objects = [];
        $last_line = null;
        /**
         * @var int $k
         * @var Component $component
         */
        foreach ( $page->components as $k => $component ){
            // bỏ qua các component khoảng trắng
            if($component->text == " "){
                continue;
            }

            // bo qua header footer
            if(
                $page->inHeader( $component )
                || $page->inFooter( $component )
            ){
                continue;
            }

            // component dau tien
            if(!$last_line){
                $last_line = Line::fromText( $component );
                continue;
            }
            
            if($this->shouldMerge( $last_line, $component, $k, $page )){
                $last_line->appendText( $component );
//                if(strpos($last_line->text, "Introduction")){
//                    dump($last_line, $last_line->getHtml());
//                }
            }else{
                $last_line->reorderComponents();
                $last_line->detectLineHeight($this->document);
                $page->objects[] = $last_line;
                $last_line = Line::fromText( $component );
            }
        }
        if($last_line){
            $last_line->reorderComponents();
            $page->objects[] = $last_line;
        }
    }
    
    protected function mergeTooNearComponents(Page $page){
        $components = $page->components;
        $result = [];
        $buff_component = null;
        $last_component = null;
        /**
         * @var int $k
         * @var Component $component
         */
        foreach ($components as $k => $component){
            if($last_component == null){
                $last_component = $component;
                $buff_component = $component;
                continue;
            }
            //$component->ddIfStarts( "ể", $component->text, $buff_component->text, $this->isTooNear($last_component, $component));
            if($this->isTooNear($last_component, $component)){
                $buff_component->text .= $component->text;
                $buff_component->raw .= $component->raw;
                $buff_component->top = min($buff_component->top, $component->top);
                $buff_component->height = max($buff_component->height, $component->height);
                $buff_component->width = $component->left + $component->width - $buff_component->left;
                
                $last_component = $component;
            }else{
                $result[] = $buff_component;
                
                $buff_component = $component;
                $last_component = $component;
            }
        }
        if($buff_component){
            $result[] = $buff_component;
        }
        $page->components = $result;
    }
    
    protected function isTooNear(Text $text1, Text $text2){
        return abs($text1->top + $text1->height - $text2->top - $text2->height ) < 2
        && abs( $text2->left - $text1->left - $text1->width ) < 2
        && $this->document->getFont( $text1->font_id )->size == $this->document->getFont( $text2->font_id )->size;
    }
    
    protected function shouldMerge(Line $current_line, Text $text, int $text_index, Page $page){
        $last_normal_text = $current_line->lastNormalText();

        if(
            $text->width == 0 &&
            $last_normal_text->width == 0
        ){
            if($text->left == $last_normal_text->left){
                return true;
            }
            if($last_normal_text->left - $text->left < $text->height * 1.5){
                return true;
            }
        }

        if(
            $last_normal_text->top + $last_normal_text->height >= $text->top + (int)($text->height/2)
            && $last_normal_text->top <= $text->top + $text->height // cùng dòng
            && $current_line->left < $text->left /** @todo ? */
        ){

            if(mb_strlen($text->text) > 10){
                // neu text hien tai dai thi bo qua

            }elseif(
                $last_normal_text->font_size > $text->font_size &&
                $last_normal_text->bottom() - $text->bottom() >= 2
            ){
                $text->v_pos = Text::V_POS_TOP;
            }elseif(
                $last_normal_text->font_size > $text->font_size &&
                $last_normal_text->bottom() - $text->bottom() <= -2
            ){
                $text->v_pos = Text::V_POS_BOTTOM;
            }

//            if(strpos($text->text, 'Jasa Kustodian') !== false){
//                dd($current_line, $last_normal_text, $text, $text->v_pos);
//            }
            
//            elseif(!$this->isSimilarStyle( $text, $last_normal_text)){
//                return false;
//            }
            return true;
        }

        // chú thích thuong nho hon va lech 1 chut so voi text dang sau
        if (count($current_line->components) == 1 && // moi co 1 component
            mb_strlen($last_normal_text->text) < 5 // component qua ngan
        && (($last_normal_text->top > $text->top && $last_normal_text->top < $text->bottom()) ||
            ($last_normal_text->bottom() > $text->top && $last_normal_text->bottom() < $text->bottom()))
        ){
            return true;
        }

        return false;
    }
    
    protected function isSimilarStyle(Text $text1, Text $text2){
        $font1 = $this->document->getFont( $text1->font_id );
        $font2 = $this->document->getFont( $text2->font_id );
        if(abs( $font1->size - $font2->size ) > 2){
            return false;
        }
        return true;
    }
    
}
