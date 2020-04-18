<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-11
 * Time: 00:31
 */

namespace ThikDev\PdfParser\Process;


trait LoggerTrait {
    
    public static $logger_enabled = false;
    
    public function dump(...$vars){
        if(self::$logger_enabled){
            if(function_exists( 'var_dump')){
                dump(...$vars);
            }else{
                var_dump(...$vars);
            }
        }
    }
    
    public static function enableLog(){
        self::$logger_enabled = true;
    }
    
    public static function disableLog(){
        self::$logger_enabled = false;
    }
    
}