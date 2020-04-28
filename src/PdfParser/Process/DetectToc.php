<?php
/**
 * Ý tưởng : 3 dòng liên tiếp có dấu hiệu in toc thì xác định là trang chứa TOC, sau đó ghép dòng bằng cách phân đoạn
 * bằng các dâu hiệu in toc
 *
 * User: hocvt
 * Date: 2020-04-15
 * Time: 00:20
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;
use ThikDev\PdfParser\Objects\Page;

class DetectToc {
    
    /**
     * Match các dòng chứa các dấu thường dùng trong TOC và kết thúc là số trang
     * @var string
     */
    public static $toc_pattern = "/(\.\s*|\-\s*|\s\s|…\s*|·\s*)\g{1}{4,}\W*[0-9ivx]+\W*$/u";
    
    /** @var string Dung chuoi nay khi chac chan trang chua toc de phan cach chuan xac hon */
    public static $toc_pattern_alt = "/(\.\s*|\-\s*|\s\s|…\s*|·\s*)\g{1}{1,}\W*[0-9ivx]+\W*$/u";
    
    use LoggerTrait;
    
    public static function apply( Document $document ): Document {
        $process = new self();
        $pagesHaveToc = $process->getPagesHaveToc( $document );
        
        foreach ($document->getPagesList(...$pagesHaveToc) as $page){
            $process->markTocLines( $page );
        }
        return $document;
    }
    
    protected function  getPagesHaveToc(Document $document){
        
        $total_pages = $document->pageCount();
        $pages = [];
        /**
         * @var int $page_number
         * @var Page $page
         */
        foreach ($document->getPages() as $page_number => $page){
            if($page_number > 20 && $page_number < $total_pages - 20){
                // chỉ xét 20 trang đầu và 20 trang cuối
                continue;
            }
            if($this->hasToc( $page )){
                $pages[] = $page_number;
            }
        }
        return $pages;
    }
    
    protected function hasToc(Page $page){
        $matched_count = 0;
        foreach($page->getObjects() as $line){
            if(preg_match( self::$toc_pattern,$line->text)){
                $matched_count++;
            }else{
                $matched_count=0;
            }
            if($matched_count > 3){
                return true;
            }
        }
        return false;
    }
    
    protected function markTocLines(Page $page){
        $started = false;
        $buffer_lines = [];
        foreach ($page->getObjects() as $k => $line){// duyet cac dong trong trang
            if($line instanceof Line && preg_match( self::$toc_pattern_alt,$line->text)){ // neu match voi pattern
                if(!$started){// neu chua bat dau
                    $started = true; // danh dau
                }elseif($started){// neu dat dau roi thi danh dau merge up cho cac dong va reset buffer
                    $buffer_lines[] = $line;
                    /**
                     * @var int $i
                     * @var Line $line
                     */
                    foreach ($buffer_lines as $i => $buffer_line){
                        $buffer_line->in_toc = true; // danh dau thuoc TOC
                        if($i>0){// bo qua dong dau tien
                            $buffer_line->merge_up = true;
                        }else{
                            $buffer_line->merge_up = false;// danh dau de sau do khong xet merge up cho dong nay
                        }
                    }
                    $buffer_lines = [];
                }
            }elseif($started){
                $buffer_lines[] = $line;
            }
        }
    }
    
}
