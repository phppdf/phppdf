<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Document\PdfDocumentMerger;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;
use PhpPdf\Text\TextFlow;

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

function rule(PdfContentStreamBuilder $s, float $x, float $y, float $width): void
{
    $s->saveGraphicsState()
      ->setLineWidth(0.5)
      ->moveTo($x, $y)->lineTo($x + $width, $y)->stroke()
      ->restoreGraphicsState();
}

// ---------------------------------------------------------------------------
// Document A — "Main Report" (2 pages of body text)
// ---------------------------------------------------------------------------

function buildDocumentA(): PdfDocument
{
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $body = str_repeat(
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor "
        . "incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis "
        . "nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. "
        . "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore "
        . "eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident.\n\n",
        6,
    );

    $box = TextBox::create($body, $helv, 11, 451.0, 15.0, TextAlign::Justify);

    $doc = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Main Report')->author('phppdf'))
        ->globalFont('FhB', 'Helvetica-Bold')
        ->globalFont('Fh', 'Helvetica')
        ->header(static function (PdfContentStreamBuilder $s, int $n, int $total): void {
            $s->beginText()->setFont('FhB', 8)
              ->setTextMatrix(Matrix::translate(72, 822))
              ->showText('Main Report')
              ->endText();

            $label = "Page $n of $total";
            $s->beginText()->setFont('Fh', 8)
              ->setTextMatrix(Matrix::translate(523 - strlen($label) * 4.5, 822))
              ->showText($label)
              ->endText();

            rule($s, 72, 815, 451);
        });

    // Title page (page 1)
    $doc->page(static function (PdfPageBuilder $page) use ($helvB): void {
        $page->size(...PdfPageSize::A4)
             ->useType1Font('FT', 'Helvetica-Bold')
             ->content(static function (PdfContentStreamBuilder $s) use ($helvB): void {
                 $title = TextBox::create('Main Report', $helvB, 28, 451.0);
                 $s->drawTextBox($title, 'FT', 72, 500);

                 $sub = TextBox::create('Produced by phppdf — Document A', $helvB, 13, 451.0);
                 $s->drawTextBox($sub, 'FT', 72, 460);
             });
    });

    // Body pages (auto-paginated)
    TextFlow::pour(
        box: $box,
        document: $doc,
        configure: static fn (PdfPageBuilder $p) => $p
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica'),
        fontName: 'F1',
        x: 72.0,
        y: 770.0,
        maxHeight: 698.0,
    );

    return $doc->build();
}

// ---------------------------------------------------------------------------
// Document B — "Appendix" (1 page, Times-Roman, different look)
// ---------------------------------------------------------------------------

function buildDocumentB(): PdfDocument
{
    $times = Type1FontMetrics::timesRoman();

    $appendixText =
        "Appendix A — Reference Data\n\n"
        . "This appendix was produced as a separate document and merged at the end. "
        . "The merger copies each source document's objects (fonts, content streams, "
        . "images) into a shared object registry, renumbers them to avoid collisions, "
        . "rebuilds the page tree, and produces a single coherent PDF. "
        . "Encryption, bookmarks, and digital signatures from the source documents "
        . "are intentionally dropped because they cannot survive object renumbering "
        . "in a trivial way.\n\n"
        . "Notice that this page uses Times-Roman at 12 pt — a completely different "
        . "font than the Helvetica body pages that precede it. The merger carries "
        . "each page's own font resources unchanged into the merged output.";

    return (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Appendix')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($times, $appendixText): void {
            $page->size(...PdfPageSize::A4)
                 ->useType1Font('FA', 'Times-Bold')
                 ->useType1Font('FB', 'Times-Roman')
                 ->content(static function (PdfContentStreamBuilder $s) use ($times, $appendixText): void {
                     $heading = TextBox::create('Appendix A', $times, 18, 451.0);
                     $s->drawTextBox($heading, 'FA', 72, 770);

                     $body = TextBox::create($appendixText, $times, 12, 451.0, 16.0, TextAlign::Left);
                     $s->drawTextBox($body, 'FB', 72, 730);
                 });
        })
        ->build();
}

// ---------------------------------------------------------------------------
// Merge and output
// ---------------------------------------------------------------------------

function generate(): void
{
    $docA = buildDocumentA();
    $docB = buildDocumentB();

    $merged = (new PdfDocumentMerger())
        ->add($docA)
        ->add($docB)
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($merged);

    header('Content-Type: application/pdf');
    echo $output->getContent();
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
