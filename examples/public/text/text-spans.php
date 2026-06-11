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
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextSpan;

function generate(): void
{
    $regular = Type1FontMetrics::helvetica();
    $bold = Type1FontMetrics::helveticaBold();
    $italic = Type1FontMetrics::timesItalic();
    $courier = Type1FontMetrics::courier();

    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Text Spans')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($regular, $bold, $italic, $courier): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->useType1Font('F3', 'Times-Italic')
                ->useType1Font('F4', 'Courier')
                ->content(static function (PdfContentStreamBuilder $s) use ($regular, $bold, $italic, $courier): void {
                    $marginL = 72.0;
                    $pageW = 451.0;
                    $y = 780.0;

                    // Title
                    $s->beginText()->setFont('F2', 18)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText('Text Spans')->endText();
                    $y -= 32;

                    // =========================================================
                    // 1. Inline bold label
                    // =========================================================
                    $s->beginText()->setFont('F2', 10)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText('1. Inline bold label')->endText();
                    $y -= 16;

                    $box = RichTextBox::create([
                        TextSpan::create('Invoice number: ', 'F1', 11, $regular),
                        TextSpan::create('INV-2024-001', 'F2', 11, $bold),
                    ], maxWidth: $pageW);

                    $s->drawRichTextBox($box, x: $marginL, y: $y);
                    $y -= $box->getHeight() + 20;

                    // =========================================================
                    // 2. Mixed font sizes (title + subtitle on the same line)
                    // =========================================================
                    $s->beginText()->setFont('F2', 10)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText('2. Mixed font sizes')->endText();
                    $y -= 16;

                    $box = RichTextBox::create([
                        TextSpan::create('Total: ', 'F1', 10, $regular),
                        TextSpan::create('$1,234.56', 'F2', 16, $bold),
                        TextSpan::create(' (incl. tax)', 'F1', 10, $regular),
                    ], maxWidth: $pageW);

                    $s->drawRichTextBox($box, x: $marginL, y: $y);
                    $y -= $box->getHeight() + 20;

                    // =========================================================
                    // 3. Three distinct typefaces wrapping within a column
                    // =========================================================
                    $s->beginText()->setFont('F2', 10)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText('3. Three typefaces with word wrap')->endText();
                    $y -= 16;

                    $colW = 300.0;
                    $box = RichTextBox::create([
                        TextSpan::create('The quick ', 'F1', 11, $regular),
                        TextSpan::create('brown fox ', 'F2', 11, $bold),
                        TextSpan::create('jumps over the lazy dog. ', 'F3', 11, $italic),
                        TextSpan::create('The quick ', 'F1', 11, $regular),
                        TextSpan::create('brown fox ', 'F2', 11, $bold),
                        TextSpan::create('jumps over the lazy dog.', 'F3', 11, $italic),
                    ], maxWidth: $colW);

                    $s->saveGraphicsState()
                      ->fillColor(Color::fromHex('#f5f5f5'))
                      ->rectangle($marginL, $y - $box->getHeight() - 4, $colW, $box->getHeight() + 14)
                      ->fill()
                      ->restoreGraphicsState();

                    $s->drawRichTextBox($box, x: $marginL, y: $y);
                    $y -= $box->getHeight() + 20;

                    // =========================================================
                    // 4. Inline code snippet (Courier span inside prose)
                    // =========================================================
                    $s->beginText()->setFont('F2', 10)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText('4. Inline code in prose')->endText();
                    $y -= 16;

                    $box = RichTextBox::create([
                        TextSpan::create('Call ', 'F1', 11, $regular),
                        TextSpan::create('RichTextBox::create()', 'F4', 10, $courier),
                        TextSpan::create(' to compose spans, then pass the result to ', 'F1', 11, $regular),
                        TextSpan::create('drawRichTextBox()', 'F4', 10, $courier),
                        TextSpan::create(' to render it on the page.', 'F1', 11, $regular),
                    ], maxWidth: $pageW);

                    $s->drawRichTextBox($box, x: $marginL, y: $y);
                    $y -= $box->getHeight() + 20;

                    // =========================================================
                    // 5. Centered alignment
                    // =========================================================
                    $s->beginText()->setFont('F2', 10)
                      ->setTextMatrix(Matrix::translate($marginL, $y))
                      ->showText('5. Centered alignment')->endText();
                    $y -= 16;

                    $box = RichTextBox::create([
                        TextSpan::create('Centered ', 'F1', 12, $regular),
                        TextSpan::create('rich ', 'F2', 12, $bold),
                        TextSpan::create('text', 'F3', 12, $italic),
                    ], maxWidth: $pageW, align: TextAlign::Center);

                    $s->drawRichTextBox($box, x: $marginL, y: $y);
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
