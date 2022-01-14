<?php


namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;
use ThikDev\PdfParser\Objects\Page;

class DetectNoiseContent extends AbstractProcess
{

    static function apply(Document $document): Document
    {
        $process = new self();
        $total_good_page_line = 0;
        $all_good_lines = 0;
        $total_words_in_good_lines = 0;
        $good_page = 0;
        foreach ($document->getPages() as $page_number => $page) {
            $page_good_lines = 0;
            foreach ($page->getMainLines() as $index => $line) {
                if($line->is_good){
                    $page_good_lines++;
                    $all_good_lines++;
                    $total_words_in_good_lines += count(explode(" ", $line->text));
                }
            }
            if(count($page->getMainLines()) * 0.7 < $page_good_lines){
                $good_page++;
                $total_good_page_line += $page_good_lines;
            }
        }
        if (empty($good_page)) return $document;
        $avg_line_per_page = $total_good_page_line/$good_page;
        $avg_word_per_good_line = $total_words_in_good_lines/$all_good_lines;
        foreach ($document->getPages() as $number => $page) {
            // chỉ xét các trang có độ dài lớn hơn độ dài trung bình của 1 trang 20%
            if(count($page->getMainLines()) > $avg_line_per_page * 1.2){
                $page->objects = $process->removeNoise($page, $number, $avg_word_per_good_line);
            }
        }
        return $document;
    }

    /**
     * Xóa bỏ các đoạn được xác định là noise
     * 1. Số lượng từ trên 1 dòng nhỏ hơn 50% so với số lượng từ trung bình của 1 dòng
     * 2. Số lượng ký tự trung bình của 1 từ nhỏ hơn 3
     * @param Page $page
     * @param $page_number
     * @param $avg_word_per_good_line
     * @return array
     */
    protected function removeNoise(Page $page, $page_number, $avg_word_per_good_line){
        $bad_lines = [];
        foreach ($page->getMainLines() as $index => $line) {
            if($this->isBadLine($line, $avg_word_per_good_line)){
                $bad_lines[] = $index;
            }
        }
        $bad_parts = [];
        $crr_part = [];
        $previous_line = 0;
        foreach ($bad_lines as $bad_line) {
            if($bad_line - $previous_line == 1){
                $crr_part[] = $bad_line;
            } else{
                if(count($crr_part) > 4){
                    foreach ($crr_part as $item) {
                        $bad_parts[] = $item;
                    }
                }
                $crr_part = [];
            }
            $previous_line = $bad_line;
        }

        // Tìm các đoạn từ 5 dòng trở lên là bad lines
        if(count($crr_part) > 4){
            foreach ($crr_part as $item) {
                $bad_parts[] = $item;
            }
        }
        $new_main_lines = [];
        foreach ($page->getMainLines() as $key => $mainLine) {
            if(!in_array($key, $bad_parts)){
                $new_main_lines[] = $mainLine;
            }
        }
        $page->objects = $new_main_lines;
        return $new_main_lines;

    }

    protected function isBadLine(Line $line, $avg_word_per_good_line){
        // Neu so luong tu trong 1 cau thap hon 50% so voi cau thong thuong.
        if(count(explode(" ", $line->text)) < 0.5 * $avg_word_per_good_line)
            return true;
        // Neu so luong chu cai trung binh trong 1 tu nho hon 3
        $text = preg_replace( '/[\W]/', '', $line->text);
        if ((strlen($text))/count(explode(" ", $line->text)) < 3)
            return true;
        return false;
    }

}
