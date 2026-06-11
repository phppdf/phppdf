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
    // Page height in points (A4 = 842 pt)
    $pageH = PdfPageSize::A4[1];

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Annotations Example')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($pageH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')

                // ── Text (sticky-note) annotations ───────────────────────────
                // Icons are placed at the right edge of each highlighted line
                // (x just past the highlight width) at the same y as the text.
                // Yellow highlight text baseline: $pageH - 198; icon beside it.
                ->addTextAnnotation(
                    x: 378,
                    y: $pageH - 200,
                    text: 'This sentence is highlighted in yellow.',
                    title: 'Reviewer',
                    color: Color::fromHex('#ffcc00'),
                )
                // Green highlight text baseline: $pageH - 236; icon beside it.
                ->addTextAnnotation(
                    x: 278,
                    y: $pageH - 238,
                    text: 'Highlight annotations mark text passages for review.',
                    title: 'Editor',
                    open: true,
                    color: Color::fromHex('#00ccff'),
                )

                // ── Highlight annotations ─────────────────────────────────────
                // y is 2 pt below the text baseline so descenders are covered;
                // height of 13 pt reaches the cap-height of 11 pt Helvetica.
                // Text "highlighted in yellow" baseline: $pageH - 198
                ->addHighlightAnnotation(
                    x: 72,
                    y: $pageH - 200,
                    width: 300,
                    height: 13,
                    color: Color::fromHex('#ffff00'),
                )
                // Text "highlighted in green" baseline: $pageH - 236
                ->addHighlightAnnotation(
                    x: 72,
                    y: $pageH - 238,
                    width: 200,
                    height: 13,
                    color: Color::fromHex('#aaffaa'),
                )

                // ── Underline annotations ─────────────────────────────────────
                // Text "red underline" baseline: $pageH - 273
                ->addUnderlineAnnotation(
                    x: 72,
                    y: $pageH - 275,
                    width: 250,
                    height: 13,
                    color: Color::fromHex('#cc0000'),
                )
                // Text "blue underline" baseline: $pageH - 311
                ->addUnderlineAnnotation(
                    x: 72,
                    y: $pageH - 313,
                    width: 180,
                    height: 13,
                    color: Color::fromHex('#0033cc'),
                )

                // ── Square annotations ────────────────────────────────────────
                ->addSquareAnnotation(
                    x: 72,
                    y: $pageH - 430,
                    width: 160,
                    height: 60,
                    borderColor: Color::fromHex('#cc0000'),
                    borderWidth: 2.0,
                )
                ->addSquareAnnotation(
                    x: 260,
                    y: $pageH - 430,
                    width: 160,
                    height: 60,
                    borderColor: Color::fromHex('#0033cc'),
                    fillColor: Color::fromHex('#ddeeff'),
                    borderWidth: 1.5,
                )

                // ── Circle annotations ────────────────────────────────────────
                ->addCircleAnnotation(
                    x: 72,
                    y: $pageH - 540,
                    width: 120,
                    height: 80,
                    borderColor: Color::fromHex('#009900'),
                    borderWidth: 2.5,
                )
                ->addCircleAnnotation(
                    x: 220,
                    y: $pageH - 540,
                    width: 120,
                    height: 80,
                    borderColor: Color::fromHex('#cc6600'),
                    fillColor: Color::fromHex('#fff0cc'),
                    borderWidth: 1.5,
                )

                // ── URI link annotation (existing API) ────────────────────────
                ->addUriLink(
                    x: 72,
                    y: $pageH - 620,
                    width: 180,
                    height: 14,
                    uri: 'https://github.com/phppdf/phppdf',
                )

                ->content(static function (PdfContentStreamBuilder $s) use ($pageH): void {
                    $s
                        // Title
                        ->beginText()
                        ->setFont('FB', 18)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 72))
                        ->showText('PDF Annotation Types')
                        ->endText()

                        // Section: Text annotations
                        ->beginText()
                        ->setFont('FB', 12)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 108))
                        ->showText('Text (sticky note) annotations')
                        ->setFont('F1', 11)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 126))
                        ->showText('Click the icon in the margin to read the attached comment.')
                        ->endText()

                        // Separator line
                        ->strokeColor(Color::rgb(0.8, 0.8, 0.8))
                        ->moveTo(72, $pageH - 140)->lineTo(523, $pageH - 140)->stroke()

                        // Section: Highlight
                        ->beginText()
                        ->setFont('FB', 12)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 162))
                        ->showText('Highlight & Underline annotations')
                        ->setFont('F1', 11)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 198))
                        ->showText('This sentence is highlighted in yellow.')
                        ->setTextMatrix(Matrix::translate(72, $pageH - 236))
                        ->showText('This sentence is highlighted in green.')
                        ->setTextMatrix(Matrix::translate(72, $pageH - 273))
                        ->showText('This sentence has a red underline.')
                        ->setTextMatrix(Matrix::translate(72, $pageH - 311))
                        ->showText('This sentence has a blue underline.')
                        ->endText()

                        ->strokeColor(Color::rgb(0.8, 0.8, 0.8))
                        ->moveTo(72, $pageH - 340)->lineTo(523, $pageH - 340)->stroke()

                        // Section: Square / Circle
                        ->beginText()
                        ->setFont('FB', 12)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 362))
                        ->showText('Square & Circle annotations')
                        ->setFont('F1', 11)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 380))
                        ->showText('Red border         Blue border + fill')
                        ->setTextMatrix(Matrix::translate(72, $pageH - 455))
                        ->showText('Green border       Orange border + fill')
                        ->endText()

                        ->strokeColor(Color::rgb(0.8, 0.8, 0.8))
                        ->moveTo(72, $pageH - 560)->lineTo(523, $pageH - 560)->stroke()

                        // Section: Link
                        ->beginText()
                        ->setFont('FB', 12)
                        ->setTextMatrix(Matrix::translate(72, $pageH - 582))
                        ->showText('URI link annotation')
                        ->setFont('F1', 11)
                        ->fillColor(Color::rgb(0.0, 0.2, 0.8))
                        ->setTextMatrix(Matrix::translate(72, $pageH - 608))
                        ->showText('github.com/phppdf/phppdf')
                        ->endText();
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
