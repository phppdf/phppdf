<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

// ---------------------------------------------------------------------------
// Font discovery
// ---------------------------------------------------------------------------

function findFont(): array
{
    $candidates = [
        // NotoSansCJK covers Latin + CJK: large file, dramatic subsetting ratio
        ['/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc', 0,
            'NotoSansCJK-Regular (TTC, font 0)'],
        // NotoSansMono: smaller but still good for demonstrating subsetting
        ['/usr/share/fonts/truetype/noto/NotoSansMono-Regular.ttf', 0,
            'NotoSansMono-Regular (TTF)'],
        // DejaVu Sans: commonly installed
        ['/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 0,
            'DejaVuSans (TTF)'],
    ];

    foreach ($candidates as [$path, $index, $label]) {
        if (is_readable($path)) {
            return [TrueTypeFont::fromFile($path, $index), $label, filesize($path)];
        }
    }

    throw new RuntimeException(
        'No suitable font found. Install fonts-noto-core or fonts-noto-cjk.',
    );
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    [$font, $fontLabel, $fontFileSize] = findFont();

    // The lines of text that will be rendered in the document
    $lines = [
        'Font Subsetting Demo',
        'Only the glyphs used on this page are embedded.',
        'Latin: The quick brown fox jumps over the lazy dog.',
        'Digits: 0123456789',
        'Symbols: ! @ # $ % & * ( ) + = - _ . , : ; ? /',
    ];

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Font Subsetting Demo')
                ->author('phppdf'),
        )
        ->page(function (PdfPageBuilder $page) use ($font, $fontLabel, $fontFileSize, $lines): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->useEmbeddedFont('FE', $font)
                ->content(function (PdfContentStreamBuilder $s) use (
                    $font,
                    $fontLabel,
                    $fontFileSize,
                    $lines,
                ): void {
                    $ml = 72.0;
                    $y  = 770.0;

                    // ── Page title ────────────────────────────────────────
                    $s->beginText()
                      ->setFont('F2', 18)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText('Font Subsetting')
                      ->endText();
                    $y -= 26;

                    $s->beginText()
                      ->setFont('F1', 10)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText("Font: {$fontLabel}")
                      ->endText();
                    $y -= 14;

                    $s->beginText()
                      ->setFont('F1', 10)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText('Full file size: ' . number_format($fontFileSize) . ' bytes ('
                          . round($fontFileSize / 1024) . ' KB)')
                      ->endText();
                    $y -= 24;

                    // ── Separator ─────────────────────────────────────────
                    $s->saveGraphicsState()
                      ->setLineWidth(0.5)
                      ->strokeColor(Color::gray(0.4))
                      ->moveTo($ml, $y)
                      ->lineTo(523.0, $y)
                      ->stroke()
                      ->restoreGraphicsState();
                    $y -= 18;

                    // ── Embedded font text demo ───────────────────────────
                    $s->beginText()
                      ->setFont('F2', 10)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText('Text rendered with the embedded (subsetted) font:')
                      ->endText();
                    $y -= 16;

                    $s->beginText()->setFont('FE', 13);
                    foreach ($lines as $line) {
                        $s->setTextMatrix(Matrix::translate($ml, $y))
                          ->showText($line);
                        $y -= 18;
                    }
                    $s->endText();
                    $y -= 10;

                    // ── Separator ─────────────────────────────────────────
                    $s->saveGraphicsState()
                      ->setLineWidth(0.5)
                      ->strokeColor(Color::gray(0.4))
                      ->moveTo($ml, $y)->lineTo(523.0, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 20;

                    // ── How subsetting works ──────────────────────────────
                    $s->beginText()
                      ->setFont('F2', 11)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText('How it works')
                      ->endText();
                    $y -= 16;

                    $explanationLines = [
                        '1. PdfContentStreamBuilder tracks every glyph ID emitted by showText().',
                        '2. After the content stream is built, compileEmbeddedFont() calls',
                        '   TrueTypeFont::subset() with the collected glyph ID set.',
                        '3. TrueTypeSubsetter rebuilds six binary tables from the original font:',
                        '     glyf   — only the needed glyph outlines (empty slot for all others)',
                        '     loca   — rewritten offsets into the new glyf table',
                        '     hmtx   — trimmed to maxGID + 1 advance-width entries',
                        '     maxp   — numGlyphs updated to maxGID + 1',
                        '     hhea   — numberOfHMetrics updated',
                        '     head   — indexToLocFormat and checkSumAdjustment recalculated',
                        '4. Composite glyphs are expanded transitively so component glyphs',
                        '   (e.g. accent marks referenced by precomposed characters) are included.',
                        '5. The cmap table is omitted; PDF uses Identity-H encoding and the',
                        '   ToUnicode CMap built by the library for text extraction.',
                    ];

                    $s->beginText()->setFont('F1', 9);
                    foreach ($explanationLines as $line) {
                        $s->setTextMatrix(Matrix::translate($ml, $y))
                          ->showText($line);
                        $y -= 13;
                    }
                    $s->endText();
                    $y -= 10;

                    // ── Separator ─────────────────────────────────────────
                    $s->saveGraphicsState()
                      ->setLineWidth(0.5)
                      ->strokeColor(Color::gray(0.4))
                      ->moveTo($ml, $y)->lineTo(523.0, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 20;

                    // ── Limitations note ──────────────────────────────────
                    $s->beginText()
                      ->setFont('F2', 11)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText('Limitations')
                      ->endText();
                    $y -= 15;

                    $limits = [
                        '* CFF / OpenType fonts (sfVersion OTTO) are embedded in full.',
                        '  CFF subsetting requires a separate CFF Charstring parser.',
                        '* The subset font keeps the original glyph IDs so the PDF',
                        '  content stream (which encodes GIDs directly via Identity-H)',
                        '  requires no modification.',
                        '* Subsetting is skipped and the original data is embedded if',
                        '  anything in the binary parsing fails (safe fallback).',
                    ];

                    $s->beginText()->setFont('F1', 9);
                    foreach ($limits as $line) {
                        $s->setTextMatrix(Matrix::translate($ml, $y))
                          ->showText($line);
                        $y -= 13;
                    }
                    $s->endText();
                });
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    $pdfSize = $output->position();

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $pdfSize);
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    header('X-Font-Full-Size: ' . $fontFileSize);
    header('X-Pdf-Size: ' . $pdfSize);
    echo $output->getContent();
}

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
