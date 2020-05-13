<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-13
 * Time: 13:13
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;
use ThikDev\PdfParser\Objects\Page;

class DetectReferences
{

    /** @todo phát hiện references và luôn merge up nếu không match prefix */

    public static $reference_pattern = [
        "/^(?<order>\d)\.\s*(?<reference>[A-Z][^A-Z].*\s?(\d\.\s)?.*)\s*/m",
        "/^\[(?<order>\d)\]\s*(?<reference>[A-Z][^A-Z].*)\s*/m"
    ];

    public static $reference_pattern_2 = [
        "/^(?<order>reference_order)\.\s*(?<reference>[A-Z][^A-Z].*\s?(\d\.\s)?.*)\s*/m",
        "/^\[(?<order>reference_order)\]\s*(?<reference>[A-Z][^A-Z].*)\s*/m"
    ];

    public static $references_sign = [
        '/\s*TÀI LIỆU THAM KHẢO\s*/',
        '/\s*Tài liệu tham khảo\s*/',
        '/\s*References\s*/',
        '/\s*REFERENCES\s*/',
        '/\s*references\s*/'
    ];


    public static function apply(Document $document)
    {
        $process = new self();
        [$first_page_have_reference, $reference_pattern] = $process->getFirstPagesHaveReference($document);
        $process->markReferenceLines($document, $first_page_have_reference, $reference_pattern);
        return $document;
    }

    protected function getFirstPagesHaveReference(Document $document)
    {
        $total_pages = $document->pageCount();
        $scores = [];
        $matched_pattern = [];

        foreach (self::$reference_pattern as $key => $item) {
            $matched_pattern[$key] = 0;
        }
        // Tìm trang bắt đầu phần tài liệu tham khảo, mỗi trang được
        foreach ($document->getPages() as $page_number => $page) {
            if ($page_number < $total_pages - 40) {
                // chỉ xét 40 trang cuối
                continue;
            }
            $scores[$page_number] = 0;
            $temp = 0;
            foreach ($page->getObjects() as $object) {

                foreach (self::$references_sign as $reference) {
                    if (preg_match($reference, $object->text)
                    || preg_match($reference, strtolower($object->text))) {
                        $scores[$page_number] += 150;
                        $temp = 1;
                        break;
                    }
                }
                foreach (self::$reference_pattern as $key => $item) {
                    if (preg_match($item, trim($object->text)) && $temp) {
                        $scores[$page_number] += 1;
                        $matched_pattern[$key] += 1;
                        break;
                    }
                }
            }
        }

        dump("Matched pattern: " . array_keys($matched_pattern, max($matched_pattern))[0]);
        dump("First page: " . array_keys($scores, max($scores))[0]);
//        dump([array_keys($scores, max($scores))[0], array_keys($matched_pattern, max($matched_pattern))[0]]);
//        dd($matched_pattern);

        return [array_keys($scores, max($scores))[0], array_keys($matched_pattern, max($matched_pattern))[0]];
    }

    public static function vi_to_en($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);

        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    protected function markReferenceLines(Document $document, $first_page_have_reference, $reference_pattern)
    {
        $end = false;
        $order_list = [];
        $start = false;

        foreach ($document->getPages() as $number => $page) {
            if($number < $first_page_have_reference)
                continue;
            $distance_to_previous_ref = 0;

            foreach ($page->getObjects() as $k => $line) {
                if ($line instanceof Line) {
                    $flag = false;
                    $ref = preg_replace("<reference_order>",
                        implode("|",
                            $this->nextPossibleOrder($order_list)),
                        self::$reference_pattern_2[$reference_pattern]);
                    if (preg_match($ref, trim($line->text), $matches)) {

                        $flag = true;
                        $start = true;
                        $order_list[] = $matches['order'];
                        $line->in_reference = true;
                        $distance_to_previous_ref = 0;
//                        break;
                    }
                    if(!$flag && $start){
                        $line->merge_up = true;
                        $distance_to_previous_ref++;
                    }
                    if ($distance_to_previous_ref > 10 && $start) {
                        $end = true;
                        break;
                    }
                }

            }
            if ($end) {
                break;
            }
        }
        dump(count($order_list));
    }

    protected function nextPossibleOrder($list)
    {
        if(empty($list)){
            return [1];
        }
        $count = count(array_filter($list, function ($a) {
            return $a == 1;
        }));
        if($count <= 1){
            return [ (int) end($list) + 1, 1];
        }
        return [(int) end($list) + 1];

    }

}
