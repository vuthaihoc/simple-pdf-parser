<?php

namespace ThikDev\PdfParser\Downloader;

class Downloader
{
    public $ids;
    public $overwrite;
    public $path ;
    public $api;
    public static $MAX_DIR_DEPTH = 2;

    public function __construct($ids, $overwrite = false, $api = 'http://ds.com/api/documents/xml/', $path = 'docs' )
    {
        $this->ids = $ids;
        $this->overwrite = $overwrite;
        $this->path = $path;
        $this->api = $api;
    }

    public function run()
    {
        $ids = explode("-", $this->ids);
        if(count($ids) !== 2){
            throw new \Exception('Document ids is not valid');
        }

        for($i = $ids[0]; $i <= $ids[1]; $i++){
            $file_name = self::make($i) ;
            $file_path = rtrim( $this->path, "/") . "/" . $file_name;
            $isInFolder = preg_match("/^(.*)\/([^\/]+)$/", $file_path, $filepathMatches);
            if($isInFolder) {
                $folderName = $filepathMatches[1];
                if (!is_dir($folderName)) {
                    mkdir($folderName, 0777, true);
                }
            }

            if(file_exists( $file_path ) && !$this->overwrite){
                throw new \Exception("File existed at " . $this->path);
            }

            if(strpos(get_headers($this->api . $i)[0], '404') !== false){
                dump('File not found: ' . $this->api . $i);
            } else {
                file_put_contents( $file_path, file_get_contents($this->api . $i));
            }
        }
    }


    public static function make(int $id, $ext = 'xml'){
        $multiplier = ( self::$MAX_DIR_DEPTH + 1 ) * 3 - strlen( (string) $id );
        if($multiplier >= 0){
            $full = str_repeat( "0", $multiplier) . $id;
        }else{
            $full = (string)$id;
        }
        $full = substr( $full, 0, -3);
        $path_partials = [];
        for($i = 1; $i < self::$MAX_DIR_DEPTH; $i++){
            $path_partials[] = substr( $full, -3*$i, 3);
        }
        $path_partials[] = substr( $full, 0, strlen( $full ) - 3*(self::$MAX_DIR_DEPTH-1) );
        $path_partials = array_reverse( $path_partials );
        return implode( "/", $path_partials ) . "/" . $id . ($ext ? "." . $ext : "");
    }
}
