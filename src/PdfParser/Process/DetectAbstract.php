<?php


namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;

class DetectAbstract extends AbstractProcess
{

    public static $abstract_signs = [
        'TÓM TẮT',
        'TÓM TẮT LUẬN VĂN',
        'ABSTRACT',
        'Abstract',
        'Executive summary',
        'SUMMARY',
        'Summary',
    ];

    public static $abstract_signs_2 = [
        'INTRODUCTION',
        'Introduction',
        'Introducción',
    ];


    public static function apply(Document $document): Document
    {
        //Danh sách các dấu hiệu abstract với độ ưu tiên giảm dần, quy trình xử lý sẽ dừng ngay khi
        //tìm được abstract đầu tiên, nếu không tìm được abstract phù hợp sẽ xét đến mảng dấu hiệu abstract tiếp theo
        $abstract_signs_list = [self::$abstract_signs, self::$abstract_signs_2];
        $abstract = '';
        foreach ($abstract_signs_list as $abstract_sign) {
            $abstract = self::getAbstract($document, $abstract_sign);
            if (trim($abstract))
                break;
        }
        dump($abstract);
        $document->abstract = $abstract;
        return $document;
    }

    public static function getAbstract(Document $document, $abstract_signs)
    {
        $content = '';
        $start = false;
        $end = false;
        foreach ($document->getPages() as $page_number => $page) {
            foreach ($page->getObjects() as $object) {
                // Chi xet 20% tai lieu (cac trang dau tien) cua cac tai lieu lon hon 100 trang
                // Chi xet 40% tai lieu (cac trang dau tien) cua cac tai lieu nho hon 100 trang
                if(($page_number > 0.4 * $document->pageCount() && $document->pageCount() < 100)
                || ($page_number > 0.2 * $document->pageCount() && $document->pageCount() >= 100))
                    continue;
                if ($object instanceof Line) {
                    if($object->in_toc){
                        continue;
                    }
                    foreach ($abstract_signs as $abstract_sign) {
                        if ($start) {
                            break;
                        }
                        if (self::isValidAbstract($object, $abstract_sign)) {
                            $start = true;
                            break;
                        }
                    }
                    if ($start) {
                        $content .= $object->text . ' ';
                        if (strlen($content) > 512) {
                            $end = true;
                            $content = substr($content, 0, 512);
                            break;
                        }
                    }
                }
            }
            if ($end) {
                break;
            }
        }
        return $content;
    }

    /**
     * Kiểm tra đoạn text có phải là abstract hợp lệ không
     *
     * @param Line $line
     * @param $abstract_sign
     * @return bool
     */
    static function isValidAbstract(Line $line, $abstract_sign)
    {
        //1. Phần bắt đầu của đoạn text trùng khớp với dấu hiệu của abstract
        if(mb_stripos(trim($line->text), $abstract_sign) === 0
        || mb_stripos(str_replace(" ", "", $line->text), $abstract_sign) === 0){
            return true;
        }

        //2. Đoạn text có chứa dấu hiệu của abstract và được in đậm
        if(mb_stripos(trim($line->raw), $abstract_sign) !== false
            && mb_stripos($line->raw, "<b>") !== false){
            return true;
        }

        //3. Phần kết thúc của đoạn text trùng khớp với dấu hiệu của abstract
        $abstract_length = mb_strlen($abstract_sign);
        $text = str_replace(" ", "", $line->text);
        return (mb_substr($text, -$abstract_length) === $abstract_sign);
    }
}
