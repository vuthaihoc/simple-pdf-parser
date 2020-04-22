<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-20
 * Time: 00:49
 */

namespace ThikDev\PdfParser;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PdfToHtmlCommand extends \Symfony\Component\Console\Command\Command {
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'html';
    
    protected function configure()
    {
        $this->setDescription( "Convert pdf to html");
        $this->setHelp( "Parse pdf file sau đó chuyển sang html");
        $this->addArgument('input', InputArgument::REQUIRED, 'Input pdf file path');
        $this->addArgument('output', InputArgument::OPTIONAL, 'Output html file path');
    }
    
    protected function getDocument($path){
        if(!file_exists( $path )){
            throw new \InvalidArgumentException("File not found at " . $path);
        }
        $parser = new Parser( $path );
        return $parser->process();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdf_file = $input->getArgument( 'input');
        $document = $this->getDocument( $pdf_file );
        $output_file = $input->getArgument( 'output');
        if($output_file){
            file_put_contents( $output_file, $document->getHtml());
        }else{
            $output->writeln( $document->getHtml() );
        }
        return 0;
    }
}