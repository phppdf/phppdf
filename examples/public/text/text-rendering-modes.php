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
    [$pageW, $pageH] = PdfPageSize::A4;
    $lm = 72.0;

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Text Rendering Modes')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($pageW, $pageH, $lm): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($pageW, $pageH, $lm): void {

                    $y = $pageH - 60.0;

                    $s->beginText()->setFont('FB', 18)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Text Rendering Modes  (setTextRenderingMode)')
                      ->endText();
                    $y -= 8;
                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                      ->moveTo($lm, $y)->lineTo($pageW - $lm, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 22;

                    $modes = [
                        [0, 'Mode 0 — Fill', 'Normal filled text. The current non-stroking colour fills each glyph.'],
                        [1, 'Mode 1 — Stroke', 'Glyph outlines are stroked. Works best at large font sizes.'],
                        [2, 'Mode 2 — Fill + Stroke', 'Glyphs are filled first, then their outlines are stroked.'],
                        [3, 'Mode 3 — Invisible', 'Glyphs produce no visible marks. Useful for hidden text layers (e.g. OCR behind an image).'],
                        [4, 'Mode 4 — Fill + Clip', 'Fills glyphs and adds them to the current clipping path. Subsequent graphics are clipped to the glyph shapes.'],
                        [5, 'Mode 5 — Stroke + Clip', 'Strokes glyph outlines and adds them to the clipping path.'],
                        [6, 'Mode 6 — Fill + Stroke + Clip', 'Fills, strokes, and clips simultaneously.'],
                        [7, 'Mode 7 — Clip only', 'Adds glyph shapes to the clipping path without any visible mark. Used before drawing graphics that should appear only inside the letters.'],
                    ];

                    foreach ($modes as [$mode, $label, $description]) {
                        // Label + description
                        $s->beginText()->setFont('FB', 9)
                          ->fillColor(Color::rgb(0.3, 0.3, 0.3))
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($label)->endText();
                        $y -= 11;

                        $s->beginText()->setFont('F1', 8)
                          ->fillColor(Color::rgb(0.5, 0.5, 0.5))
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($description)->endText();
                        $y -= 13;

                        // Demo text block
                        $demoY = $y;
                        $demoH = 42.0;

                        // Coloured background so clip-mode effects are visible
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#e8eeff'))
                          ->rectangle($lm, $demoY - $demoH, $pageW - $lm * 2, $demoH)
                          ->fill()
                          ->restoreGraphicsState();

                        // For clip modes (4-7), draw a gradient-like stripe behind the text
                        // before the clip is applied, so the effect is obvious.
                        if ($mode >= 4) {
                            // Horizontal colour bands to reveal clipping
                            $bands = 8;
                            $bw = ($pageW - $lm * 2) / $bands;

                            for ($b = 0; $b < $bands; $b++) {
                                $hue = $b / $bands;
                                $r = max(0, 1 - $hue * 2);
                                $g = min(1, $hue * 2);
                                $bl = 0.5 - abs($hue - 0.5);
                                $s->saveGraphicsState()
                                  ->fillColor(Color::rgb($r, $g, $bl))
                                  ->rectangle($lm + $b * $bw, $demoY - $demoH, $bw, $demoH)
                                  ->fill()
                                  ->restoreGraphicsState();
                            }
                        }

                        $s->saveGraphicsState();

                        // Colours for the text itself
                        $s->fillColor(Color::rgb(0.1, 0.1, 0.8)) // fill colour
                          ->strokeColor(Color::rgb(0.8, 0.1, 0.1)) // stroke colour
                          ->setLineWidth(0.6);

                        if ($mode >= 4) {
                            // Clip modes: apply text as clip then draw coloured rectangle inside
                            $s->beginText()
                              ->setFont('FB', 34)
                              ->setTextRenderingMode($mode)
                              ->setTextMatrix(Matrix::translate($lm + 4, $demoY - 36))
                              ->showText('Clip Mode ' . $mode)
                              ->endText();

                            // Draw a filled rectangle that is clipped to the glyph shapes
                            $s->fillColor(Color::rgb(0.05, 0.05, 0.05))
                              ->rectangle($lm, $demoY - $demoH, $pageW - $lm * 2, $demoH)
                              ->fill();
                        } else {
                            $s->beginText()
                              ->setFont('FB', 34)
                              ->setTextRenderingMode($mode)
                              ->setTextMatrix(Matrix::translate($lm + 4, $demoY - 36))
                              ->showText('Mode ' . $mode . ' Sample Text')
                              ->endText();
                        }

                        $s->restoreGraphicsState();
                        $y -= $demoH + 14;
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
