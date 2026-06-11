<?php

declare(strict_types=1);

use PhpPdf\Barcode\Code128;
use PhpPdf\Barcode\EAN13;
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

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;
    $lm   = 72.0;
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Barcode Examples')
                ->author('phppdf'),
        )
        ->page(function (PdfPageBuilder $page) use ($pageW, $pageH, $lm, $helv, $helvB): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(function (PdfContentStreamBuilder $s) use (
                    $pageW,
                    $pageH,
                    $lm,
                    $helv,
                    $helvB,
                ): void {

                    $y = $pageH - 60.0;

                    // ── Page title ────────────────────────────────────────────
                    $s->beginText()->setFont('FB', 16)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Linear Barcodes — Code 128 & EAN-13')
                      ->endText();
                    $y -= 8;
                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                      ->moveTo($lm, $y)->lineTo($pageW - $lm, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 24;

                    // ── Section helper ────────────────────────────────────────
                    $section = function (string $title) use ($s, $lm, &$y): void {
                        $s->beginText()->setFont('FB', 11)
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($title)->endText();
                        $y -= 14;
                    };

                    $caption = function (string $text, float $cx, float $cy) use ($s): void {
                        $s->beginText()->setFont('F1', 8)
                          ->fillColor(Color::rgb(0.3, 0.3, 0.3))
                          ->setTextMatrix(Matrix::translate($cx, $cy))
                          ->showText($text)->endText();
                    };

                    // ── 1. Code 128 — basic samples ───────────────────────────
                    $section('1.  Code 128 B — general-purpose (ASCII 32-127)');

                    $samples128 = [
                        'Hello, World!',
                        'ABC-1234',
                        'https://example.com',
                        '1234567890',
                    ];

                    foreach ($samples128 as $text) {
                        $bc  = Code128::encode($text);
                        $mw  = 1.0;
                        $bh  = 28.0;
                        $s->drawBarcode(
                            $bc,
                            x: $lm,
                            y: $y - $bh,
                            height: $bh,
                            moduleWidth: $mw,
                            fontName: 'F1',
                            fontSize: 7,
                            metrics: $helv,
                        );
                        $caption('"' . $text . '"', $lm, $y - $bh - 16);
                        $y -= $bh + 28;
                    }
                    $y -= 6;

                    // ── 2. Code 128 — module width variation ──────────────────
                    $section('2.  Code 128 — varying module width (same data)');

                    $bc128 = Code128::encode('PHPPDF');
                    $widths = [0.7, 1.0, 1.4];
                    $bx = $lm;
                    $bh = 25.0;

                    foreach ($widths as $mw) {
                        $bars  = $bc128->getBars();
                        $total = (int) array_sum($bars);
                        $bw    = ($total + 20) * $mw;

                        $s->drawBarcode(
                            $bc128,
                            x: $bx,
                            y: $y - $bh,
                            height: $bh,
                            moduleWidth: $mw,
                        );
                        $caption("{$mw} pt/module", $bx, $y - $bh - 12);
                        $bx += $bw + 8;
                    }
                    $y -= $bh + 26;

                    // ── 3. Code 128 — bar height variation ────────────────────
                    $section('3.  Code 128 — varying bar height');

                    $bc128h = Code128::encode('HEIGHT');
                    $heights = [15.0, 25.0, 40.0, 60.0];
                    $bx = $lm;

                    foreach ($heights as $bh) {
                        $s->drawBarcode(
                            $bc128h,
                            x: $bx,
                            y: $y - $bh,
                            height: $bh,
                            moduleWidth: 1.0,
                        );
                        $caption("{$bh} pt", $bx, $y - $bh - 12);
                        $bars  = $bc128h->getBars();
                        $bw    = ((int) array_sum($bars) + 20) * 1.0;
                        $bx   += $bw + 8;
                    }
                    $y -= 60 + 26;
                });
        })
        ->page(function (PdfPageBuilder $page) use ($pageW, $pageH, $lm, $helv, $helvB): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(function (PdfContentStreamBuilder $s) use (
                    $pageW,
                    $pageH,
                    $lm,
                    $helv,
                    $helvB,
                ): void {

                    $y = $pageH - 60.0;

                    $s->beginText()->setFont('FB', 16)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Linear Barcodes — EAN-13')
                      ->endText();
                    $y -= 8;
                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                      ->moveTo($lm, $y)->lineTo($pageW - $lm, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 24;

                    $section = function (string $title) use ($s, $lm, &$y): void {
                        $s->beginText()->setFont('FB', 11)
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($title)->endText();
                        $y -= 14;
                    };

                    $caption = function (string $text, float $cx, float $cy) use ($s): void {
                        $s->beginText()->setFont('F1', 8)
                          ->fillColor(Color::rgb(0.3, 0.3, 0.3))
                          ->setTextMatrix(Matrix::translate($cx, $cy))
                          ->showText($text)->endText();
                    };

                    // ── 4. EAN-13 samples ─────────────────────────────────────
                    $section('4.  EAN-13 — retail product codes (12 digits, check computed)');

                    $ean13Samples = [
                        ['5901234123457', 'Generic product (verified)'],
                        ['978038547340',  'ISBN-13 (12 digits in)'],
                        ['400638133393',  'German product code'],
                        ['012345678905',  'UPC-A compatible'],
                    ];

                    $mw = 1.0;
                    $bh = 40.0;
                    $bx = $lm;

                    foreach ($ean13Samples as [$digits, $label]) {
                        $bc  = EAN13::encode($digits);
                        $bars = $bc->getBars();
                        $bw   = ((int) array_sum($bars) + 14) * $mw; // EAN quiet: 11 left + 7 right ≈ 14

                        $s->drawBarcode(
                            $bc,
                            x: $bx,
                            y: $y - $bh,
                            height: $bh,
                            moduleWidth: $mw,
                            quietZone: 7.0,
                            fontName: 'F1',
                            fontSize: 7.5,
                            metrics: $helv,
                        );

                        // First digit label (left of barcode, EAN convention)
                        $s->beginText()->setFont('F1', 7)
                          ->fillColor(Color::rgb(0, 0, 0))
                          ->setTextMatrix(Matrix::translate($bx - 1, $y - $bh + 2))
                          ->showText($bc->getText()[0])
                          ->endText();

                        $caption($label, $bx, $y - $bh - 20);
                        $bx += $bw + 14;

                        // Wrap to next row after 2
                        if ($bx > $pageW - $lm - 80) {
                            $bx  = $lm;
                            $y  -= $bh + 36;
                        }
                    }
                    $y -= $bh + 40;

                    // ── 5. EAN-13 — module width variation ────────────────────
                    $section('5.  EAN-13 — varying module width');

                    $bcEan = EAN13::encode('590123412345');
                    $bx = $lm;

                    foreach ([0.8, 1.0, 1.4] as $mw) {
                        $bars = $bcEan->getBars();
                        $bh   = 35.0;
                        $bw   = ((int) array_sum($bars) + 14) * $mw;

                        $s->drawBarcode(
                            $bcEan,
                            x: $bx,
                            y: $y - $bh,
                            height: $bh,
                            moduleWidth: $mw,
                            quietZone: 7.0,
                            fontName: 'F1',
                            fontSize: max(6.0, $mw * 7),
                            metrics: $helv,
                        );
                        $caption("{$mw} pt/module", $bx, $y - $bh - 20);
                        $bx += $bw + 14;
                    }
                    $y -= $bh + 40;

                    // ── 6. Side-by-side comparison ────────────────────────────
                    $section('6.  Code 128 vs EAN-13 — encoding the same digits');

                    $digits = '5901234123457';
                    $bh     = 36.0;

                    $bc128 = Code128::encode($digits);
                    $bars128 = $bc128->getBars();
                    $bw128   = ((int) array_sum($bars128) + 20) * 0.9;

                    $bcEan13 = EAN13::encode($digits);
                    $barsEan = $bcEan13->getBars();
                    $bwEan   = ((int) array_sum($barsEan) + 14) * 0.9;

                    $s->drawBarcode(
                        $bc128,
                        x: $lm,
                        y: $y - $bh,
                        height: $bh,
                        moduleWidth: 0.9,
                        fontName: 'F1',
                        fontSize: 7,
                        metrics: $helv,
                    );
                    $caption('Code 128 B  (' . count($bars128) . ' bar segments)', $lm, $y - $bh - 19);

                    $s->drawBarcode(
                        $bcEan13,
                        x: $lm + $bw128 + 20,
                        y: $y - $bh,
                        height: $bh,
                        moduleWidth: 0.9,
                        quietZone: 7.0,
                        fontName: 'F1',
                        fontSize: 7,
                        metrics: $helv,
                    );
                    $caption('EAN-13  (' . count($barsEan) . ' bar segments)', $lm + $bw128 + 20, $y - $bh - 19);
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

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
