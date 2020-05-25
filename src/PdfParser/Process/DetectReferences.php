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
        "/^(?<order>\d{1,3})\.\s*(?<reference>[A-Z].*)/m",
        "/^\[(?<order>\d{1,3})\]\s*(?<reference>[A-Z].*)/m",
        "/^\((?<order>\d{1,3})\)\s*(?<reference>[A-Z].*)/m",
        "/^(?<reference>[A-Z].*)\s*(?<year>\(?\d{4}\w?\)?)\s*.*/m",
    ];

    public static $reference_pattern_2 = [
        "/^(?<order>reference_order)\.\s*(?<reference>[A-Z].*)/m",
        "/^\[(?<order>reference_order)\]\s*(?<reference>[A-Z].*)/m",
        "/^\((?<order>reference_order)\)\s*(?<reference>[A-Z].*)/m",
        "/^(?<reference>[A-Z].*)\s*(?<year>\(?\d{4}\w?\)?)\s*.*/m",
    ];

    public static $references_sign = [
        '/TÀI LIỆU THAM KHẢO$/',
        '/Tài liệu tham khảo$/',
        '/References$/',
        '/REFERENCES$/',
        '/REFERENCE$/',
        '/REFERENCE$/',
        '/references$/'
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
            if ($page_number < $total_pages - 70) {
                // chỉ xét 70 trang cuối
                continue;
            }
            $scores[$page_number] = 0;
            $temp = 0;
            foreach ($page->getObjects() as $object) {
//                if($page_number == 1){
//                    dump($object->text);
//                }

                foreach (self::$references_sign as $reference) {
                    if (preg_match($reference, trim($object->text))
                    || preg_match($reference, trim(strtolower($object->text)))
                    || preg_match($reference, str_replace(" ", "", $object->text))) {
//                        dump($object->text);
                        $scores[$page_number] += 150;
                        $temp = 1;
                        break;
                    }
                }

                foreach (self::$reference_pattern as $key => $item) {

//                    if($temp && $key ==1){
//                        dump($item);
//                        dump($object->text);
//                    }


                    if (preg_match($item, trim($object->text)) && $temp) {

                        $scores[$page_number] += 1;
                        $matched_pattern[$key] += 1;
//                        break;
                    }
                }
            }
        }

//        dump("Matched pattern: " . array_keys($matched_pattern, max($matched_pattern))[0]);
//        dump("First page: " . array_keys($scores, max($scores))[0]);
//        dump([array_keys($scores, max($scores))[0], array_keys($matched_pattern, max($matched_pattern))[0]]);
//        dd($matched_pattern);
//        dd($scores);

        return [array_keys($scores, max($scores))[0], array_keys($matched_pattern, max($matched_pattern))[0]];
    }


    protected function markReferenceLines(Document $document, $first_page_have_reference, $reference_pattern)
    {
        $end = false;
        $order_list = [];
        $start = false;
        $count_refs = 0;

        if($first_page_have_reference == 0)
            return;
        foreach ($document->getPages() as $number => $page) {
            if($number < $first_page_have_reference)
                continue;
            $distance_to_previous_ref = 0;

            foreach ($page->getObjects() as $k => $line) {
                if ($line instanceof Line) {
                    $ref = preg_replace("<reference_order>",
                        implode("|",
                            $this->nextPossibleOrder($order_list)),
                        self::$reference_pattern_2[$reference_pattern]);
                    if (preg_match($ref, trim(self::vi_to_en($line->text)), $matches)) {
                        $start = true;
//                        dump($line->text);
                        if(!empty($matches['order'])){
                            $order_list[] = $matches['order'];
                        }
                        $count_refs++;
                        $line->in_reference = true;
                        $distance_to_previous_ref = 0;
                    }
                    if( $start && !$line->in_reference){
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
//        dump($order_list);
//        dump($count_refs);
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
            return [ (int) end($list), (int) end($list) + 1, (int) end($list) + 2, 1];
        }
        return [(int) end($list), (int) end($list) + 1, (int) end($list) + 2];

    }

    public static function vi_to_en($str)
    {
        $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
        $str = preg_replace("/“/", '', $str);
        $str = preg_replace("/”/", '', $str);
        return $str;
    }


}
