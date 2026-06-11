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
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;

const LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. '
    . 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. '
    . 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris '
    . 'nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in '
    . 'reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla '
    . 'pariatur. Excepteur sint occaecat cupidatat non proident.';

function heading(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()->setFont('F2', 10)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)->endText();
}

function generate(): void
{
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();
    $times = Type1FontMetrics::timesRoman();
    $courier = Type1FontMetrics::courier();

    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Text Layout')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($helv, $helvB, $times, $courier): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->useType1Font('F3', 'Times-Roman')
                ->useType1Font('F4', 'Courier')
                ->content(static function (PdfContentStreamBuilder $s) use ($helv, $helvB, $times, $courier): void {

                    $marginL = 72.0;
                    $pageW = 451.0; // usable width (A4 595 − 2×72)
                    $y = 800.0; // current baseline, decrements as we go

                    // =========================================================
                    // Title
                    // =========================================================
                    $title = TextBox::create('Text Layout Utilities', $helvB, 18, $pageW);
                    $s->drawTextBox($title, fontName: 'F2', x: $marginL, y: $y);
                    $y -= $title->getHeight() + 16;

                    // =========================================================
                    // 1. Word wrapping + multiple paragraphs (side by side)
                    // =========================================================
                    heading($s, $marginL, $y, '1. Word wrapping');
                    heading($s, $marginL + 220, $y, '2. Multiple paragraphs');
                    $y -= 14;

                    $multiPara =
                        "First paragraph: text wraps and flows naturally within the column.\n\n"
                        . "Second paragraph: a blank line above creates visual separation "
                        . "between paragraphs, as in normal typography.\n\n"
                        . "Third paragraph: short.";

                    $leftBox = TextBox::create(LOREM, $helv, 10, 200, 13);
                    $rightBox = TextBox::create($multiPara, $helv, 10, 210, 13);

                    $s->drawTextBox($leftBox, fontName: 'F1', x: $marginL, y: $y);
                    $s->drawTextBox($rightBox, fontName: 'F1', x: $marginL + 220, y: $y);

                    $y -= max($leftBox->getHeight(), $rightBox->getHeight()) + 18;

                    // =========================================================
                    // 3. Text alignment
                    // =========================================================
                    heading($s, $marginL, $y, '3. Text alignment');
                    $y -= 14;

                    // Four columns — the Justify column needs enough words per line
                    // to make the extra word spacing clearly visible.
                    $short = 'The quick brown fox jumps over the lazy dog. '
                        . 'Pack my box with five dozen liquor jugs.';
                    $colW = 100.0;
                    $gap = 12.0;
                    $maxAlignH = 0.0;

                    foreach (
                        [
                        [TextAlign::Left, 'Left', $marginL],
                        [TextAlign::Center, 'Centre', $marginL + $colW + $gap],
                        [TextAlign::Right, 'Right', $marginL + ($colW + $gap) * 2],
                        [TextAlign::Justify, 'Justify', $marginL + ($colW + $gap) * 3],
                        ] as [$align, $label, $bx]
                    ) {
                        $box = TextBox::create($short, $helv, 10, $colW, 13, $align);

                        // shaded background
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#f0f0f0'))
                          ->rectangle($bx, $y - $box->getHeight() - 2, $colW, $box->getHeight() + 14)
                          ->fill()
                          ->restoreGraphicsState();

                        $s->beginText()->setFont('F2', 9)
                          ->setTextMatrix(Matrix::translate($bx + 2, $y))
                          ->showText($label)->endText();

                        $s->drawTextBox($box, fontName: 'F1', x: $bx, y: $y - 12);

                        $maxAlignH = max($maxAlignH, $box->getHeight() + 14);
                    }

                    $y -= $maxAlignH + 18;

                    // =========================================================
                    // 4. Typefaces
                    // =========================================================
                    heading($s, $marginL, $y, '4. Typefaces');
                    $y -= 14;

                    $sample = 'Pack my box with five dozen liquor jugs.';

                    foreach (
                        [
                        ['Helvetica 11pt', $helv, 11, 'F1'],
                        ['Helvetica-Bold 11pt', $helvB, 11, 'F2'],
                        ['Times-Roman 12pt', $times, 12, 'F3'],
                        ['Courier 10pt', $courier, 10, 'F4'],
                        ] as [$label, $m, $sz, $fn]
                    ) {
                        $s->beginText()->setFont('F2', 8)
                          ->setTextMatrix(Matrix::translate($marginL, $y + 1))
                          ->showText($label)->endText();

                        $box = TextBox::create($sample, $m, $sz, 340, $sz * 1.2);
                        $s->drawTextBox($box, fontName: $fn, x: $marginL + 110, y: $y);
                        $y -= $box->getHeight() + 4;
                    }

                    $y -= 14;

                    // =========================================================
                    // 5. Overflow detection
                    // =========================================================
                    heading($s, $marginL, $y, '5. Overflow detection (maxHeight clips output)');
                    $y -= 14;

                    // In PDF, the Y coordinate is the text BASELINE — glyphs extend
                    // above it by the cap height (≈7 pt for 10 pt Helvetica).
                    // $topPad reserves that space so text caps appear inside the box.
                    $topPad = 9.0; // cap height + 2 pt breathing room
                    $botPad = 4.0; // space below the last descender
                    $maxH = 42.0; // maximum height available for text lines

                    $bigBox = TextBox::create(LOREM, $helv, 10, 300, 14);

                    // Rectangle encloses text visually: from ($y) down to ($y - total).
                    $rectH = $topPad + $maxH + $botPad;
                    $s->saveGraphicsState()
                      ->strokeColor(Color::fromHex('#999999'))
                      ->setLineWidth(0.5)
                      ->setDashPattern([3, 2], 0)
                      ->rectangle($marginL, $y - $rectH, 300, $rectH)->stroke()
                      ->restoreGraphicsState();

                    // First baseline is $topPad below the rectangle top so that
                    // cap tops land just inside the upper edge.
                    $s->drawTextBox($bigBox, fontName: 'F1', x: $marginL, y: $y - $topPad, maxHeight: $maxH);

                    $shown = count($bigBox->linesFor($maxH));
                    $remaining = count($bigBox->getLines()) - $shown;
                    // Gap must clear the ascender of the 9 pt label below (≈6 pt),
                    // so 14 pt gives comfortable separation from the rectangle edge.
                    $y -= $rectH + 14;

                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText("{$shown} lines shown, {$remaining} hidden — "
                          . 'overflows(): ' . ($bigBox->overflows($maxH) ? 'true' : 'false'))
                      ->endText();

                    $y -= 22;

                    // =========================================================
                    // 6. Two-column layout
                    // =========================================================
                    heading($s, $marginL, $y, '6. Two-column layout with overflow reflow');
                    $y -= 14;

                    $colW2 = 210.0;
                    $col2X = $marginL + $colW2 + 12;
                    $avail = $y - 40; // leave 40 pt bottom margin

                    // Three copies of LOREM guarantee overflow into the second column.
                    $allBox = TextBox::create(LOREM . ' ' . LOREM . ' ' . LOREM, $times, 10, $colW2, 13);
                    $col1Lines = $allBox->linesFor($avail);

                    $overflow = implode(' ', array_filter(
                        array_slice($allBox->getLines(), count($col1Lines)),
                        static fn ($l) => $l !== '',
                    ));
                    $col2Box = TextBox::create($overflow, $times, 10, $colW2, 13);

                    // Column separator
                    $s->saveGraphicsState()
                      ->strokeColor(Color::fromHex('#bbbbbb'))
                      ->setLineWidth(0.5)
                      ->moveTo($marginL + $colW2 + 6, $y + 2)
                      ->lineTo($marginL + $colW2 + 6, $y - $avail)
                      ->stroke()
                      ->restoreGraphicsState();

                    $col1Rebuilt = TextBox::create(
                        implode("\n", $col1Lines),
                        $times,
                        10,
                        $colW2,
                        13,
                    );
                    $s->drawTextBox($col1Rebuilt, fontName: 'F3', x: $marginL, y: $y);
                    $s->drawTextBox($col2Box, fontName: 'F3', x: $col2X, y: $y);
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
