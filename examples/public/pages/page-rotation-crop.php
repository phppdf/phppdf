<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $pH = PdfPageSize::A4[1]; // 842 pt

    // -------------------------------------------------------------------------
    // Step 1: Build a 4-page source document (all portrait A4).
    // -------------------------------------------------------------------------

    $bgColors = [
        [0.85, 0.92, 1.00], // blue-tint
        [0.92, 1.00, 0.85], // green-tint
        [1.00, 0.95, 0.80], // yellow-tint
        [1.00, 0.85, 0.90], // pink-tint
    ];

    $labels = [
        'Page 1 — unchanged (0°)',
        'Page 2 — will be rotated 90°',
        'Page 3 — will be rotated 180°',
        'Page 4 — will be cropped to centre strip',
    ];

    $buildPage = static function (PdfPageBuilder $page, int $n) use ($pH, $bgColors, $labels): void {
        [$r, $g, $b] = $bgColors[$n];

        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica-Bold')
            ->useType1Font('F2', 'Helvetica')
            ->content(static function (PdfContentStreamBuilder $s) use ($pH, $r, $g, $b, $n, $labels): void {
                // Background fill.
                $s->fillColor(Color::rgb($r, $g, $b))
                  ->rectangle(0, 0, 595, 842)->fill();

                // Page border.
                $s->saveGraphicsState()
                  ->strokeColor(Color::rgb(0.55, 0.55, 0.55))
                  ->setLineWidth(1.5)
                  ->rectangle(14, 14, 567, 814)->stroke()
                  ->restoreGraphicsState();

                // Large page number centred.
                $s->fillColor(Color::rgb(0.2, 0.2, 0.2))
                  ->beginText()->setFont('F1', 120)
                  ->setTextMatrix(Matrix::translate(215, 360))
                  ->showText((string) ($n + 1))
                  ->endText();

                // Label at top.
                $s->beginText()->setFont('F2', 13)
                  ->setTextMatrix(Matrix::translate(72, $pH - 60))
                  ->showText($labels[$n])
                  ->endText();

                // On page 4, draw the crop boundary so it's visible.
                if ($n !== 3) {
                    return;
                }

                $s->saveGraphicsState()
                  ->strokeColor(Color::rgb(0.8, 0.0, 0.0))
                  ->setLineWidth(2.0)
                  ->rectangle(72, 250, 451, 380)->stroke()
                  ->restoreGraphicsState();

                $s->fillColor(Color::rgb(0.7, 0.0, 0.0))
                  ->beginText()->setFont('F2', 11)
                  ->setTextMatrix(Matrix::translate(72, 238))
                  ->showText('red box = /CropBox — content outside is clipped by the viewer')
                  ->endText();
            });
    };

    $source = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Rotation & Crop — source'))
        ->page(static fn (PdfPageBuilder $p) => $buildPage($p, 0))
        ->page(static fn (PdfPageBuilder $p) => $buildPage($p, 1))
        ->page(static fn (PdfPageBuilder $p) => $buildPage($p, 2))
        ->page(static fn (PdfPageBuilder $p) => $buildPage($p, 3))
        ->build();

    // -------------------------------------------------------------------------
    // Step 2: Edit — rotate pages 1 and 2, crop page 3.
    // -------------------------------------------------------------------------

    $out1 = '/tmp/phppdf-rotation-crop-edited.pdf';

    $edited = (new PdfDocumentEditor($source))
        ->rotatePage(1, 90)
        ->rotatePage(2, 180)
        ->cropPage(3, x: 72, y: 250, width: 451, height: 380)
        ->build();

    (new PdfDocumentSerializer(new PdfFileOutput($out1)))->writeDocument($edited);

    // -------------------------------------------------------------------------
    // Step 3: PdfPageBuilder::rotate() — bake /Rotate directly into new pages.
    // -------------------------------------------------------------------------

    $out2 = '/tmp/phppdf-rotation-builder.pdf';

    $built = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Rotation & Crop — builder'))
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page->size(...PdfPageSize::A4)
                 ->rotate(0)
                 ->useType1Font('F1', 'Helvetica-Bold')
                 ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                     $s->beginText()->setFont('F1', 20)
                       ->setTextMatrix(Matrix::translate(72, $pH - 100))
                       ->showText('rotate(0) — viewer shows this upright')->endText();
                 });
        })
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page->size(...PdfPageSize::A4)
                 ->rotate(90)
                 ->useType1Font('F1', 'Helvetica-Bold')
                 ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                     $s->beginText()->setFont('F1', 20)
                       ->setTextMatrix(Matrix::translate(72, $pH - 100))
                       ->showText('rotate(90) — viewer rotates 90° CW')->endText();
                 });
        })
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page->size(...PdfPageSize::A4)
                 ->rotate(180)
                 ->useType1Font('F1', 'Helvetica-Bold')
                 ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                     $s->beginText()->setFont('F1', 20)
                       ->setTextMatrix(Matrix::translate(72, $pH - 100))
                       ->showText('rotate(180) — viewer shows upside down')->endText();
                 });
        })
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page->size(...PdfPageSize::A4)
                 ->rotate(270)
                 ->useType1Font('F1', 'Helvetica-Bold')
                 ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                     $s->beginText()->setFont('F1', 20)
                       ->setTextMatrix(Matrix::translate(72, $pH - 100))
                       ->showText('rotate(270) — viewer rotates 90° CCW')->endText();
                 });
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($built);

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
