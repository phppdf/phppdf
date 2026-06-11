<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
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
    // =========================================================================
    // Part 1 — Pre-build page management on PdfDocumentBuilder
    //
    // Pages are added in the "wrong" order, then corrected with movePage()
    // and removePage() before build() is called. insertPage() splices a new
    // page into any position.
    // =========================================================================

    $doc = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Page Management Demo'));

    // Deliberately wrong order.
    $doc->page(static fn (PdfPageBuilder $p) => labelPage($p, 'C - Third', 0.10, 0.40, 0.80));
    $doc->page(static fn (PdfPageBuilder $p) => labelPage($p, 'A - First', 0.80, 0.20, 0.10));
    $doc->page(static fn (PdfPageBuilder $p) => labelPage($p, 'D - Fourth', 0.10, 0.60, 0.30));
    $doc->page(static fn (PdfPageBuilder $p) => labelPage($p, 'REMOVE ME', 0.50, 0.50, 0.50));
    $doc->page(static fn (PdfPageBuilder $p) => labelPage($p, 'B - Second', 0.60, 0.30, 0.80));

    // Plan:                     → [C, A, D, REMOVE, B]
    $doc->removePage(3); // → [C, A, D, B]
    $doc->movePage(1, 0); // A (index 1) → position 0: [A, C, D, B]
    $doc->movePage(3, 1); // B (index 3) → position 1: [A, B, C, D]
    $doc->insertPage(4, static fn (PdfPageBuilder $p) => labelPage($p, 'E - Fifth', 0.90, 0.60, 0.10));
    //                          → [A, B, C, D, E]

    $pdf = buildPdf($doc->build()); // 5 pages, ordered A–E — validates the build

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
