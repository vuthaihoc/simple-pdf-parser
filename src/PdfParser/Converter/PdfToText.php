<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-03-25
 * Time: 13:05
 */

namespace ThikDev\PdfParser\Converter;


use Symfony\Component\Process\Process;

class PdfToText {
    
    public static $bin = "pdftohtml";
    public static $timeout = 69;// 69 seconds
    /** @var Process */
    protected $process;
    protected $command;
    protected $tmp;
    
    
    public function __construct() {
    
    }
    
    protected function getProcess($command, $timeout){
        $process = new Process( $command );
        $process->setTimeout( $timeout );
        return $process;
    }
    
    public function convert($path, $first_page = 1, $last_page = -1){
    
        $this->tmp = tempnam(self::getTempDir(), "pdftohtml_") . ".xml";
        
        $command = [self::$bin,
            "-c",
//            "-i",
            "-s",
            "-xml",
            "-f",
            $first_page,
            "-l",
            $last_page,
            "-q",
            $path, $this->tmp];
        $this->run( $command );
        $content = file_get_contents( $this->tmp );
        @unlink($this->tmp);
        return $content;
    }
    
    public function __destruct()
    {
        @unlink($this->tmp);
    }
    
    
    protected function run($command)
    {
        $this->command = $command;
        $this->process = $this->getProcess( $this->command, self::$timeout);
        $this->process->run();
        $this->validateRun();
        
        return $this;
    }
    
    protected function validateRun()
    {
        $status = $this->process->getExitCode();
        $error  = $this->process->getErrorOutput();
        
        if ($status !== 0) {
            throw new \RuntimeException(
                sprintf(
                    "The exit status code %s says something went wrong:\n stderr: %s\n stdout: %s\ncommand: %s.",
                    $status,
                    $error,
                    $this->process->getOutput(),
                    $this->process->getCommandLine()
                )
            );
        }
    }
    
    protected function output()
    {
        return $this->process->getOutput();
    }
    
    public static function getTempDir()
    {
        if (function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();
        } elseif (
            ($tmp = getenv('TMP')) ||
            ($tmp = getenv('TEMP')) ||
            ($tmp = getenv('TMPDIR'))
        ) {
            return realpath($tmp);
        } else {
            return '/tmp';
        }
    }
}
