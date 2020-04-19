<?php
/**
 * Parse từ pdf -> xml(pdftohtml) -> Document object
 * User: hocvt
 * Date: 2020-04-01
 * Time: 16:31
 */

namespace ThikDev\PdfParser;


use ThikDev\PdfParser\Converter\PdfToText;
use ThikDev\PdfParser\Exceptions\ParseException;
use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Font;
use ThikDev\PdfParser\Objects\Page;
use ThikDev\PdfParser\Objects\Text;
use ThikDev\PdfParser\Process\DetectColumns;
use ThikDev\PdfParser\Process\DetectExtraContent;
use ThikDev\PdfParser\Process\DetectHeading;
use ThikDev\PdfParser\Process\DetectTable;
use ThikDev\PdfParser\Process\DetectToc;
use ThikDev\PdfParser\Process\FontClassify;
use ThikDev\PdfParser\Process\DetectMargin;
use ThikDev\PdfParser\Process\MergeComponents;
use ThikDev\PdfParser\Process\MergeLines;

class Parser {
    
    protected $path;
    protected $first_page;
    protected $last_page;
    public static $word_separate = " ";
    protected $xml;
    
    
    protected $pipeline = [
        DetectMargin::class,/** Tính toán margin cho các trang */
        FontClassify::class,/** Tính toán font mặc định */
        DetectExtraContent::class,/** Tính toán phần header/footer */
        MergeComponents::class,/** Kết hợp các Text box thành các dòng Line */
//        DetectColumns::class,/** Xác định số cột và vị trí các Line */
        DetectToc::class,/** Phát hiện Table of content */
        DetectTable::class,/** Tính toán một số khu vực là dạng bảng */
        MergeLines::class,/** Merge các line thành các đoạn văn */
        DetectHeading::class,/** Merge các line thành các đoạn văn */
    ];
    
    public function getPipeline(){
        return $this->pipeline;
    }
    
    /**
     * Thêm process vào trước các process truyền vào
     *
     * @param $process
     * @param null ...$before_processes
     *
     * @return int|false khi không tìm được before_processe nào thì trả về false
     */
    public function addProcessBefore($process, ...$before_processes) : int {
        if(empty( $before_processes )){
            array_unshift( $this->pipeline, $process );
        }
        foreach ($this->pipeline as $k => $p){
            if(in_array( $p, $before_processes)){
                break;
            }
        }
        if($k == count( $this->pipeline )){
            return false;
        }else{
            array_splice( $this->pipeline, $k, 0, [$process]);
            return $k;
        }
    }
    
    /**
     * Thêm process vào sau các process truyền vào
     *
     * @param $process
     * @param array $after_processes
     *
     * @return false|int
     */
    public function addProcessAfter($process, ...$after_processes){
        $this->pipeline = array_reverse( $this->pipeline );
        $result = $this->addProcessBefore( $process, ...$after_processes);
        $this->pipeline = array_reverse( $this->pipeline );
        return $result == null ? $result : ( count( $this->pipeline ) - 1 - $result );
    }
    
    /**
     * Dùng để ghi đè, thay thế core process
     * @param $search
     * @param $replacement
     *
     * @return bool
     */
    public function replaceProcess($search, $replacement) : bool {
    
    }
    
    /**
     * Parser constructor.
     *
     * @param $path
     * @param int $last_page
     * @param int $first_page
     */
    public function __construct( $path, $last_page = 100, $first_page = 1 ) {
        $this->path = $path;
        $this->first_page = $first_page;
        $this->last_page = $last_page;
    }
    
    /**
     * Luồng chính chạy các process để xử lý từ pdf -> xml -> Document -> perfect Document
     * @throws ParseException
     */
    public function process(): Document {
        
        $this->xml = ( new PdfToText() )->convert( $this->path, $this->first_page, $this->last_page );
        
        /** Tạo Document object cơ bản từ pdf -> xml -> Document */
        $document = $this->makeSimpleDocument();
        
        foreach ($this->pipeline as $process){
            $process::apply($document);
        }
        
        return $document;
        
    }
    
    public function getXml(): string {
        return $this->xml;
    }
    
    protected function parseComponents() {
    
    }
    
    protected function makeSimpleDocument() {
        $lines = explode( "\n", $this->xml );
        /** @var Font[] $fonts */
        $fonts = [];
        $fonts_width = [];
        $pages = [];
        
        $page_start = false;
        $page_buffer = null;
        $last_text = null;
        foreach ( $lines as $line ) {
            // font define
            if ( preg_match( "/^\s*\<fontspec\s/", $line ) ) {
                $font = Font::parse( $line );
                if ( ! $font ) {
                    throw new ParseException( "Parse font error " . $line );
                }
                $fonts[ $font->id ] = $font;
                $fonts_width[ $font->id ] = 0;
                continue;
            }
            
            // page start
            if ( preg_match( "/^\s*\<page\snumber=\"/", $line ) ) {
                $page_start = true;
                $page_buffer = Page::parse( $line );
                $last_text = null;
                continue;
            }
            
            // page content
            if ( $page_start && $page_buffer && preg_match( "/^\s*\<text\stop=\"/", $line ) ) {
                $text = Text::parse( $line );
                $fonts[ $text->font_id ]->chars += mb_strlen( $text->text );
                $fonts_width[ $text->font_id ] += $text->width;
                if ( $last_text && $text->font_id == $last_text->font_id ) {
                    $line_height = $text->top - $last_text->top;
                    if ( $line_height > $fonts[ $text->font_id ]->line_height
                         && $line_height < $fonts[ $text->font_id ]->size * 3
                    ) {
                        $fonts[ $text->font_id ]->line_height = $line_height;
//                        dump($line_height . "========" . $fonts[$text->font_id]->size . "=========" . $text->font_id);
                    }
                }
                $last_text = $text;
                $page_buffer->components[] = $text;
                continue;
            }
            
            // page end
            if ( trim( $line ) == '</page>' ) {
                $pages[] = $page_buffer;
                $page_start = false;
                $page_buffer = null;
                continue;
            }
        }
        
        foreach ( $fonts as &$font ) {
            if ( $font->chars == 0 ) {
                continue;
            }
            $font->char_width = round( $fonts_width[ $font->id ] / $font->chars, 2 );
        }
        
        $document = new Document( $pages, $fonts );
        
        return $document;
        
    }
    
}