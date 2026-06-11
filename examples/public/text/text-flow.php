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
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;
use PhpPdf\Text\TextFlow;

const CHAPTER = <<<'EOT'
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute
irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla
pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia
deserunt mollit anim id est laborum.

Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac
turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit
amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae
est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper
pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi.

Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis
tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis.
Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate
magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan
porttitor, facilisis luctus, metus. Phasellus ultrices nulla quis nibh.

Quisque a lectus. Donec consectetuer ligula vulputate sem tristique cursus. Nam
nulla quam, gravida non, commodo a, sodales sit amet, nisi. Nullam in massa.
Suspendisse vitae nisl sit amet augue bibendum aliquam. Vestibulum ante ipsum primis
in faucibus orci luctus et ultrices posuere cubilia curae; Proin vel ante a orci
tempus eleifend ut et magna. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Vivamus luctus urna sed urna ultricies ac tempor dui sagittis.
EOT;

function generate(): void
{
    $metrics = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    // A4 margins: 72 pt (1 in) on all sides.
    $marginL = 72.0;
    $pageW = 595;
    $pageH = 842;
    $textWidth = $pageW - 2 * $marginL; // 451 pt
    $topY = $pageH - $marginL; // 770 pt — first baseline
    $botMargin = $marginL; // 72 pt from bottom
    $maxHeight = $topY - $botMargin; // 698 pt of usable text height

    // Five chapters of text to guarantee several pages of overflow.
    $longText = implode("\n\n", array_fill(0, 5, CHAPTER));

    $box = TextBox::create(
        text: $longText,
        metrics: $metrics,
        fontSize: 11,
        maxWidth: $textWidth,
        lineHeight: 15,
        align: TextAlign::Justify,
    );

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Multi-page Text Flow')
                ->author('phppdf'),
        )
        // Global font for header — every page needs it before header runs.
        ->globalFont('Fh', 'Helvetica')
        ->globalFont('FhB', 'Helvetica-Bold')
        // Running header with page numbers.
        ->header(static function (PdfContentStreamBuilder $s, int $n, int $total): void {
            $s->beginText()
              ->setFont('FhB', 8)
              ->setTextMatrix(Matrix::translate(72, 822))
              ->showText('Multi-page Text Flow Demo')
              ->endText();

            $label = "Page $n of $total";
            $s->beginText()
              ->setFont('Fh', 8)
              ->setTextMatrix(Matrix::translate(595 - 72 - strlen($label) * 4.5, 822))
              ->showText($label)
              ->endText();

            // Hairline rule below header.
            $s->saveGraphicsState()
              ->setLineWidth(0.25)
              ->moveTo(72, 815)
              ->lineTo(523, 815)
              ->stroke()
              ->restoreGraphicsState();
        });

    // Pour the long text — TextFlow creates one page per screen-full automatically.
    TextFlow::pour(
        box: $box,
        document: $document,
        configure: static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica');
        },
        fontName: 'F1',
        x: $marginL,
        y: $topY,
        maxHeight: $maxHeight,
    );

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document->build());

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
