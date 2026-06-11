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
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TeXHyphenator;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextSpan;

function buildSpans(Type1FontMetrics $regular, Type1FontMetrics $bold, Type1FontMetrics $italic): array
{
    return [
        TextSpan::create('Typography ', 'F2', 11, $bold),
        TextSpan::create(
            'is the art of arranging type to make written language legible and appealing. ',
            'F1',
            11,
            $regular,
        ),
        TextSpan::create('Extraordinarily ', 'F3', 11, $italic),
        TextSpan::create('sophisticated typesetting algorithms — such as the ', 'F1', 11, $regular),
        TextSpan::create('Knuth–Liang ', 'F2', 11, $bold),
        TextSpan::create('method — decompose words like ', 'F1', 11, $regular),
        TextSpan::create('"typographical" ', 'F3', 11, $italic),
        TextSpan::create('and ', 'F1', 11, $regular),
        TextSpan::create('"uncharacteristically" ', 'F3', 11, $italic),
        TextSpan::create(
            'at their natural syllable boundaries, improving text density and visual consistency.',
            'F1',
            11,
            $regular,
        ),
    ];
}

function generate(): void
{
    $regular = Type1FontMetrics::helvetica();
    $bold = Type1FontMetrics::helveticaBold();
    $italic = Type1FontMetrics::timesItalic();
    $hyphenator = new TeXHyphenator(
        file(__DIR__ . '/../../../resources/hyphenation/en-US.tex', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
    );

    $pageW = 595.0;
    $gap = 18.0;
    $colWidth = 115.0;
    $marginL = ($pageW - 3 * $colWidth - 2 * $gap) / 2;

    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Rich Text Hyphenation')->author('phppdf'))
        ->page(
            static function (PdfPageBuilder $page) use ($regular, $bold, $italic, $hyphenator, $marginL, $gap, $colWidth): void {
                $page
                    ->size(...PdfPageSize::A4)
                    ->useType1Font('F1', 'Helvetica')
                    ->useType1Font('F2', 'Helvetica-Bold')
                    ->useType1Font('F3', 'Times-Italic')
                    ->content(
                        static function (PdfContentStreamBuilder $s) use ($regular, $bold, $italic, $hyphenator, $marginL, $gap, $colWidth): void {

                            $y = 790.0;
                            $col = static fn (int $n): float => $marginL + $n * ($colWidth + $gap);

                            $label = static function (string $text, float $x) use ($s, $y): void {
                                $s->beginText()->setFont('F2', 9)
                                    ->setTextMatrix(Matrix::translate($x, $y + 14))
                                    ->showText($text)->endText();
                            };

                            $spans = buildSpans($regular, $bold, $italic);

                        // ---- Without hyphenation ----
                            $label('Without hyphenation', $col(0));
                            $box = RichTextBox::create(
                                spans: $spans,
                                maxWidth: $colWidth,
                                align: TextAlign::Justify,
                            );
                            $s->drawRichTextBox($box, x: $col(0), y: $y);

                        // ---- With hyphenation (Justify) ----
                            $label('With hyphenation (Justify)', $col(1));
                            $box = RichTextBox::create(
                                spans: $spans,
                                maxWidth: $colWidth,
                                align: TextAlign::Justify,
                                hyphenator: $hyphenator,
                            );
                            $s->drawRichTextBox($box, x: $col(1), y: $y);

                        // ---- With hyphenation (Left) ----
                            $label('With hyphenation (Left)', $col(2));
                            $box = RichTextBox::create(
                                spans: $spans,
                                maxWidth: $colWidth,
                                align: TextAlign::Left,
                                hyphenator: $hyphenator,
                            );
                            $s->drawRichTextBox($box, x: $col(2), y: $y);
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
