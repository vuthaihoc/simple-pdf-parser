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

class PdfToTextCommand extends \Symfony\Component\Console\Command\Command {
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'text';
    
    protected function configure()
    {
        $this->setDescription( "Convert pdf to text");
        $this->setHelp( "Parse pdf file sau đó chuyển sang text");
        $this->addArgument('input', InputArgument::REQUIRED, 'Input pdf file path');
        $this->addArgument('output', InputArgument::OPTIONAL, 'Output text file path');
        $this->addOption( 'xml', null, InputArgument::OPTIONAL, 'Dump xml');
    }
    
    protected function getDocument($path, InputInterface $input){
        if(!file_exists( $path )){
            throw new \InvalidArgumentException("File not found at " . $path);
        }
        $parser = new Parser( $path );
        $document = $parser->process();
        
        $xml = $input->getOption( 'xml');
        if($xml){
            file_put_contents( $xml, $parser->getXml() );
        }
        
        return $document;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdf_file = $input->getArgument( 'input');
        $document = $this->getDocument( $pdf_file, $input );
        $output_file = $input->getArgument( 'output');
        $result = $document->getText();
        
        if($output_file){
            file_put_contents( $output_file, $result );
        }else{
            $output->writeln( $result );
        }
        return 0;
    }
}