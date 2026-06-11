<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfOutlineBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function addPage(PdfDocumentBuilder $builder, string $heading): void
{
    $builder->page(static function (PdfPageBuilder $page) use ($heading): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica')
            ->useType1Font('F2', 'Helvetica-Bold')
            ->content(static function (PdfContentStreamBuilder $stream) use ($heading): void {
                $stream
                    ->beginText()
                    ->setFont('F2', 20)
                    ->setTextMatrix(Matrix::translate(72, 750))
                    ->showText($heading)
                    ->endText();
            });
    });
}

function generate(): void
{
    $builder = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Outlines Example')
                ->author('phppdf'),
        );

    // Pages must be added before outline() so page indices are predictable.
    addPage($builder, 'Introduction'); // page 0
    addPage($builder, 'Chapter 1'); // page 1
    addPage($builder, 'Section 1.1'); // page 2
    addPage($builder, 'Section 1.2'); // page 3
    addPage($builder, 'Chapter 2'); // page 4
    addPage($builder, 'Section 2.1'); // page 5
    addPage($builder, 'Conclusion'); // page 6

    $builder->outline(static function (PdfOutlineBuilder $outline): void {
        $outline
            ->item('Introduction', 0)
            ->item('Chapter 1', 1, static function (PdfOutlineBuilder $chapter): void {
                $chapter
                    ->item('Section 1.1', 2)
                    ->item('Section 1.2', 3);
            })
            ->item('Chapter 2', 4, static function (PdfOutlineBuilder $chapter): void {
                $chapter
                    ->item('Section 2.1', 5);
            })
            ->item('Conclusion', 6);
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
