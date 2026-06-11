<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Text\TeXHyphenator;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;

const LOREM_HY = 'Typography is the art and technique of arranging type to make written language '
    . 'legible, readable, and appealing when displayed. The arrangement of type involves '
    . 'selecting typefaces, point sizes, line lengths, line-spacing, and letter-spacing, '
    . 'and adjusting the space between pairs of letters. Hyphenation dramatically improves '
    . 'text density and visual consistency, especially in narrow columns and justified layouts. '
    . 'Extraordinarily sophisticated algorithms such as the Knuth-Liang method can decompose '
    . 'words like "hyphenation", "typographical", and "uncharacteristically" at their natural '
    . 'syllable boundaries.';

function generate(): void
{
    $helv = Type1FontMetrics::helvetica();
    $hyphenator = new TeXHyphenator(
        file(__DIR__ . '/../../../resources/hyphenation/en-US.tex', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
    );
    $fontSize = 11.0;
    $marginL = 72.0;
    $pageW = 595.0; // A4 width in points
    $gap = 18.0;
    $colWidth = ($pageW - 2 * $marginL - 2 * $gap) / 3; // ~139 pt

    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Hyphenation Example')->author('phppdf'))
        ->page(
            static function (PdfPageBuilder $page) use ($helv, $hyphenator, $colWidth, $fontSize, $marginL, $gap): void {
                $page
                    ->size(...PdfPageSize::A4)
                    ->useType1Font('F1', 'Helvetica')
                    ->useType1Font('F2', 'Helvetica-Bold')
                    ->content(
                        static function (PdfContentStreamBuilder $s) use ($helv, $hyphenator, $colWidth, $fontSize, $marginL, $gap): void {

                            $y = 790.0;
                            $col = static fn (int $n): float => $marginL + $n * ($colWidth + $gap);

                        // ---- Helper: draw a column label ----
                            $label = static function (string $text, float $x) use ($s, $y): void {
                                $s->beginText()->setFont('F2', 9)
                                    ->setTextMatrix(Matrix::translate($x, $y + 14))
                                    ->showText($text)->endText();
                            };

                        // ---- Without hyphenation ----
                            $label('Without hyphenation (Justify)', $col(0));
                            $boxPlain = TextBox::create(
                                text: LOREM_HY,
                                metrics: $helv,
                                fontSize: $fontSize,
                                maxWidth: $colWidth,
                                align: TextAlign::Justify,
                            );
                            $s->drawTextBox($boxPlain, fontName: 'F1', x: $col(0), y: $y);

                        // ---- With hyphenation ----
                            $label('With hyphenation (Justify)', $col(1));
                            $boxHyphen = TextBox::create(
                                text: LOREM_HY,
                                metrics: $helv,
                                fontSize: $fontSize,
                                maxWidth: $colWidth,
                                align: TextAlign::Justify,
                                hyphenator: $hyphenator,
                            );
                            $s->drawTextBox($boxHyphen, fontName: 'F1', x: $col(1), y: $y);

                        // ---- With hyphenation, Left-aligned ----
                            $label('With hyphenation (Left)', $col(2));
                            $boxLeft = TextBox::create(
                                text: LOREM_HY,
                                metrics: $helv,
                                fontSize: $fontSize,
                                maxWidth: $colWidth,
                                align: TextAlign::Left,
                                hyphenator: $hyphenator,
                            );
                            $s->drawTextBox($boxLeft, fontName: 'F1', x: $col(2), y: $y);
                        },
                    );
            },
        )
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
