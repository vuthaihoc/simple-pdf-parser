<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-09
 * Time: 15:37
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;
use ThikDev\PdfParser\Objects\Page;
use ThikDev\PdfParser\Objects\Text;

class MergeLines extends AbstractProcess {

    public static $words_separator = " ";
    public static $should_trim = ".,:/";


    /**
     * Lưu text của các dòng Line xịn (ít bị gãy)
     * @var array
     */
    protected $objects = [];
    /** @var Document */
    protected $document;

    protected function __construct(Document $document) {
        $this->document = $document;
        $this->getObjects( );
    }

    public static function apply( Document $document, $force = false ): Document {
        $process = new self($document);

        foreach ($document->getPages() as $k => $page){
            $process->mergeLinesInPage($page, $force);
        }

        return $document;
    }

    protected function mergeLinesInPage(Page $page, $force = false){
        $lines = $page->objects;

        foreach ($lines as $k => $line){
            if($line->merge_up !== null && !$force){// neu truoc do da quyet dinh gia tri merge up thi bo qua
                continue;
            }
            $line->merge_up = $this->shouldMergeUp( $page, $k );
        }
    }

    protected function shouldMergeUp(Page $page, $index) : bool {
        $line = $page->getObject($index);
        $pre_line = $index > 0 ? $page->getObject( $index - 1 ) : null;
        $nex_line = $index < $page->countObjects() - 2 ? $page->getObject( $index + 1 ) : null;

        $word_width = 50;
        $error = 2; // sai số chấp nhận được trong một số tính toán

        if(!$pre_line){ // dòng đầu tiên
            $this->dump( "Dòng đầu");
            return false;
        }

//        $line->ddIfContains( "Carcinoma", $line->text);

        if(!$this->isGoodLine( $line, $pre_line, $nex_line )){ // dòng không xịn
            $line->is_noise = true;
            $this->dump( "Dòng không xịn");
            return false;
        }

        if($pre_line->is_noise){
            $this->dump( "Dòng trên không xịn");
            return false;
        }

        if($pre_line->components){ // quá xa dòng trên
            $pre_line_height_by_font = 0;
            $pre_line_height_by_text = 0;
            foreach ($pre_line->components as $component) {
                $pre_line_height_by_font = max($pre_line_height_by_font, $this->document->getFont($component->font_id)->line_height);
                $pre_line_height_by_text = max($pre_line_height_by_text, (int) ceil( $component->height *  1.6));
            }

            if ($pre_line_height_by_font > $pre_line_height_by_text) {
                if ($line->top > $pre_line->top + $pre_line->line_height * 1.2 ) {
                    return false;
                }

                } else {
                if ($line->top > $pre_line->top + $pre_line->line_height * 2 ) {
                    return false;
                }
            }
        } else {
            if ($line->top > $pre_line->top + $pre_line->line_height * 1) {
                return false;
            }
        }

        // dòng trên thụt bên phải quá 1 word
        /** @todo kiem tra xem doan van thuoc loai align co phai justify khong de giam word-width */
        if($pre_line->merge_up){
            $pre_pre_line = $page->getObject( $index - 2 );
            if($pre_pre_line->left + $pre_pre_line->width - $pre_line->left - $pre_line->width > $word_width){
                $this->dump( "Dòng trên thụt phải quá 1 từ so với dòng trước đó");
                return false;
            }
        }
        if($line->left + $line->width - $pre_line->left - $pre_line->width > $word_width){
            $this->dump( "Dòng trên thụt phải quá 1 từ so với dòng hiện tại");
            return false;
        }

        /** @todo kiem tra xem co phai list khong */

        // indent sai khac dòng trên đã merge up
        /** @todo xac dinh so cot de su dung margin right thay cho left + width cua dong truoc do duoc merge */
        if($pre_line->merge_up){
            $pre_pre_line = $page->getObject( $index - 2 );
            if($pre_line->left < $line->left - 2/2){ // indent sâu hơn dòng trên đã merge up
                $diff1 = abs($pre_line->left + $pre_line->width - $pre_pre_line->left - $pre_pre_line->width);
                $diff2 = abs($pre_pre_line->left + $pre_pre_line->width - $line->left - $line->width);
                if($diff1 > 2 || $diff2 > 12){ // $diff2 > 12 vì có thể các dòng trên chứa dấu cách ở cuối
                    $this->dump( "Không căn phải và indent sâu hơn dòng trên đã merge up");
                    return false;
                }else{
                    // căn phải
                }
            }
            $diff1 = abs($pre_pre_line->left + $pre_line->left);
            $diff2 = abs($pre_line->left - $line->left);
            if($diff1 < 2 && $diff2 > 1){
                return false;
            }
        }

        // check font
        if(!$this->isSimilarStyle( $pre_line->lastNormalText(), $line->firstNormalText())){
            return false;
        }

        // @todo xa dong tren hon dong duoi

        //

        return true;
    }

    protected function isSimilarStyle(Text $text1, Text $text2){
        $font1 = $this->document->getFont( $text1->font_id );
        $font2 = $this->document->getFont( $text2->font_id );
        if(abs( $font1->size - $font2->size ) > 2){
            return false;
        }
        return true;
    }


    /**
     * Chuẩn bị các dòng "xịn" để tính toán merge line đang confuse
     */
    protected function getObjects(){
        foreach ($this->document->getPages() as $page){
            foreach ($page->objects as $object){
                if($this->isGoodLine( $object )){
                    if(count( explode( self::$words_separator, $object->text )) > 1){
                        $this->objects[] = self::$words_separator . $object->text . self::$words_separator;
                    }
                }
            }
        }
    }

    /**
     * Dòng được đánh giá là tốt nếu
     * - Chứa không quá nhiều phần (component)
     * - Padding left không quá 100px so với các dòng trên/dưới
     *
     * @done neu chua nhieu thanh phan thi phai kiem tra mat do va do lech dong => Tỉ lệ ký tự trên dòng quá loãng
     *
     * @param Line $line
     * @param Line|null $pre_line
     * @param Line|null $next_line
     *
     * @return bool
     */
    protected function isGoodLine(Line $line, Line $pre_line = null, Line $next_line = null){

        if($line->width == 0){
            return false;
        }

        /**
         * Tỉ lệ ký tự trên dòng quá loãng
         * Chi xet voi dong nhieu hon 1 thanh phan
         */
        if(count($line->components) > 1){
            $font = $this->document->getFont( $line->commonFont() );
            $chars_to_fit = $font->charsToFit( $line->width );
            if( $chars_to_fit && mb_strlen( $line->text )/$chars_to_fit < 0.65){
//                dump($line->text . " ====== " . $line->width . ($pre_line ? $pre_line->text : "") . " Fit " . (mb_strlen( $line->text )/$chars_to_fit) );
                return false;
            }
        }

        /** can trai so voi cac dong truoc do qua nhieu
         *
         *
         *
         */
//        if($pre_line && $next_line){
//            if($line->left + $line->width - $pre_line->left - $pre_line->width > 1 &&
//               max($line->left - $pre_line->left, $line->left - $next_line->left) > 100) {
//                return false;
//            }
//        }
        $line->is_good = true;
        return true;
    }

}
