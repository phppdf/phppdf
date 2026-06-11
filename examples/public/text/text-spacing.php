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

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;
    $lm = 72.0;
    $helv = Type1FontMetrics::helvetica();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Text Spacing Controls')
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
                      ->showText('Text Spacing Controls')
                      ->endText();
                    $y -= 8;
                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                      ->moveTo($lm, $y)->lineTo($pageW - $lm, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 24;

                    // Helper: draw a section heading + a coloured row for each variant
                    $section = static function (string $title, string $api) use ($s, $lm, &$y): void {
                        $s->beginText()->setFont('FB', 11)
                          ->fillColor(Color::rgb(0, 0, 0))
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($title)->endText();
                        $y -= 12;
                        $s->beginText()->setFont('F1', 8)
                          ->fillColor(Color::rgb(0.2, 0.4, 0.7))
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($api)->endText();
                        $y -= 14;
                    };

                    $row = static function (string $label, callable $draw) use ($s, $lm, $pageW, &$y): void {
                        $rh = 22.0;
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#f5f5f5'))
                          ->rectangle($lm, $y - $rh, $pageW - $lm * 2, $rh)
                          ->fill()
                          ->restoreGraphicsState();

                        $s->beginText()->setFont('F1', 8)
                          ->fillColor(Color::rgb(0.4, 0.4, 0.4))
                          ->setTextMatrix(Matrix::translate($lm + 3, $y - 7))
                          ->showText($label)->endText();

                        $draw($s, $lm + 130, $y - 16);
                        $y -= $rh + 2;
                    };

                    // ── 1. Character spacing (Tc) ─────────────────────────────
                    $section('1.  Character spacing  (Tc)', 'setCharacterSpacing(float $spacing)  — extra space after every glyph in points');

                foreach ([-0.5, 0, 1, 2, 4] as $tc) {
                    $row("Tc = {$tc} pt", static function (PdfContentStreamBuilder $s, float $x, float $y) use ($tc): void {
                        $s->beginText()->setFont('F1', 11)
                          ->setCharacterSpacing($tc)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText('The quick brown fox')
                          ->setCharacterSpacing(0)
                          ->endText();
                    });
                }

                    $y -= 8;

                    // ── 2. Word spacing (Tw) ──────────────────────────────────
                    $section('2.  Word spacing  (Tw)', 'setWordSpacing(float $spacing)  — extra space after each ASCII space character');

                foreach ([-2, 0, 4, 8, 16] as $tw) {
                    $row("Tw = {$tw} pt", static function (PdfContentStreamBuilder $s, float $x, float $y) use ($tw): void {
                        $s->beginText()->setFont('F1', 11)
                          ->setWordSpacing($tw)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText('Pack my box with five jugs')
                          ->setWordSpacing(0)
                          ->endText();
                    });
                }

                    $y -= 8;

                    // ── 3. Horizontal scaling (Tz) ────────────────────────────
                    $section('3.  Horizontal text scaling  (Tz)', 'setHorizontalTextScaling(float $scale)  — percentage of normal width (100 = normal)');

                foreach ([60, 80, 100, 120, 150] as $tz) {
                    $row("Tz = {$tz}%", static function (PdfContentStreamBuilder $s, float $x, float $y) use ($tz): void {
                        $s->beginText()->setFont('F1', 11)
                          ->setHorizontalTextScaling($tz)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText('Horizontal scaling')
                          ->setHorizontalTextScaling(100)
                          ->endText();
                    });
                }

                    $y -= 8;

                    // ── 4. Text rise (Ts) ─────────────────────────────────────
                    $section('4.  Text rise  (Ts)', 'setTextRise(float $rise)  — shifts baseline up (positive) or down (negative) for super/subscript');

                    $row('super/subscript demo', static function (PdfContentStreamBuilder $s, float $x, float $y): void {
                        $s->beginText()->setFont('F1', 12)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText('H')
                          ->setFont('F1', 8)->setTextRise(-3)->showText('2')
                          ->setFont('F1', 12)->setTextRise(0)->showText('O  and  E = mc')
                          ->setFont('F1', 8)->setTextRise(5)->showText('2')
                          ->setFont('F1', 12)->setTextRise(0)
                          ->endText();
                    });

                    foreach ([-4, 0, 4, 8] as $ts) {
                        $row("Ts = {$ts} pt", static function (PdfContentStreamBuilder $s, float $x, float $y) use ($ts): void {
                            $s->beginText()->setFont('F1', 11)
                              ->setTextRise($ts)
                              ->setTextMatrix(Matrix::translate($x, $y))
                              ->showText('Text rise baseline shift')
                              ->setTextRise(0)
                              ->endText();
                        });
                    }

                    $y -= 8;

                    // ── 5. showTextWithPositioning (TJ) ───────────────────────
                    $section('5.  Manual glyph positioning  (TJ)', 'showTextWithPositioning(array $elements)  — interleave strings and kerning adjustments (1/1000 text-space units)');

                    $row('without kerning', static function (PdfContentStreamBuilder $s, float $x, float $y): void {
                        $s->beginText()->setFont('F1', 14)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showText('WAVE AWAY')
                          ->endText();
                    });

                    $row('with TJ kerning', static function (PdfContentStreamBuilder $s, float $x, float $y): void {
                        $s->beginText()->setFont('F1', 14)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showTextWithPositioning([
                              'W', -60, 'A', -40, 'V', -30, 'E',
                              200,
                              'A', -40, 'W', -60, 'A', -40, 'Y',
                          ])
                          ->endText();
                    });

                    $row('optical margin (negative)', static function (PdfContentStreamBuilder $s, float $x, float $y): void {
                        $s->beginText()->setFont('F1', 14)
                          ->setTextMatrix(Matrix::translate($x, $y))
                          ->showTextWithPositioning(['T', -80, 'o', 80, ' ', 0, 'V', -60, 'a', 20, 'r', 0, 'y'])
                          ->endText();
                    });
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
