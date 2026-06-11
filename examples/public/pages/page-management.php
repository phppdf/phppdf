<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Document\PdfDocumentMerger;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

/** Builds a single colour-filled page with a large white label. */
function labelPage(PdfPageBuilder $page, string $text, float $r, float $g, float $b): void
{
    $page->size(...PdfPageSize::A4)
         ->useType1Font('F1', 'Helvetica-Bold')
         ->content(static function (PdfContentStreamBuilder $s) use ($text, $r, $g, $b): void {
             $s->saveGraphicsState()
               ->fillColor(Color::rgb($r, $g, $b))
               ->rectangle(0, 0, 595, 842)
               ->fill()
               ->restoreGraphicsState();

             $s->beginText()
               ->setFont('F1', 36)
               ->fillColor(Color::rgb(1.0, 1.0, 1.0))
               ->setTextMatrix(Matrix::translate(72, 400))
               ->showText($text)
               ->endText();
         });
}

function buildPdf(PdfDocument $doc): string
{
    $out = new PdfMemoryOutput();
    (new PdfDocumentSerializer($out))->writeDocument($doc);

    return $out->getContent();
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    // Three documents are built independently, then merged with the Appendix
    // accidentally placed second. PdfDocumentEditor corrects the order by
    // moving the Appendix to the end, where it belongs.

    $cover = new PdfDocumentBuilder();
    $cover->page(static fn (PdfPageBuilder $p) => labelPage($p, 'Cover', 0.05, 0.05, 0.05));

    $chapters = new PdfDocumentBuilder();

    foreach (['Chapter 1', 'Chapter 2', 'Chapter 3'] as $ch) {
        $chapters->page(static fn (PdfPageBuilder $p) => labelPage($p, $ch, 0.15, 0.35, 0.60));
    }

    $appendix = new PdfDocumentBuilder();
    $appendix->page(static fn (PdfPageBuilder $p) => labelPage($p, 'Appendix', 0.30, 0.60, 0.30));

    // Merge with the Appendix accidentally placed second:
    // Cover + Appendix + Ch1 + Ch2 + Ch3  →  indices 0–4.
    $merged = (new PdfDocumentMerger())
        ->add($cover->build())
        ->add($appendix->build())
        ->add($chapters->build())
        ->build();

    // Fix the order: move Appendix from index 1 to the end (index 4)
    // →  [Cover, Ch1, Ch2, Ch3, Appendix].
    $result = (new PdfDocumentEditor($merged))
        ->movePage(1, 4)
        ->build();

    $pdf = buildPdf($result);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $pdf;
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
