<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfTextExtractor;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generateSamplePdf(string $path): void
{
    $document = (new PdfDocumentBuilder())
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 18)
                        ->setTextMatrix(Matrix::translate(72, 720))
                        ->showText('PDF Reading Demo')
                        ->endText()
                        ->beginText()
                        ->setFont('F1', 12)
                        ->setTextMatrix(Matrix::translate(72, 680))
                        ->showText('This is the first line of body text.')
                        ->setTextMatrix(Matrix::translate(72, 660))
                        ->showText('This is the second line of body text.')
                        ->setTextMatrix(Matrix::translate(72, 640))
                        ->showText('And a third line to round things off.')
                        ->endText();
                });
        })
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 14)
                        ->setTextMatrix(Matrix::translate(72, 720))
                        ->showText('Page Two')
                        ->endText()
                        ->beginText()
                        ->setFont('F1', 12)
                        ->setTextMatrix(Matrix::translate(72, 690))
                        ->showText('Content on the second page.')
                        ->endText();
                });
        })
        ->build();

    $output = new PdfFileOutput($path);
    (new PdfDocumentSerializer($output))->writeDocument($document);

    echo "Written: {$path}\n";
}

function readAndExtract(string $path): void
{
    $doc = PdfDocumentReader::open($path);

    echo 'PDF version : ' . $doc->getVersion()->value . "\n";
    echo 'Page count  : ' . $doc->getPageCount() . "\n\n";

    $extractor = new PdfTextExtractor($doc);

    for ($i = 0; $i < $doc->getPageCount(); $i++) {
        echo "--- Page " . ($i + 1) . " ---\n";
        echo $extractor->getTextForPage($i) . "\n\n";
    }
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);

    $tmpPath = sys_get_temp_dir() . '/phppdf-reading-demo.pdf';

    generateSamplePdf($tmpPath);
    readAndExtract($tmpPath);
})();
