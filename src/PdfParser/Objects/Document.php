<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-01
 * Time: 14:32
 */

namespace ThikDev\PdfParser\Objects;


use ThikDev\PdfParser\Exceptions\ParseException;

class Document {

    protected $pages = [];
    protected $fonts = [];
    protected $path;

    protected $html_prefix = "<!DOCTYPE html>
<html lang=\"en-US\">
<head>
<title>{{name}}</title>
<meta charset=\"utf-8\">
<style>body{font-size: 18px;}
h2 {
font-size: xx-large;
}
h3{
font-size: x-large;
}</style>
<body>";
    protected $html_subfix = "</body>";
    protected $page_template = "<div data-page='{{number}}' style='padding: 20px;margin: 10px auto;max-width: 800px;'><p>Page {{number}}</p>{{content}}</div>";

    public $abstract;
    public $conclusion;
    public $outlines;

    public $nlp_data = [
//        'title' => '',
//        'alt_titles' => '',
//        'description' => '',
//        //...
    ];

    use HasMarginTrait;

    /**
     * Document constructor.
     *
     * @param array $pages
     * @param array $fonts
     */
    public function __construct( array $pages, array $fonts, $path ) {
        $this->pages = $pages;
        $this->fonts = $fonts;
        $this->path = $path;
    }

    public function pageCount() {
        return count( $this->pages );
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getPage( $index ): Page {
        if ( isset( $this->pages[ $index ] ) ) {
            return $this->pages[ $index ];
        } else {
            throw new ParseException( "Not found Page at index " . $index );
        }
    }

    public function getPagesList( ...$positions ): Array {
        return array_map( function ( $i ) {
            return $this->getPage( $i );
        }, $positions );
    }

    public function getPages( $indexes = [] ) {
        if ( empty( $indexes ) ) {
            foreach ( $this->pages as $k => $page ) {
                yield $k => $page;
            }
        } else {
            foreach ( $indexes as $index ) {
                yield $index => $this->getPage( $index );
            }
        }
    }

    /**
     * Get font by id
     * @param $font_id
     * @return Font
     * @throws ParseException
     */
    public function getFont($font_id ): Font {
        if ( isset( $this->fonts[ $font_id ] ) ) {
            return $this->fonts[ $font_id ];
        } else {
            throw new ParseException( "Not found Font at index " . $font_id );
        }
    }

    public function font_size($font_id){
        return $this->getFont($font_id) ? $this->getFont($font_id)->size : 0;
    }
    public function font_heading_level($font_id){
        return $this->getFont($font_id) ? $this->getFont($font_id)->level : 0;
    }

    public function arrangeFont()
    {
        if(empty($this->fonts))
            return;
        // compute distribution
        $total_chars = 0;
        /** @var Font $font */
        foreach ($this->fonts as $font){
            $total_chars += $font->chars;
        }
        foreach ($this->fonts as &$font){
            $font->distribution = (int)(100*$font->chars/$total_chars);
        }

        $latin_fonts = array_filter($this->fonts, function ($font){
            return $font->is_latin !== false;
        });
        $sorted_fonts = $this->detectLevel($latin_fonts);
        foreach ($sorted_fonts as $k => $v){
            $this->fonts[$k]->level = $v->level;
        }
        $non_latin_fonts = array_filter($this->fonts, function ($font){
            return $font->is_latin === false;
        });
        $sorted_fonts = $this->detectLevel($non_latin_fonts);
        foreach ($sorted_fonts as $k => $v){
            $this->fonts[$k]->level = $v->level;
        }

    }

    protected function detectLevel($fonts) : array {
        if(count($fonts) == 0){
            return [];
        }
        $tmp_fonts = $fonts;
        usort($tmp_fonts, function ($a, $b){
            return $a->chars < $b->chars;
        });;

        $normal_font_size = (int)reset($tmp_fonts)->size;
        $largest_font_size = $normal_font_size;

        $no_fonts = count( array_filter($this->fonts, function ($item) use ($normal_font_size){
            return (int) $item->size > $normal_font_size;
        }));

        foreach ($fonts as $_font) {
            if( (int) $_font->size > $largest_font_size)
                $largest_font_size = (int) $_font->size;
        }

        foreach ($fonts as &$font) {
            if ((int)$font->size >= $largest_font_size - 1 && $no_fonts > 2) {
                $font->level = 2;
            } elseif ((int)$font->size > $normal_font_size + ($font->distribution > 0 ? 1 : 2)) {
                // neu distribution nho thi tang sai so
                $font->level = 1;
            } elseif ((int)$font->size < $normal_font_size - 1) {
                // nho hon font normal 2 cỡ là nhỏ
                $font->level = -1;
            } else {
                $font->level = 0;
            }
        }
        return $fonts;
    }

    public function getAllFonts()
    {
        return $this->fonts;
    }

    public function getFonts( $indexes = [] ) {
        if ( empty( $indexes ) ) {
            foreach ( $this->fonts as $k => $font ) {
                yield $k => $font;
            }
        } else {
            foreach ( $indexes as $index ) {
                yield $index => $this->getFont( $index );
            }
        }
    }

    public function getText( $pages = [], $page_break = "\f\n", $page_prefix = "Page {{number}}\n" ) {
        $texts = [];
        foreach ( $this->getPages( $pages ) as $k => $page ) {
            $texts[] = str_replace( "{{number}}", $k + 1, $page_prefix ) . $page->getText();
        }

        return implode( $page_break, $texts );
    }

    public function getHtml( $name = "", $pages = [], $page_template = '', $html_prefix = '', $html_subfix = '' ) {
        $html = $html_prefix ?: $this->html_prefix;
        $name = $name ?: "html from pdf";
        $html = str_replace( "{{name}}", $name, $html );
        $page_template = $page_template ?: $this->page_template;
        /**
         * @var int $k
         * @var Page $page */
        foreach ( $this->getPages( $pages ) as $k => $page ) {
            $page_content = str_replace( '{{content}}', $page->getHtml(), $page_template );
            $page_content = str_replace( '{{number}}', $k + 1, $page_content );
            $html .= "\n" . $page_content;
        }
        $html .= $html_subfix ?: $this->html_subfix;

        return $html;
    }

    public function getReferences()
    {
        $references = [];
        $start = 0;
        $reference_length = 0;
        foreach ($this->getPages() as $page) {
            foreach ($page->getObjects() as $object) {
                if ($object->in_reference) {
                    $start++;
                    $reference_length = 0;
                    $references[$start] = $object->text;
                } elseif ($object->merge_up && $start && $reference_length < 10) {
                    $reference_length++;
                    $references[$start] .= $object->text;
                }
            }
        }
        return $references;
    }

}
