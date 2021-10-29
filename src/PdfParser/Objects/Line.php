<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-08
 * Time: 16:35
 */

namespace ThikDev\PdfParser\Objects;


class Line extends Component {
    
    // @done them null lam diem khoi dau de inject mot so process tuy bien xac dinh truoc co merge hay khong
    // Ví dụ TOC hoặc Refernces
    public $merge_up = null;
    public $is_noise = false;// một số dòng nhiễu sẽ không được merge thành paragraph
    public $in_toc = false;// đánh dấu dòng thuộc phần TOC
    public $in_reference = false;// đánh dấu dòng thuộc phần references
    public $is_good = false;

    // xác định cột của dòng hiện tại, null là confuse, 0 là phần tài liệu 1 cột, 1,2,3 là số cột tương ứng với trang có nhiều cột
    public $column = null;
    
    public $line_height = 0;
    
    public $begin_indent = 0;
    public $end_indent = 0;

    public $v_pos = 0;
    public $heading_level = 0;
    public $font_size = 0;
    public $font_name = '';
    
    public static function fromText( Text $text ) {
        $line = new Line( $text->top, $text->left, $text->width, $text->height );
        
        $line->components[] = $text;
        
        $line->begin_indent = $text->left;
        $line->end_indent = $text->left + $text->width;
        
        $line->text = $text->text;
        $line->raw = $text->raw;
        
        $line->line_height = (int)ceil( $text->height * 1.6);
        
        return $line;
    }
    
    public static function fromTexts( Text $text, Text ...$texts ) {
        $line = self::fromText( $text );
        foreach ( $texts as $text ) {
            $line->appendText( $text );
        }
        
        return $line;
    }
    
    public function appendText( Text $text ) {
        $this->left = min( $this->left, $text->left );
        $this->width = max( $text->left + $text->width - $this->left, $this->width );
        if ( $this->top + $this->height < $text->top + $text->height ) {
            // thêm text dòng mới
            $this->height = $text->top + $text->height - $this->top;
            $this->end_indent = $text->left + $text->width;
            $separate = " ";
        } else {
            if($text->left + $text->width - $this->end_indent < 1){
//                dd($this->text, $text->text);
                $separate = "";
            }else{
                $separate = " ";
            }
            $this->end_indent = max( $text->left + $text->width, $this->end_indent );
        }
        $this->text .= $separate . $text->text;
        $this->components[] = $text;
        return $this;
    }
    
    public function lastComponent(): ?Component {
        if ( $count = count( $this->components ) ) {
            return $this->components[ $count - 1 ];
        }
    }
    
    public function lastNormalText(): ?Component {
        if ( $count = count( $this->components ) ) {
            for ( $i = $count - 1; $i >= 0; $i -- ) {
                if ( $this->components[ $i ]->v_pos == Text::V_POS_NORMAL ) {
                    return $this->components[ $i ];
                }
            }
            
            return $this->components[ $count - 1 ];
        }
    }
    
    public function firstComponent(): ?Component {
        if ( $count = count( $this->components ) ) {
            return $this->components[0];
        }
    }
    
    public function firstNormalText(): ?Component {
        foreach ( $this->components as $component ) {
            if ( $component->v_pos == Text::V_POS_NORMAL ) {
                return $component;
            }
        }
        
        return $this->firstComponent();
    }
    
    /**
     * @todo doi voi tai lieu toan can so sanh them top/height de xac dinh chinh xac hon thanh phan truoc
     *    sau hoac tinh nang nay co the bat tat theo ngon ngu
     *
     */
    public function reorderComponents() {
        if($this->components[0]->width == 0){
            return;
        }
        $sort = usort( $this->components, function ( $component1, $component2 ) {
            if ( $component1->left == $component2->left ) {
                return 0;
            }
            
            return $component1->left < $component2->left ? - 1 : 1;
        } );
        if ( $sort ) {
            $this->text = implode( " ", array_map( function ( $component ) {
                return $component->text;
            }, $this->components ) );
        }
    }
    
    public function detectLineHeight( Document $document ) {
        $line_height = $this->line_height;
        foreach ( $this->components as $component ) {
            $line_height = max( $line_height, $document->getFont( $component->font_id )->line_height );
        }
//        dump( $line_height );
        $this->line_height = $line_height;
    }
    
    public function getHtml(){
        $html = "";
        $pre_component = null;
        /** @var Text $component */
        foreach ($this->components as $component){
            
            if($component instanceof Text){
                if($pre_component && $component->left - $pre_component->left - $pre_component->width > 5){
                    $html .= " ";
                }
                $html .= $component->html();
            }
            $pre_component = $component;
            
        }
        $html = preg_replace( "/(\.\s*|\-\s*|\s\s|…\s*|·\s*)\g{1}{4,}/u", "$1$1$1", $html);
        $html = str_replace( "</b><b>", "", $html);
        $html = str_replace( "</i><i>", "", $html);
        $html = str_replace( "</strong><strong>", "", $html);
        $html = str_replace( "</em><em>", "", $html);
        $html = preg_replace( "/\<\/h\d\>\s?\<h\d\>/", " ", $html);
        return $html;
    }
    
    public function commonFont() : int {
        $font = null;
        foreach ($this->components as $component){
            if($font === null){
                $font = $component->font_id;
            }
            if(mb_strlen( $component->text ) > 4){
                $font = $component->font_id;
                break;
            }
        }
        return $font;
    }
    
}
