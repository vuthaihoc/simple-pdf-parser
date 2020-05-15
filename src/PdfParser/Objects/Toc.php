<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-29
 * Time: 13:35
 */

namespace ThikDev\PdfParser\Objects;


class Toc {
    
    protected $name = [];
    
    protected $items = [];
    
    /**
     * Toc constructor.
     *
     * @param array $name array of Line
     */
    public function __construct( $name = [], $items = [] ) {
        $this->name = $name;
        foreach ( $items as $item ) {
            $this->addItem( $item );
        }
    }
    
    /**
     * @return array
     */
    public function getName() {
        return $this->name;
    }
    public function getNameText() {
        return implode( " ", array_map( function ( $line ) {
            return $line->text;
        }, $this->getName() ) );
    }
    
    public function setName( $name ): self {
        $this->name = $name;
        return $this;
    }
    
    public function getItems() : array {
        return $this->items;
    }
    
    public function setItems($items = []) : self {
        $this->items = [];
        foreach ( $items as $item ) {
            $this->addItem( $item );
        }
        return $this;
    }
    
    public function addItem(Line $line) : self {
        $this->items[] = $line;
        return $this;
    }
    
    public function merge(Toc ...$tocs) : self {
        foreach ($tocs as $toc){
            foreach ($toc->getItems() as $item){
                $this->addItem( $item );
            }
        }
        return $this;
    }
    
}