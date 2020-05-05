<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2020-04-18
 * Time: 15:54
 */

use ThikDev\PdfParser\Parser;

require __DIR__ . '/vendor/autoload.php';


$pdf_dir = __DIR__ . "/test_docs/pdf/";
$txt_dir = __DIR__ . "/test_docs/txt/";
$html_dir = __DIR__ . "/test_docs/html/";

$files = glob( $pdf_dir . "*.pdf" );
foreach ( $files as $file ) {
    $name = basename( $file );
    echo "Process " . $name . " \n";
    $parser = new Parser( $file );
//    $parser->addProcessAfter(\ThikDev\PdfParser\Process\DetectImage::class);
//    $parser->addProcessAfter(\ThikDev\PdfParser\Process\DetectReferences::class);
    $parser->addProcessAfter(\ThikDev\PdfParser\Process\DetectAbstract::class);
    $document = $parser->process();
    $txt_path = $txt_dir . $name . ".txt";
    $html_path = $html_dir . $name . ".html";
    file_put_contents( $txt_path, $document->getText());
    file_put_contents( $html_path, $document->getHtml());
}
