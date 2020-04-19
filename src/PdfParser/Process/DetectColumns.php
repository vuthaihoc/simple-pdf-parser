<?php
/**
 * Xác định trang nhieu cot,
 * User: hocvt
 * Date: 2020-04-15
 * Time: 17:01
 */

namespace ThikDev\PdfParser\Process;


use ThikDev\PdfParser\Objects\Document;
use ThikDev\PdfParser\Objects\Page;

class DetectColumns extends AbstractProcess {
    
    public static function apply( Document $document ): Document {
        $process = new self;
        
        foreach ( $document->getPages( [1] ) as $k => $page ) {
            $page->columns = $process->detectPageColumns( $page );
            dump( "Page " . ( $k + 1 ) . " -> " . $page->columns );
        }
        
        dd( "DONE" );
        
        return $document;
    }
    
    protected function detectPageColumns( Page $page ): int {
        $vertical_middle = $page->left + $page->margin_left + (int) ( ( $page->width - $page->margin_left - $page->margin_right ) / 2 );
        
        $vertical_points = []; // các điểm trên đường thẳng giữa trang lưu tài điểm đó có thể là giữa cột hay không
        
        foreach ( $page->getLines() as $line ) {
            if ( $page->inFooter( $line ) || $page->inHeader( $line ) ) {
                continue;
            }
            if ( $line->left > $vertical_middle
                 || $line->left + $line->width < $vertical_middle
            ) {
                $vertical_points[ $line->top + $line->height ] = 2;
            } else {
//                dump( "=======" . $line->text, $vertical_middle, $line->left, $line->left + $line->width);
                $vertical_points[ $line->top + $line->height ] = 1;
            }
        }
        ksort( $vertical_points );
        
        $points_count = count( $vertical_points );
        $alias_columns = array_values( $vertical_points );// mảng chứa số lượng cột tại point
        $alias_points = array_keys( $vertical_points );// mảng chứa các point
        $min_height = 80;
        
        dump( $vertical_points );
        
        for ( $i = 0; $i < $points_count; $i ++ ) {
            if ( $alias_columns[ $i ] == 1 ) {
                continue;
            }
            $start_2_column_point = $alias_points[ $i ];
            dump( "Start " . $start_2_column_point . " " . $i );
            
            for ( $j = $i; $j < $points_count; $j ++ ) {
                dump( $alias_points[$j] . "----" . $j . " c " . $alias_columns[$j]);
                if($alias_columns[$j] == 1){
                    break;
                }
            }
    
            dump("End " . $alias_points[$j] . "----" . $j);
            
            if(
                $alias_points[$j-1] - $start_2_column_point < $min_height
//                &&
            ){// nếu ko đủ min height
                dump("Set to 1 from " . $i . " to " . $j);
                for(;$i<$j;$i++){
                    dump($i . "->" . $alias_points[$i]);
                    $vertical_points[$alias_points[$i]] = 1;
                }
            }else{// đủ điều kiện
                dd( "Distance " . ( $alias_points[$j-1] - $start_2_column_point));
                continue;
            }
        }
        
        dump( $vertical_points );
        
        return 1; // mặc định 1 cột
    }
    
}