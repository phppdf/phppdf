<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Text\TextBox;

/** Draws a simple labelled page for the given size name. */
function makePageContent(
    PdfContentStreamBuilder $s,
    string $sizeName,
    int $w,
    int $h,
    string $note = '',
): void {
    $helv  = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();
    $lm    = min(36.0, $w * 0.06);

    // Page border
    $s->saveGraphicsState()
      ->strokeColor(Color::fromHex('#3355aa'))
      ->setLineWidth(2.0)
      ->rectangle(4, 4, $w - 8, $h - 8)
      ->stroke()
      ->restoreGraphicsState();

    // Diagonal watermark lines
    $s->saveGraphicsState()
      ->setLineWidth(0.3)
      ->strokeColor(Color::gray(0.88))
      ->moveTo(0, 0)->lineTo($w, $h)->stroke()
      ->moveTo($w, 0)->lineTo(0, $h)->stroke()
      ->restoreGraphicsState();

    // Name
    $fontSize = min(28.0, $w / 8);
    $s->beginText()->setFont('FB', $fontSize)
      ->fillColor(Color::rgb(0.1, 0.2, 0.6))
      ->setTextMatrix(Matrix::translate($lm, $h / 2 + $fontSize * 0.5))
      ->showText($sizeName)
      ->endText();

    // Dimensions
    $dimFontSize = min(14.0, $w / 16);
    $ptLabel     = "{$w} × {$h} pt";
    $mmW         = round($w / 72 * 25.4);
    $mmH         = round($h / 72 * 25.4);
    $mmLabel     = "{$mmW} × {$mmH} mm";

    $s->beginText()->setFont('F1', $dimFontSize)
      ->fillColor(Color::rgb(0.3, 0.3, 0.3))
      ->setTextMatrix(Matrix::translate($lm, $h / 2 - $dimFontSize * 0.8))
      ->showText($ptLabel)
      ->endText();

    $s->beginText()->setFont('F1', $dimFontSize)
      ->setTextMatrix(Matrix::translate($lm, $h / 2 - $dimFontSize * 2.2))
      ->showText($mmLabel)
      ->endText();

    if ($note !== '') {
        $noteBox = TextBox::create($note, $helv, min(9.0, $w / 40), $w - $lm * 2, 11);
        $s->drawTextBox($noteBox, fontName: 'F1', x: $lm, y: $lm + $noteBox->getHeight() + 2);
    }
}

function generate(): void
{
    $helv  = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $sizes = [
        ['A3',      PdfPageSize::A3,      'ISO 216 — twice the area of A4. Common for posters, engineering drawings, and large diagrams.'],
        ['A4',      PdfPageSize::A4,      'ISO 216 — the international standard office paper size used throughout most of the world.'],
        ['A5',      PdfPageSize::A5,      'ISO 216 — half the area of A4. Used for booklets, flyers, and pocket-sized publications.'],
        ['Letter',  PdfPageSize::LETTER,  'ANSI/ASME Y14.1 — the standard office size in North America (8.5 × 11 in).'],
        ['Legal',   PdfPageSize::LEGAL,   'North American legal paper (8.5 × 14 in). Used for legal documents and contracts.'],
        ['Tabloid',PdfPageSize::TABLOID,  'North American tabloid / ANSI B (11 × 17 in). Used for newspapers, posters, and spreads.'],
    ];

    // Custom sizes (in points)
    $custom = [
        ['Business Card', 252, 144, '3.5 × 2 in — typical business card'],
        ['DL Envelope',   624, 312, '220 × 110 mm — European DL envelope'],
        ['Square 100mm',  283, 283, '100 × 100 mm — square social/invite'],
    ];

    $builder = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Page Sizes')
                ->author('phppdf'),
        );

    // Summary page (A4) listing all available constants
    $builder->page(function (PdfPageBuilder $page) use ($helv, $helvB, $sizes, $custom): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica')
            ->useType1Font('FB', 'Helvetica-Bold')
            ->content(function (PdfContentStreamBuilder $s) use ($helv, $helvB, $sizes, $custom): void {

                $lm = 72.0;
                $y  = 790.0;

                $s->beginText()->setFont('FB', 18)
                  ->setTextMatrix(Matrix::translate($lm, $y))
                  ->showText('Page Sizes — PdfPageSize constants & custom sizes')
                  ->endText();
                $y -= 8;
                $s->saveGraphicsState()->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                  ->moveTo($lm, $y)->lineTo(523, $y)->stroke()->restoreGraphicsState();
                $y -= 18;

                $s->beginText()->setFont('F1', 10)
                  ->setTextMatrix(Matrix::translate($lm, $y))
                  ->showText('Each subsequent page in this document uses a different size. Custom sizes use PdfPageBuilder::size(width, height).')
                  ->endText();
                $y -= 22;

                $s->beginText()->setFont('FB', 10)
                  ->setTextMatrix(Matrix::translate($lm, $y))
                  ->showText('Standard constants (PdfPageSize::NAME)')->endText();
                $y -= 14;

                $headerBg = Color::fromHex('#3355aa');
                $altBg    = Color::fromHex('#f0f4ff');

                $s->saveGraphicsState()->fillColor($headerBg)
                  ->rectangle($lm, $y - 16, 451, 18)->fill()->restoreGraphicsState();
                $s->beginText()->setFont('FB', 9)->fillColor(Color::rgb(1, 1, 1))
                  ->setTextMatrix(Matrix::translate($lm + 4, $y - 11))->showText('Constant')
                  ->setTextMatrix(Matrix::translate($lm + 90, $y - 11))->showText('Width (pt)')
                  ->setTextMatrix(Matrix::translate($lm + 175, $y - 11))->showText('Height (pt)')
                  ->setTextMatrix(Matrix::translate($lm + 260, $y - 11))->showText('Width (mm)')
                  ->setTextMatrix(Matrix::translate($lm + 345, $y - 11))->showText('Height (mm)')
                  ->endText();
                $y -= 18;

                foreach ($sizes as $i => [$name, [$w, $h], $note]) {
                    if ($i % 2 === 0) {
                        $s->saveGraphicsState()->fillColor($altBg)
                          ->rectangle($lm, $y - 16, 451, 18)->fill()->restoreGraphicsState();
                    }
                    $mmW = round($w / 72 * 25.4);
                    $mmH = round($h / 72 * 25.4);
                    $s->beginText()->setFont('FB', 9)->fillColor(Color::rgb(0, 0, 0))
                      ->setTextMatrix(Matrix::translate($lm + 4, $y - 11))->showText($name)->endText();
                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($lm + 90, $y - 11))->showText((string)$w)
                      ->setTextMatrix(Matrix::translate($lm + 175, $y - 11))->showText((string)$h)
                      ->setTextMatrix(Matrix::translate($lm + 260, $y - 11))->showText((string)$mmW)
                      ->setTextMatrix(Matrix::translate($lm + 345, $y - 11))->showText((string)$mmH)
                      ->endText();
                    $y -= 18;
                }

                $y -= 14;
                $s->beginText()->setFont('FB', 10)
                  ->fillColor(Color::rgb(0, 0, 0))
                  ->setTextMatrix(Matrix::translate($lm, $y))
                  ->showText('Custom sizes  —  PdfPageBuilder::size(int $width, int $height)')->endText();
                $y -= 14;

                $s->saveGraphicsState()->fillColor($headerBg)
                  ->rectangle($lm, $y - 16, 451, 18)->fill()->restoreGraphicsState();
                $s->beginText()->setFont('FB', 9)->fillColor(Color::rgb(1, 1, 1))
                  ->setTextMatrix(Matrix::translate($lm + 4, $y - 11))->showText('Name')
                  ->setTextMatrix(Matrix::translate($lm + 100, $y - 11))->showText('Width (pt)')
                  ->setTextMatrix(Matrix::translate($lm + 180, $y - 11))->showText('Height (pt)')
                  ->setTextMatrix(Matrix::translate($lm + 260, $y - 11))->showText('Description')
                  ->endText();
                $y -= 18;

                foreach ($custom as $i => [$name, $w, $h, $note]) {
                    if ($i % 2 === 0) {
                        $s->saveGraphicsState()->fillColor($altBg)
                          ->rectangle($lm, $y - 16, 451, 18)->fill()->restoreGraphicsState();
                    }
                    $s->beginText()->setFont('FB', 9)->fillColor(Color::rgb(0, 0, 0))
                      ->setTextMatrix(Matrix::translate($lm + 4, $y - 11))->showText($name)->endText();
                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($lm + 100, $y - 11))->showText((string)$w)
                      ->setTextMatrix(Matrix::translate($lm + 180, $y - 11))->showText((string)$h)
                      ->setTextMatrix(Matrix::translate($lm + 260, $y - 11))->showText($note)
                      ->endText();
                    $y -= 18;
                }
            });
    });

    // One page per standard size
    foreach ($sizes as [$name, [$w, $h], $note]) {
        $builder->page(function (PdfPageBuilder $page) use ($name, $w, $h, $note): void {
            $page->size($w, $h)
                 ->useType1Font('F1', 'Helvetica')
                 ->useType1Font('FB', 'Helvetica-Bold')
                 ->content(fn(PdfContentStreamBuilder $s) => makePageContent($s, $name, $w, $h, $note));
        });
    }

    // One page per custom size
    foreach ($custom as [$name, $w, $h, $note]) {
        $builder->page(function (PdfPageBuilder $page) use ($name, $w, $h, $note): void {
            $page->size($w, $h)
                 ->useType1Font('F1', 'Helvetica')
                 ->useType1Font('FB', 'Helvetica-Bold')
                 ->content(fn(PdfContentStreamBuilder $s) => makePageContent($s, $name, $w, $h, $note));
        });
    }

    $document = $builder->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $output->getContent();
}

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
