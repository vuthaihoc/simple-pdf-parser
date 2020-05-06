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
        'Introduction',
        'Introducción'
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

        dump($abstract);
        return $document;
    }

    public static function getAbstract(Document $document, $abstract_signs)
    {
        $content = '';
        $start = false;
        $end = false;
        foreach ($document->getPages() as $page_number => $page) {
            foreach ($page->getObjects() as $object) {
                if ($object instanceof Line) {
                    if($object->in_toc){
                        continue;
                    }
                    foreach ($abstract_signs as $abstract_sign) {
                        if ($start) {
                            break;
                        }
                        if (stripos(trim($object->text), $abstract_sign) === 0) {
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
