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
    
    protected $html_prefix = "<!DOCTYPE html>
<html lang=\"en-US\">
<head>
<title>{{name}}</title>
<meta charset=\"utf-8\">
<style>body{font-size: 18px;}</style>
<body>";
    protected $html_subfix = "</body>";
    protected $page_template = "<div data-page='{{number}}' style='padding: 20px;margin: 10px auto;max-width: 800px;'><p>Page {{number}}</p>{{content}}</div>";
    
    use HasMarginTrait;
    
    /**
     * Document constructor.
     *
     * @param array $pages
     * @param array $fonts
     */
    public function __construct( array $pages, array $fonts ) {
        $this->pages = $pages;
        $this->fonts = $fonts;
    }
    
    public function pageCount() {
        return count( $this->pages );
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
    
    public function getFont( $index ): Font {
        if ( isset( $this->fonts[ $index ] ) ) {
            return $this->fonts[ $index ];
        } else {
            throw new ParseException( "Not found Font at index " . $index );
        }
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
    
    public function getText( $pages = [], $page_break = "\f", $page_prefix = "Page {{number}}\n" ) {
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
        foreach ( $this->getPages( $pages ) as $k => $page ) {
            $page_content = str_replace( '{{content}}', $page->getHtml(), $page_template );
            $page_content = str_replace( '{{number}}', $k + 1, $page_content );
            $html .= "\n" . $page_content;
        }
        $html .= $html_subfix ?: $this->html_subfix;
        
        return $html;
    }
    
}