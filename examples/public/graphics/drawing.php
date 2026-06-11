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
        ->info(
            (new PdfDocumentInfo())
                ->title('Drawing Primitives')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s): void {

                    // ---- Section label helper (inline) ----
                    $label = static function (PdfContentStreamBuilder $s, float $x, float $y, string $text): void {
                        $s->beginText()
                          ->setFont('F1', 9)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText($text)
                          ->endText();
                    };

                    // =========================================================
                    // Title
                    // =========================================================
                    $s->beginText()
                      ->setFont('F2', 16)
                      ->setTextMatrix(Matrix::translate(72, 800))
                      ->showText('Drawing Primitives')
                      ->endText();

                    // =========================================================
                    // 1. Lines — width, cap style, dash pattern
                    // =========================================================
                    $label($s, 72, 775, 'Lines');

                    // Thin solid line
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0, 0, 0))
                      ->setLineWidth(0.5)
                      ->moveTo(72, 762)->lineTo(280, 762)->stroke()
                      ->restoreGraphicsState();

                    // Thick line
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.2, 0.4, 0.8))
                      ->setLineWidth(4)
                      ->moveTo(72, 750)->lineTo(280, 750)->stroke()
                      ->restoreGraphicsState();

                    // Dashed line — [8 4] dash pattern, butt cap
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.8, 0.2, 0.2))
                      ->setLineWidth(2)
                      ->setDashPattern([8, 4], 0)
                      ->setLineCap(0)
                      ->moveTo(72, 738)->lineTo(280, 738)->stroke()
                      ->restoreGraphicsState();

                    // Dotted line — round cap
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.1, 0.6, 0.3))
                      ->setLineWidth(3)
                      ->setDashPattern([1, 6], 0)
                      ->setLineCap(1) // round
                      ->moveTo(72, 726)->lineTo(280, 726)->stroke()
                      ->restoreGraphicsState();

                    // =========================================================
                    // 2. Rectangles — stroked, filled, filled+stroked
                    // =========================================================
                    $label($s, 72, 710, 'Rectangles');

                    // Stroked only
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0, 0, 0))
                      ->setLineWidth(1.5)
                      ->rectangle(72, 672, 80, 30)->stroke()
                      ->restoreGraphicsState();

                    // Filled only
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.9, 0.7, 0.1))
                      ->rectangle(165, 672, 80, 30)->fill()
                      ->restoreGraphicsState();

                    // Filled + stroked
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.2, 0.6, 0.9))
                      ->strokeColor(Color::rgb(0, 0, 0.5))
                      ->setLineWidth(2)
                      ->rectangle(258, 672, 80, 30)->fillAndStroke()
                      ->restoreGraphicsState();

                    // =========================================================
                    // 3. Rounded rectangles
                    // =========================================================
                    $label($s, 72, 660, 'Rounded rectangles');

                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.95, 0.4, 0.4))
                      ->strokeColor(Color::rgb(0.6, 0, 0))
                      ->setLineWidth(1.5)
                      ->roundedRectangle(72, 630, 80, 24, 8)->fillAndStroke()
                      ->restoreGraphicsState();

                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.4, 0.8, 0.5))
                      ->strokeColor(Color::rgb(0, 0.4, 0.1))
                      ->setLineWidth(1.5)
                      ->roundedRectangle(165, 630, 80, 24, 12)->fillAndStroke()
                      ->restoreGraphicsState();

                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.3, 0.3, 0.7))
                      ->setLineWidth(2)
                      ->roundedRectangle(258, 630, 80, 24, 4)->stroke()
                      ->restoreGraphicsState();

                    // =========================================================
                    // 4. Circles and ellipses
                    // =========================================================
                    $label($s, 72, 618, 'Circles and ellipses');

                    // Stroked circle
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0, 0, 0))
                      ->setLineWidth(1.5)
                      ->circle(107, 592, 20)->stroke()
                      ->restoreGraphicsState();

                    // Filled circle
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.9, 0.4, 0.1))
                      ->circle(170, 592, 20)->fill()
                      ->restoreGraphicsState();

                    // Filled + stroked circle
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.5, 0.8, 1.0))
                      ->strokeColor(Color::rgb(0, 0.3, 0.6))
                      ->setLineWidth(2)
                      ->circle(233, 592, 20)->fillAndStroke()
                      ->restoreGraphicsState();

                    // Wide ellipse
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.8, 0.6, 0.9))
                      ->strokeColor(Color::rgb(0.4, 0, 0.6))
                      ->setLineWidth(1.5)
                      ->ellipse(316, 592, 42, 18)->fillAndStroke()
                      ->restoreGraphicsState();

                    // =========================================================
                    // 5. Bézier curves
                    // =========================================================
                    $label($s, 72, 562, 'Bézier curves');

                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.1, 0.1, 0.8))
                      ->setLineWidth(2)
                      ->moveTo(72, 548)
                      ->curveTo(120, 510, 180, 570, 228, 548)
                      ->stroke()
                      ->restoreGraphicsState();

                    // S-curve using two connected cubic segments
                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.8, 0.2, 0.5))
                      ->setLineWidth(2)
                      ->moveTo(248, 548)
                      ->curveTo(260, 570, 310, 530, 320, 548)
                      ->curveTo(330, 567, 380, 528, 392, 548)
                      ->stroke()
                      ->restoreGraphicsState();

                    // =========================================================
                    // 6. Compound path (donut using even-odd fill rule)
                    // =========================================================
                    $label($s, 72, 512, 'Compound path (even-odd fill)');

                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.2, 0.5, 0.9))
                      ->circle(112, 488, 22)
                      ->circle(112, 488, 12)
                      ->fillEvenOdd()
                      ->restoreGraphicsState();

                    // Star shape via alternating moveTo/lineTo
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.95, 0.75, 0.1))
                      ->strokeColor(Color::rgb(0.7, 0.4, 0))
                      ->setLineWidth(1);

                    $cx = 200;
                    $cy = 488;
                    $outer = 24;
                    $inner = 10;
                    $points = 5;
                    $first = true;

                for ($i = 0; $i < $points * 2; $i++) {
                    $angle = deg2rad(-90 + $i * 180 / $points);
                    $r = $i % 2 === 0
                        ? $outer
                        : $inner;
                    $px = $cx + $r * cos($angle);
                    $py = $cy + $r * sin($angle);

                    if ($first) {
                        $s->moveTo($px, $py);
                        $first = false;
                    } else {
                        $s->lineTo($px, $py);
                    }
                }

                    $s->closeFillAndStroke()
                      ->restoreGraphicsState();

                    // =========================================================
                    // 7. Clipping path
                    // =========================================================
                    $label($s, 72, 455, 'Clipping path');

                    $s->saveGraphicsState()
                      // Define a circular clip region, then discard the path
                      ->circle(112, 428, 28)
                      ->clip()
                      ->endPath()
                      // Fill the clip region with a gradient of horizontal stripes
                      ->setLineWidth(4);

                    $colors = [
                [0.9,0.1,0.1], [0.9,0.5,0.1], [0.9,0.9,0.1],
                               [0.1,0.8,0.1], [0.1,0.4,0.9], [0.5,0.1,0.8]];

                    foreach ($colors as $i => [$r, $g, $b]) {
                        $y = 402 + $i * 9;
                        $s->strokeColor(Color::rgb($r, $g, $b))
                          ->moveTo(84, $y)->lineTo(140, $y)->stroke();
                    }

                    $s->restoreGraphicsState();
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
