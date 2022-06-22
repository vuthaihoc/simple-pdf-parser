<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-03-25
 * Time: 13:05
 */

namespace ThikDev\PdfParser\Converter;


use Symfony\Component\Process\InputStream;
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

    public function convert($path, $first_page = 1, $last_page = -1, $output_hidden_text = true){
        $input = file_get_contents($path);
        return $this->convertStream($input, $first_page, $last_page, $output_hidden_text);
    }
    public function convertStream($input, $first_page = 1, $last_page = -1, $output_hidden_text = true){
        $command = [self::$bin,
            "-i",
            "-xml",
            "-f",
            $first_page,
            "-l",
            $last_page,
            "-q",
            "-nodrm",
            "-stdout",
            "-",
            "nonsense",
        ];
        if($output_hidden_text){
            $command = $this->array_insert_after($command, "-xml", "-hidden");
        }
        $_input = new InputStream();
        $this->process = new Process($command);
        $this->process->setTimeout(self::$timeout);
        $this->process->setInput($_input);
        $this->process->start();
        if(is_string($input)){
            $_input->write($input);
            $_input->close();
        }elseif (is_resource($input)){
            while (!feof($input)) {
                $_input->write(fread($input, 8192));
            }
            $_input->close();
        }
        $this->process->wait();
        $content = $this->output();
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

    public function array_insert_after(array $array, $insert_value , $new_value){
        $new = array();
        foreach ($array as $value) {
            $new[] = $value;
            if($value == $insert_value){
                $new[] = $new_value;
            }
        }
        return $new;
    }
}
