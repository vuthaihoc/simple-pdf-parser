<?php


namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Line;

class DetectConclusion extends AbstractProcess
{

    public static $conclusion_sign = [
        'CONCLUSIONS',
        'CONCLUSION',
        'KẾT LUẬN',
        'TỔNG KẾT',
        'CONCLUSIONES',
        'Conclusions',
        'Conclusión',
        'Kết luận',
        'Tổng kết',
        'Conclusiones',
    ];

    public static $conclusion_sign_2 = [

    ];


    public static function apply(Document $document): Document
    {
        $conclusion_sign_list = [self::$conclusion_sign];
        $conclusion = '';
        foreach ($conclusion_sign_list as $conclusion_sign) {
            $conclusion = self::getConclusion($document, $conclusion_sign);
            dump($conclusion);
            if (trim($conclusion))
                break;
        }

        $document->conclusion = $conclusion;
        return $document;
    }

    public static function getConclusion(Document $document, $conclusion_signs)
    {
        $content = '';
        $start = false;
        $force_restart = false;
        $end = false;
        foreach ($document->getPages() as $page_number => $page) {
            foreach ($page->getObjects() as $object) {
                if ($object instanceof Line) {
                    if ($object->in_toc) {
                        continue;
                    }
                    foreach ($conclusion_signs as $conclusion_sign) {
                        if ($force_restart)
                            break;
                        if (mb_strpos($object->text, 'KẾT LUẬN') !== false){
//                            dd($object);
                            self::isValidConclusion($object->text, $conclusion_sign);
                        }

                        // match exactly conclusion sign
                        if (self::isValidConclusion($object->text, $conclusion_sign)) {
                            $content = '';
                            $force_restart = true;
                            dump('force_restart');
                            break;
                        }
                        if (stripos(trim($object->text), $conclusion_sign) !== false && mb_strlen(trim($object->text)) < mb_strlen($conclusion_sign) + 10) {
                            $start = true;
                        }
                    }
                    if (($force_restart || $start) && strlen($content) < 512) {
                        $content .= $object->text . ' ';
                    }
                }
            }
            if ($end) {
                break;
            }
        }
        return $content;
    }

    static function isValidConclusion($text, $conclusion_sign)
    {
        $conclusion_length = mb_strlen($conclusion_sign);
        if(trim($text) === $conclusion_sign
            || mb_substr(trim($text), -$conclusion_length) === $conclusion_sign){
        }

        return (
                trim($text) === $conclusion_sign
                || mb_substr(trim($text), -$conclusion_length) === $conclusion_sign)
            && mb_strlen($text) < mb_strlen($conclusion_sign) + 10;
    }
}
