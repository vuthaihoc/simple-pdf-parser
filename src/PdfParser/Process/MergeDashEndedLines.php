<?php


namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Page;
use ThikDev\PdfParser\Objects\Text;

class MergeDashEndedLines  extends AbstractProcess
{
    protected $word;
    protected $document_content;

    static function apply(Document $document): Document
    {
        $process = new self();
        $process->document_content = $document->getText();
        foreach ($document->getPages() as $k => $page){
            $process->mergeWords($page);
        }

        return $document;
    }

    protected function mergeWords(Page $page){
        $lines = $page->objects;
        foreach ($lines as $k => $line){
            if(!empty($this->word)){
                foreach ($line->components as $component) {
                    if ($component instanceof Text){
                        $first_word = explode(" ", $component->text)[0];
                        $search_word = explode("-", $this->word)[0] . $first_word;
                        $replace_word = stripos($search_word, $this->document_content) !== false ? $search_word : $this->word . $first_word;
                        $component->text = str_replace($first_word, $replace_word , $component->text);
                        $component->raw = str_replace($first_word, $replace_word , $component->raw);
                        break;
                    }
                }
            }

            if( $this->endsWith($line->text, '-')){
                $texts = explode(" ", $line->text);
                $this->word = array_pop($texts);
                end($line->components)->text = str_replace($this->word, '' , end($line->components)->text);
                end($line->components)->raw = str_replace($this->word, '' . ' ', end($line->components)->raw);

            } else{
                $this->word = '';
            }

        }

    }

    protected function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

}
