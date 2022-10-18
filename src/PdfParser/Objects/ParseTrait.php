<?php

namespace ThikDev\PdfParser\Objects;

use voku\helper\UTF8;

trait ParseTrait
{
    public static function preParse($string, $tag, $has_close_tag = true) : array{
        if($has_close_tag && !str_ends_with($string, "</" . $tag . ">")){
            return [];
        }

        $fixed = false;

        start_parse:

        if($has_close_tag){
            if(preg_match( "/^\<".$tag."((\s+\S+=\"[^\"\>]+\")+)\>(.*)<\/".$tag.">/ui", $string, $matches)){
                $attributes = array_filter(explode("\" ", trim($matches[1])));
                $attributes = array_column(array_map(function ($attribute) {
                    list($key, $value) = explode("=", $attribute, 2);
                    return [$key, trim($value, "\"")];
                }, $attributes), 1, 0);
                $attributes['text'] = $matches[3];
                return $attributes;
            }else {
                if(!$fixed){
                    $fixed = true;
                    $string = UTF8::fix_utf8($string);
                    goto start_parse;
                }
                dump("Can not parse component (has close tag): " . $string);
            }
        }else{
            if(preg_match( "/^\<".$tag."((\s+\S+=\"[^\"\>]+\")+)\/?\>/ui", $string, $matches)){
                $attributes = array_filter(explode("\" ", trim($matches[1])));
                $attributes = array_column(array_map(function ($attribute) {
                    list($key, $value) = explode("=", $attribute, 2);
                    return [$key, trim($value, "\"")];
                }, $attributes), 1, 0);
                return $attributes;
            }else {
                if(!$fixed){
                    $fixed = true;
                    $string = UTF8::fix_utf8($string);
                    goto start_parse;
                }
                dump("Can not parse component : " . $string);
            }
        }
        return [];
    }
}