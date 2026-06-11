<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Archival Document')
                ->author('phppdf')
                ->subject('PDF/A-2b conformance example')
                ->creator('phppdf example'),
        )
        ->conformTo(PdfAConformance::PdfA2b)
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 24)
                        ->setTextMatrix(Matrix::translate(72, 720))
                        ->showText('This document targets PDF/A-2b.')
                        ->endText();
                });
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $output->getContent();
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
