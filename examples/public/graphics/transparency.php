<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\BlendMode;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function label(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('Fh', 8)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

function sectionHeading(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FhB', 11)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    $helv = Type1FontMetrics::helvetica();

    // Register one named graphics state per scenario we want to demonstrate.
    // Names follow the convention GS_<description>.

    $blendModes = [
        BlendMode::Normal,
        BlendMode::Multiply,
        BlendMode::Screen,
        BlendMode::Overlay,
        BlendMode::Darken,
        BlendMode::Lighten,
        BlendMode::ColorDodge,
        BlendMode::ColorBurn,
        BlendMode::HardLight,
        BlendMode::SoftLight,
        BlendMode::Difference,
        BlendMode::Exclusion,
    ];

    $document = (new PdfDocumentBuilder())
        ->version(PdfVersion::PDF_1_4) // transparency requires 1.4+
        ->info((new PdfDocumentInfo())->title('Transparency & Blend Modes')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($blendModes): void {
            $page->size(...PdfPageSize::A4)
                 ->useType1Font('Fh', 'Helvetica')
                 ->useType1Font('FhB', 'Helvetica-Bold');

            // ── Opacity states ────────────────────────────────────────────
            foreach ([10, 25, 50, 75, 90] as $pct) {
                $alpha = $pct / 100;
                $page->useGraphicsState(
                    "GS_fill{$pct}",
                    new PdfGraphicsStateDictionary(fillAlpha: $alpha, strokeAlpha: 1.0),
                );
                $page->useGraphicsState(
                    "GS_stroke{$pct}",
                    new PdfGraphicsStateDictionary(fillAlpha: 1.0, strokeAlpha: $alpha),
                );
            }

            // ── Blend-mode states (full opacity so the mode is visible) ───
            foreach ($blendModes as $mode) {
                $page->useGraphicsState(
                    'GS_bm_' . $mode->value,
                    new PdfGraphicsStateDictionary(fillAlpha: 1.0, blendMode: $mode),
                );
            }

            $page->content(static function (PdfContentStreamBuilder $s) use ($blendModes): void {

                $ml = 52.0; // left margin
                $y = 800.0; // cursor

                // ============================================================
                // Section 1 — Fill opacity
                // ============================================================
                sectionHeading($s, $ml, $y, '1. Fill opacity (ca)');
                $y -= 18;

                // Solid coloured strip as background reference.
                $stripW = 451.0;
                $stripH = 36.0;
                $s->saveGraphicsState()
                  ->fillColor(Color::rgb(0.20, 0.55, 0.90))
                  ->rectangle($ml, $y - $stripH, $stripW, $stripH)
                  ->fill()
                  ->restoreGraphicsState();

                // Five rectangles of decreasing fill opacity overlaid on the strip.
                $rectW = 72.0;
                $spacer = 8.0;

                foreach ([10, 25, 50, 75, 90] as $i => $pct) {
                    $rx = $ml + $i * ($rectW + $spacer);
                    $s->saveGraphicsState()
                      ->setGraphicsStateParameters("GS_fill{$pct}")
                      ->fillColor(Color::rgb(0.90, 0.25, 0.10))
                      ->rectangle($rx, $y - $stripH, $rectW, $stripH)
                      ->fill()
                      ->restoreGraphicsState();
                    label($s, $rx + 4, $y - $stripH + 4, "{$pct}%");
                }

                $y -= $stripH + 8;
                label($s, $ml, $y, 'Red rectangles at 10 / 25 / 50 / 75 / 90 % fill opacity over a blue background.');
                $y -= 28;

                // ============================================================
                // Section 2 — Stroke opacity
                // ============================================================
                sectionHeading($s, $ml, $y, '2. Stroke opacity (CA)');
                $y -= 18;

                // Solid fill, fading stroke width 6.
                foreach ([10, 25, 50, 75, 90] as $i => $pct) {
                    $rx = $ml + $i * ($rectW + $spacer);
                    $s->saveGraphicsState()
                      ->setGraphicsStateParameters("GS_stroke{$pct}")
                      ->setLineWidth(6)
                      ->strokeColor(Color::rgb(0.10, 0.10, 0.80))
                      ->fillColor(Color::rgb(0.92, 0.92, 0.92))
                      ->rectangle($rx, $y - $stripH, $rectW, $stripH)
                      ->fillAndStroke()
                      ->restoreGraphicsState();
                    label($s, $rx + 4, $y - $stripH + 4, "{$pct}%");
                }

                $y -= $stripH + 8;
                label($s, $ml, $y, 'Blue stroke at 10 / 25 / 50 / 75 / 90 % stroke opacity over a light fill.');
                $y -= 28;

                // ============================================================
                // Section 3 — Blend modes
                // A warm amber base rectangle + a cool indigo overlay rectangle
                // applied with each blend mode. Twelve modes in a 4×3 grid.
                // ============================================================
                sectionHeading($s, $ml, $y, '3. Blend modes (BM) — amber base + indigo overlay');
                $y -= 16;

                $cols = 4;
                $cellW = 108.0;
                $cellH = 56.0;
                $gapX = 8.0;
                $gapY = 22.0; // room for the label below each cell

                foreach ($blendModes as $idx => $mode) {
                    $col = $idx % $cols;
                    $row = intdiv($idx, $cols);
                    $cx = $ml + $col * ($cellW + $gapX);
                    $cy = $y - $row * ($cellH + $gapY);

                    // Base layer — amber (no special graphics state).
                    $s->saveGraphicsState()
                      ->fillColor(Color::rgb(0.95, 0.65, 0.10))
                      ->rectangle($cx, $cy - $cellH, $cellW, $cellH)
                      ->fill()
                      ->restoreGraphicsState();

                    // Overlay layer — indigo, with the named blend mode.
                    $overW = $cellW * 0.65;
                    $overX = $cx + ($cellW - $overW) / 2;
                    $s->saveGraphicsState()
                      ->setGraphicsStateParameters('GS_bm_' . $mode->value)
                      ->fillColor(Color::rgb(0.20, 0.10, 0.75))
                      ->rectangle($overX, $cy - $cellH + 6, $overW, $cellH - 12)
                      ->fill()
                      ->restoreGraphicsState();

                    // Mode name label beneath the cell.
                    label($s, $cx, $cy - $cellH - 12, $mode->value);
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
