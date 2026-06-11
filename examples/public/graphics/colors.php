<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Color Support')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s): void {

                    $heading = static function (PdfContentStreamBuilder $s, float $y, string $text): void {
                        $s->beginText()->setFont('F2', 10)
                          ->setTextMatrix(Matrix::translate(72, $y))
                          ->showText($text)->endText();
                    };

                    $label = static function (PdfContentStreamBuilder $s, float $x, float $y, string $text): void {
                        $s->beginText()->setFont('F1', 7)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText($text)->endText();
                    };

                    // =========================================================
                    // Title
                    // =========================================================
                    $s->beginText()->setFont('F2', 16)
                      ->setTextMatrix(Matrix::translate(72, 800))
                      ->showText('Color Support')->endText();

                    // =========================================================
                    // 1. Named colours
                    // =========================================================
                    $heading($s, 778, 'Named colours');

                    $named = [
                        ['red', Color::red()],
                        ['orange', Color::orange()],
                        ['yellow', Color::yellow()],
                        ['green', Color::green()],
                        ['lime', Color::lime()],
                        ['teal', Color::teal()],
                        ['cyan', Color::cyan()],
                        ['blue', Color::blue()],
                        ['navy', Color::navy()],
                        ['purple', Color::purple()],
                        ['magenta', Color::magenta()],
                        ['pink', Color::pink()],
                        ['brown', Color::brown()],
                        ['black', Color::black()],
                        ['white', Color::white()],
                    ];

                    foreach ($named as $i => [$name, $color]) {
                        $x = 72 + $i * 32;
                        $s->saveGraphicsState()
                          ->fillColor($color)
                          ->strokeColor(Color::black())
                          ->setLineWidth(0.5)
                          ->rectangle($x, 736, 28, 28)->fillAndStroke()
                          ->restoreGraphicsState();
                        $label($s, $x + 1, 729, $name);
                    }

                    // =========================================================
                    // 2. Hex colours
                    // =========================================================
                    $heading($s, 715, 'Hex colours (#rrggbb and #rgb shorthand)');

                    $hexColors = [
                        '#e74c3c', '#e67e22', '#f1c40f', '#2ecc71',
                        '#1abc9c', '#3498db', '#9b59b6', '#e91e63',
                        '#607d8b', '#795548', '#ff5722', '#009688',
                    ];

                    foreach ($hexColors as $i => $hex) {
                        $x = 72 + $i * 38;
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex($hex))
                          ->rectangle($x, 682, 33, 26)->fill()
                          ->restoreGraphicsState();
                        $label($s, $x, 675, $hex);
                    }

                    // =========================================================
                    // 3. Lighter / darker
                    // =========================================================
                    $heading($s, 660, 'Lighter and darker (Color::blue())');

                    $base = Color::blue();
                    $steps = 7;

                    for ($i = 0; $i < $steps; $i++) {
                        $factor = $i / ($steps - 1);
                        $x = 72 + $i * 40;
                        $s->saveGraphicsState()
                          ->fillColor($base->lighter($factor))
                          ->rectangle($x, 632, 36, 22)->fill()
                          ->restoreGraphicsState();
                        $label($s, $x + 2, 626, number_format($factor, 2));
                    }

                    $heading($s, 614, 'Darker');

                    for ($i = 0; $i < $steps; $i++) {
                        $factor = $i / ($steps - 1);
                        $x = 72 + $i * 40;
                        $s->saveGraphicsState()
                          ->fillColor($base->darker($factor))
                          ->rectangle($x, 586, 36, 22)->fill()
                          ->restoreGraphicsState();
                    }

                    // =========================================================
                    // 4. Mix — interpolate between two colours
                    // =========================================================
                    $heading($s, 574, 'Mix: red → blue');

                    $from = Color::red();
                    $to = Color::blue();
                    $steps = 10;

                    for ($i = 0; $i < $steps; $i++) {
                        $t = $i / ($steps - 1);
                        $s->saveGraphicsState()
                          ->fillColor($from->mix($to, $t))
                          ->rectangle(72 + $i * 44, 546, 40, 22)->fill()
                          ->restoreGraphicsState();
                    }

                    // =========================================================
                    // 5. Grayscale ramp
                    // =========================================================
                    $heading($s, 534, 'Grayscale ramp');

                    $steps = 11;

                    for ($i = 0; $i < $steps; $i++) {
                        $t = $i / ($steps - 1);
                        $s->saveGraphicsState()
                          ->fillColor(Color::gray($t))
                          ->rectangle(72 + $i * 40, 506, 36, 22)->fill()
                          ->restoreGraphicsState();
                        $label($s, 74 + $i * 40, 500, number_format($t, 1));
                    }

                    // =========================================================
                    // 6. CMYK
                    // =========================================================
                    $heading($s, 488, 'CMYK — varying cyan (M=0.6, Y=0, K=0)');

                    $steps = 8;

                    for ($i = 0; $i < $steps; $i++) {
                        $c = $i / ($steps - 1);
                        $s->saveGraphicsState()
                          ->fillColor(Color::cmyk($c, 0.6, 0, 0))
                          ->rectangle(72 + $i * 50, 460, 46, 22)->fill()
                          ->restoreGraphicsState();
                        $label($s, 74 + $i * 50, 454, 'C=' . number_format($c, 1));
                    }

                    // =========================================================
                    // 7. Stroke vs. fill colour
                    // =========================================================
                    $heading($s, 442, 'Independent stroke and fill colours');

                    $pairs = [
                        [Color::fromHex('#e74c3c'), Color::fromHex('#2c3e50')],
                        [Color::fromHex('#f39c12'), Color::fromHex('#8e44ad')],
                        [Color::fromHex('#1abc9c'), Color::fromHex('#c0392b')],
                        [Color::fromHex('#3498db'), Color::fromHex('#e67e22')],
                        [Color::fromHex('#2ecc71'), Color::fromHex('#e74c3c')],
                    ];

                    foreach ($pairs as $i => [$fill, $stroke]) {
                        $cx = 90 + $i * 80;
                        $s->saveGraphicsState()
                          ->fillColor($fill)
                          ->strokeColor($stroke)
                          ->setLineWidth(3)
                          ->circle($cx, 408, 22)->fillAndStroke()
                          ->restoreGraphicsState();
                    }

                    // =========================================================
                    // 8. toHex() round-trip
                    // =========================================================
                    $heading($s, 374, 'toHex() round-trip — parsed then re-serialised as text');

                    $samples = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6'];

                    foreach ($samples as $i => $hex) {
                        $color = Color::fromHex($hex);
                        $x = 72 + $i * 90;
                        $s->saveGraphicsState()
                          ->fillColor($color)
                          ->rectangle($x, 348, 80, 20)->fill()
                          ->restoreGraphicsState();
                        $label($s, $x + 2, 342, $color->toHex());
                    }
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
