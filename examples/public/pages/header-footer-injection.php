<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $pH = PdfPageSize::A4[1];

    // -------------------------------------------------------------------------
    // Build the source document (3 content pages, no header/footer).
    // -------------------------------------------------------------------------

    $source = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Header/Footer Injection Demo')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F1', 24)
                      ->setTextMatrix(Matrix::translate(72, $pH - 120))
                      ->showText('Chapter 1: Introduction')
                      ->endText();

                    $s->beginText()->setFont('F2', 12)
                      ->setTextMatrix(Matrix::translate(72, $pH - 170))
                      ->showText('This is the first page of the document.')
                      ->endText();

                    $s->beginText()->setFont('F2', 12)
                      ->setTextMatrix(Matrix::translate(72, $pH - 190))
                      ->showText('The header and footer are injected as separate content streams.')
                      ->endText();
                });
        })
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F1', 20)
                      ->setTextMatrix(Matrix::translate(72, $pH - 120))
                      ->showText('Chapter 2: Details')
                      ->endText();

                    $s->beginText()->setFont('F2', 12)
                      ->setTextMatrix(Matrix::translate(72, $pH - 160))
                      ->showText('Second page content. Notice the header and footer below.')
                      ->endText();
                });
        })
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F1', 20)
                      ->setTextMatrix(Matrix::translate(72, $pH - 120))
                      ->showText('Chapter 3: Conclusion')
                      ->endText();

                    $s->beginText()->setFont('F2', 12)
                      ->setTextMatrix(Matrix::translate(72, $pH - 160))
                      ->showText('Third and final page. Same header and footer applied.')
                      ->endText();
                });
        })
        ->build();

    // -------------------------------------------------------------------------
    // Build: inject header + footer using standard Type1 fonts.
    // -------------------------------------------------------------------------

    $doc = (new PdfDocumentEditor($source))
        ->useType1Font('HF', 'Helvetica')
        ->useType1Font('HFB', 'Helvetica-Bold')
        ->header(static function (PdfContentStreamBuilder $s, int $page, int $total, float $w, float $h): void {
            $s->saveGraphicsState()
              ->setLineWidth(0.5)
              ->moveTo(36, $h - 30)->lineTo($w - 36, $h - 30)->stroke()
              ->restoreGraphicsState();

            $s->beginText()->setFont('HFB', 9)
              ->setTextMatrix(Matrix::translate(36, $h - 26))
              ->showText('Header/Footer Injection Demo')
              ->endText();
        })
        ->footer(static function (PdfContentStreamBuilder $s, int $page, int $total, float $w, float $h): void {
            $s->saveGraphicsState()
              ->setLineWidth(0.5)
              ->moveTo(36, 30)->lineTo($w - 36, 30)->stroke()
              ->restoreGraphicsState();

            $label = "Page {$page} of {$total}";
            $s->beginText()->setFont('HF', 9)
              ->setTextMatrix(Matrix::translate($w / 2 - 20, 18))
              ->showText($label)
              ->endText();
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($doc);

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
