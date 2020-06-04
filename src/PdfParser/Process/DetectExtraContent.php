<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-05
 * Time: 17:07
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\FineDiff\FineDiff;
use ThikDev\FineDiff\FineDiffCopyOp;
use ThikDev\FineDiff\FineDiffDeleteOp;
use ThikDev\FineDiff\FineDiffInsertOp;
use ThikDev\FineDiff\FineDiffReplaceOp;
use App\NLP\TextDiff;
use ThikDev\PdfParser\Objects\Component;
use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Page;
use ThikDev\PdfParser\Objects\Text;

class DetectExtraContent extends AbstractProcess {
    
    public static function apply( Document $document, $force = false ): Document {
        $process = new self();
        $page_count = $document->pageCount();
        if ( $page_count < 3
             || ( $page_count < 5 && ! $force ) ) {
            $process->dump( "Too short : " . $page_count . " page");
            return $document;
        }
        if ( $page_count > 10 ) {
            // start from 1/4 document
            $start_page = (int) ( $page_count / 4 );
            if ( $start_page % 2 ) {
                $even_pages = [ $start_page - 1, $start_page + 1, $start_page + 3  ];
                $odd_pages = [ $start_page, $start_page + 2, $start_page + 4 ];
            }else{
                $even_pages = [ $start_page, $start_page + 2, $start_page + 4 ];
                $odd_pages = [ $start_page - 1, $start_page + 1, $start_page + 3 ];
            }
        }elseif ($page_count > 4){
            $even_pages = [ 1, 3 ];
            $odd_pages = [ 2, 4 ];
        }else{
            $even_pages = [ 1, 2 ];
            $odd_pages = [ 1, 2 ];
        }

        $even_header = $process->detectHeader( ...$document->getPagesList(...$even_pages) );
        $odd_header = $process->detectHeader( ...$document->getPagesList(...$odd_pages) );
        $even_footer = $process->detectFooter( ...$document->getPagesList(...$even_pages) );
        $odd_footer = $process->detectFooter( ...$document->getPagesList(...$odd_pages) );
        
        // Trang mau dung de sua margin bottom cua 1 so trang co it noi dung dan den thieu noi dung khi remove footer
        $odd_page_example = $document->getPage( $odd_pages[0]);
        $even_page_example = $document->getPage( $odd_pages[0]);
        
        /**
         * @var int $k
         * @var Page $page
         */
        foreach ($document->getPages() as $k => $page){
            if($k % 2){ // trang lẻ
                $page->header = $odd_header;
                $page->footer = $odd_footer;
                if($page->margin_bottom - $odd_page_example->margin_bottom > 5){
                    $page->margin_bottom = $odd_page_example->margin_bottom;
                }
            }else{ // trang chẵn
                $page->header = $even_header;
                $page->footer = $even_footer;
                if($page->margin_bottom - $even_page_example->margin_bottom > 5){
                    $page->margin_bottom = $even_page_example->margin_bottom;
                }
            }
        }
        
        return $document;
        
    }
    
    protected function detectHeader( Page ...$pages ) {
        $line_height = 0;
        $step = 30;
        
        start:
        $tmp_headers = [];
        foreach ($pages as $page){
            $tmp_headers[] = $page->cropTextTop( $line_height + $step );
        }
        $is_header = $this->checkHeaders( $tmp_headers );
        if(!$is_header){
            return $line_height;
        }
    
        if($line_height > (int)($pages[0]->height/4)){
            return 0;
        }
        
        $line_height += $tmp_headers[0][0] ?: $step;
        goto start;
    }
    
    protected function detectFooter( Page ...$pages ) {
        $line_height = 0;
        $step = 30;
        
        start:
        $tmp_headers = [];
        foreach ($pages as $page){
            $tmp_headers[] = $page->cropTextBottom( $line_height + $step );
        }
//        dd($tmp_headers);
        $is_header = $this->checkHeaders( $tmp_headers );
//        dd($is_header);
        if(!$is_header){
            return $line_height;
        }
        
        if($line_height > (int)($pages[0]->height/4)){
            return 0;
        }
        
        $line_height += $tmp_headers[0][0] ?: $step;
        goto start;
    }
    
    protected function checkHeaders($headers){
        $pre_header = null;
        foreach ($headers as $header){
            if(empty($header[1])){
                return false;
            }
            if(!$pre_header){
                $pre_header = $header;
                continue;
            }
            if($pre_header[1] == $header[1]){
                continue;
            }
            // check length
            if(mb_strlen( $pre_header[1]) - mb_strlen( $header[1]) > 4){
                return false;
            }
            
            // check diff
            $differ = new FineDiff($pre_header[1], $header[1]);
            $ops = $differ->getOps();
            if(!$this->checkOps( $ops )){
                return false;
            }
            
            $pre_header = $header;
        }
        return true;
    }
    
    protected function checkOps($ops){
        $count = 0;
        foreach ($ops as $op){
            if($op instanceof FineDiffCopyOp){
                continue;
            }
            if($op instanceof FineDiffReplaceOp
                //|| $op instanceof FineDiffDeleteOp // comment lại vì luôn xét trang nhỏ trước
                || $op instanceof FineDiffInsertOp
            ){
                if($count > 2){ // một số trường hợp phân trang bị detect thành 2 box text
                    return false;
                }
                $count++;
                if(!preg_match( "/^\d+|[iv]+$/", $op->getText())){
                    return false;
                }
            }
        }
        return true;
    }
    
    protected function cropText(Page $page, $top, $right, $bottom, $left){
        $text = [];
        $max_height = 0;
        $error = 2;
        /** @var Component $component */
        foreach ($page->components as $component){
            if($component->top > $top - $error
                && $component->left > $left - $error
                && $component->top + $component->height < $bottom + $error
                && $component->left + $component->width < $right + $error
            ){
                $text[] = trim( $component->text );
                $max_height = max( $max_height, $component->height );
            }
        }
        return [$max_height, implode( " ", $text)];
    }
    
    public function detectExtraLeft( Page ...$pages ) {
    
    }
    
    public function detectExtraRight( Page ...$pages ) {
    
    }
    
}