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
        'Concluding Remarks',
        'CONCLUDING REMARKS',
    ];

    public static $conclusion_sign_2 = [

    ];


    public static function apply(Document $document): Document
    {
        $conclusion_sign_list = [self::$conclusion_sign];
        $conclusion = '';
        foreach ($conclusion_sign_list as $conclusion_sign) {
            $conclusion = self::getConclusion($document, $conclusion_sign);
            if (trim($conclusion))
                break;
        }

        dump($conclusion);
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
            // Chi xet 30% tai lieu (cac trang cuoi cung) neu tai lieu lon hon 100 trang
            if(($page_number < 0.7 * $document->pageCount() && $document->pageCount() > 100))
                continue;

            foreach ($page->getObjects() as $object) {
                if ($object instanceof Line) {
                    if ($object->in_toc) {
                        continue;
                    }
                    foreach ($conclusion_signs as $conclusion_sign) {
                        if ($force_restart)
                            break;
                        // match exactly conclusion sign
                        if (self::isValidConclusion($object, $conclusion_sign)) {
                            $content = '';
                            $force_restart = true;
                            break;
                        }
                        // Nếu đoạn text chứa dấu hiệu của conclusion và độ dài không qúa 10 dấu hiệu cuả conclusion
                        if (stripos(trim($object->text), $conclusion_sign) !== false
                            && mb_strlen(trim($object->text)) < mb_strlen($conclusion_sign) + 10) {
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


    /**
     * Kiểm tra đoạn text đầu vào có phải là conlusion hợp lệ không
     *
     * @param Line $line
     * @param $conclusion_sign
     * @return bool
     */
    static function isValidConclusion(Line $line, $conclusion_sign)
    {
        // 1. Có chứa dấu hiệu của conclusion và được in đậm
        if(mb_stripos(trim($line->text), $conclusion_sign) !== false
            && mb_stripos($line->raw, "<b>") !== false){
            return true;
        }

        // 2. Có chứa dấu hiệu của conclusion và được viết hoa.
        if(mb_stripos(trim($line->text), $conclusion_sign) !== false
            && ctype_upper($line->text)){
            return true;
        }

        // 3. Phần kết thúc đoạn text trùng khớp với dấu hiệu của conclusion
        $conclusion_length = mb_strlen($conclusion_sign);
        $text = str_replace(" ", "", $line->text);
        return (mb_substr($text, -$conclusion_length) === $conclusion_sign)
            && mb_strlen($text) < mb_strlen($conclusion_sign) + 10;
    }
}
