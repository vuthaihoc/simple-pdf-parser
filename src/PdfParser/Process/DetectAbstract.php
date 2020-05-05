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
    ];

    public static $abstract_signs_2 = [
        'INTRODUCTION',
        'Introduction'
    ];

    public static $conclusion_sign = [
        'CONCLUSIONS',
        'CONCLUSION',
        'Conclusion',
        'Conclusions',
        'KẾT LUẬN',
        'Kết luận',
        'TỔNG KẾT',
        'Tổng kết'
    ];


    public static function apply(Document $document): Document
    {
        $abstract_signs_list = [self::$abstract_signs, self::$abstract_signs_2];
        $abstract = '';
        foreach ($abstract_signs_list as $abstract_sign) {
            $abstract = self::getAbstract($document, $abstract_sign);
//            dump($abstract);
            if (trim($abstract))
                break;
        }
        $document->abstract = $abstract;

        $conclusion_sign_list = [self::$conclusion_sign];
        $conclusion = '';
        foreach ($conclusion_sign_list as $conclusion_sign) {
            $conclusion = self::getAbstract($document, $conclusion_sign, 'conclusion');
            dump($conclusion);
            if (trim($conclusion))
                break;
        }

        $document->conclusion = $conclusion;
        return $document;
    }

    public static function getAbstract(Document $document, $abstract_signs, $type = 'abstract')
    {
        $content = '';
        $start = false;
        $end = false;
        foreach ($document->getPages() as $page_number => $page) {
            foreach ($page->getObjects() as $object) {
                if ($object instanceof Line) {
                    foreach ($abstract_signs as $abstract_sign) {
                        if ($start) {
                            break;
                        }
                        if (stripos(trim($object->text), $abstract_sign) === 0 && $type == 'abstract') {
                            $start = true;
                            break;
                        }
                        if (stripos(trim($object->text), $abstract_sign) !== false && $type = 'conclusion') {
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
}
