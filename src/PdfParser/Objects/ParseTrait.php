<?php

namespace ThikDev\PdfParser\Objects;

trait ParseTrait
{
    public static function preParse($string, $tag, $has_close_tag = true) : array{
        if($has_close_tag && !str_ends_with($string, "</" . $tag . ">")){
            return [];
        }

        if($has_close_tag){
            if(preg_match( "/^\<".$tag."((\s+\S+=\"[^\"\>]+\")+)\>(.*)<\/".$tag.">/ui", $string, $matches)){
                $attributes = array_filter(explode(" ", $matches[1]));
                $attributes = array_column(array_map(function ($attribute) {
                    list($key, $value) = explode("=", $attribute, 2);
                    return [$key, trim($value, "\"")];
                }, $attributes), 1, 0);
                $attributes['text'] = $matches[3];
                return $attributes;
            }else {
                dump("Can not parse component : " . $string);
            }
        }else{
            if(preg_match( "/^\<".$tag."((\s+\S+=\"[^\"\>]+\")+)\/?\>/ui", $string, $matches)){
                $attributes = array_filter(explode(" ", $matches[1]));
                $attributes = array_column(array_map(function ($attribute) {
                    list($key, $value) = explode("=", $attribute, 2);
                    return [$key, trim($value, "\"")];
                }, $attributes), 1, 0);
                return $attributes;
            }else {
                dump("Can not parse component : " . $string);
            }
        }
        return [];
    }
}