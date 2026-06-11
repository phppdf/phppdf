<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $builder = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Hyperlinks Example')
                ->author('phppdf'),
        );

    // Page 0 — external URI links and an internal page link.
    $builder->page(static function (PdfPageBuilder $page): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica')
            ->useType1Font('F2', 'Helvetica-Bold')
            // Text baseline at y=700; rect bottom = baseline − 3, top = baseline + 12.
            ->addUriLink(x: 72, y: 697, width: 200, height: 15, uri: 'https://github.com')
            // Text baseline at y=660; same offset.
            ->addPageLink(x: 72, y: 657, width: 200, height: 15, pageIndex: 1)
            ->content(static function (PdfContentStreamBuilder $stream): void {
                $stream
                    ->beginText()
                    ->setFont('F2', 16)
                    ->setTextMatrix(Matrix::translate(72, 750))
                    ->showText('Page 1 — Links')
                    ->setFont('F1', 12)
                    ->setTextMatrix(Matrix::translate(72, 700))
                    ->showText('External link (click here): github.com')
                    ->setTextMatrix(Matrix::translate(72, 660))
                    ->showText('Internal link (click here): go to page 2')
                    ->endText();
            });
    });

    // Page 1 — link back to page 0.
    $builder->page(static function (PdfPageBuilder $page): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica')
            ->useType1Font('F2', 'Helvetica-Bold')
            ->addPageLink(x: 72, y: 697, width: 200, height: 15, pageIndex: 0)
            ->content(static function (PdfContentStreamBuilder $stream): void {
                $stream
                    ->beginText()
                    ->setFont('F2', 16)
                    ->setTextMatrix(Matrix::translate(72, 750))
                    ->showText('Page 2 — Links')
                    ->setFont('F1', 12)
                    ->setTextMatrix(Matrix::translate(72, 700))
                    ->showText('Internal link (click here): go back to page 1')
                    ->endText();
            });
    });

    $document = $builder->build();

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
