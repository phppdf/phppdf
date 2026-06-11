<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Reader\PdfAnnotationExtractor;
use PhpPdf\Reader\PdfAnnotationType;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $path = '/tmp/phppdf-annotations-demo.pdf';

    // -------------------------------------------------------------------------
    // Step 1: Build a two-page PDF with a variety of annotation types.
    // -------------------------------------------------------------------------

    $pH = PdfPageSize::A4[1]; // 842 pt

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Annotation Extraction Demo')
                ->author('phppdf'),
        )

        // ── Page 1: markup + shape annotations ──────────────────────────────
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')

                // Sticky notes
                ->addTextAnnotation(
                    x: 370,
                    y: $pH - 202,
                    text: 'This sentence is highlighted in yellow.',
                    title: 'Alice',
                    color: Color::fromHex('#ffcc00'),
                )
                ->addTextAnnotation(
                    x: 270,
                    y: $pH - 240,
                    text: 'Mark for follow-up.',
                    title: 'Bob',
                    open: true,
                    color: Color::fromHex('#00ccff'),
                )

                // Highlights
                ->addHighlightAnnotation(
                    x: 72,
                    y: $pH - 204,
                    width: 290,
                    height: 14,
                    color: Color::fromHex('#ffff00'),
                )
                ->addHighlightAnnotation(
                    x: 72,
                    y: $pH - 242,
                    width: 195,
                    height: 14,
                    color: Color::fromHex('#aaffaa'),
                )

                // Underlines
                ->addUnderlineAnnotation(
                    x: 72,
                    y: $pH - 280,
                    width: 240,
                    height: 14,
                    color: Color::fromHex('#cc0000'),
                )
                ->addUnderlineAnnotation(
                    x: 72,
                    y: $pH - 318,
                    width: 165,
                    height: 14,
                    color: Color::fromHex('#0033cc'),
                )

                // Shapes
                ->addSquareAnnotation(
                    x: 72,
                    y: $pH - 430,
                    width: 150,
                    height: 55,
                    borderColor: Color::fromHex('#cc0000'),
                    borderWidth: 2.0,
                )
                ->addCircleAnnotation(
                    x: 250,
                    y: $pH - 430,
                    width: 120,
                    height: 55,
                    borderColor: Color::fromHex('#009900'),
                    fillColor: Color::fromHex('#ccffcc'),
                    borderWidth: 1.5,
                )

                // URI link
                ->addUriLink(
                    x: 72,
                    y: $pH - 540,
                    width: 195,
                    height: 14,
                    uri: 'https://github.com/phppdf/phppdf',
                )

                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('FB', 16)
                      ->setTextMatrix(Matrix::translate(72, $pH - 72))
                      ->showText('Page 1 — Markup & Shape Annotations')->endText();

                    $s->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate(72, $pH - 190))
                      ->showText('This sentence is highlighted in yellow.')
                      ->setTextMatrix(Matrix::translate(72, $pH - 228))
                      ->showText('This sentence is highlighted in green.')
                      ->setTextMatrix(Matrix::translate(72, $pH - 266))
                      ->showText('This sentence has a red underline.')
                      ->setTextMatrix(Matrix::translate(72, $pH - 304))
                      ->showText('This sentence has a blue underline.')
                      ->endText();

                    $s->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate(72, $pH - 380))
                      ->showText('Red Square    Green-filled Circle')
                      ->endText();

                    $s->fillColor(Color::rgb(0.0, 0.2, 0.8))
                      ->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate(72, $pH - 528))
                      ->showText('github.com/phppdf/phppdf')->endText();
                });
        })

        // ── Page 2: more links, including an internal page link ──────────────
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')

                ->addUriLink(
                    x: 72,
                    y: $pH - 200,
                    width: 280,
                    height: 14,
                    uri: 'https://www.php.net/manual/en/book.pdf.php',
                )
                ->addPageLink(
                    x: 72,
                    y: $pH - 250,
                    width: 170,
                    height: 14,
                    pageIndex: 0,
                )
                ->addHighlightAnnotation(
                    x: 72,
                    y: $pH - 304,
                    width: 340,
                    height: 14,
                    color: Color::fromHex('#ffe0a0'),
                )
                ->addTextAnnotation(
                    x: 340,
                    y: $pH - 306,
                    text: 'Cross-page note from page 2.',
                    title: 'Charlie',
                )

                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('FB', 16)
                      ->setTextMatrix(Matrix::translate(72, $pH - 72))
                      ->showText('Page 2 — Links & Cross-page Annotations')->endText();

                    $s->fillColor(Color::rgb(0.0, 0.2, 0.8))
                      ->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate(72, $pH - 188))
                      ->showText('php.net/manual/en/book.pdf.php  (URI link)')
                      ->setTextMatrix(Matrix::translate(72, $pH - 238))
                      ->showText('← Back to page 1  (internal GoTo link)')
                      ->endText();

                    $s->fillColor(Color::rgb(0.1, 0.1, 0.1))
                      ->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate(72, $pH - 292))
                      ->showText('This highlighted sentence spans a sticky note on the right.')
                      ->endText();
                });
        })
        ->build();

    (new PdfDocumentSerializer(new PdfFileOutput($path)))->writeDocument($document);
    echo "Written: {$path}" . PHP_EOL . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 2: Read back and extract all annotations.
    // -------------------------------------------------------------------------

    $doc = PdfDocumentReader::open($path);
    $extractor = new PdfAnnotationExtractor($doc);
    $byPage = $extractor->getAllAnnotations();

    $typeLabel = static fn (PdfAnnotationType $t): string => match ($t) {
        PdfAnnotationType::Text => 'Text (sticky note)',
        PdfAnnotationType::Link => 'Link',
        PdfAnnotationType::Highlight => 'Highlight',
        PdfAnnotationType::Underline => 'Underline',
        PdfAnnotationType::StrikeOut => 'StrikeOut',
        PdfAnnotationType::Squiggly => 'Squiggly',
        PdfAnnotationType::Square => 'Square',
        PdfAnnotationType::Circle => 'Circle',
        PdfAnnotationType::Unknown => 'Unknown',
    };

    $colorStr = static fn (?array $c): string => $c === null
            ? 'none'
            : sprintf('rgb(%.2f, %.2f, %.2f)', $c[0], $c[1], $c[2]);

    $total = 0;

    foreach ($byPage as $pageIdx => $annotations) {
        $total += count($annotations);
        echo sprintf("Page %d — %d annotation(s):\n", $pageIdx + 1, count($annotations));
        echo str_repeat('─', 60) . PHP_EOL;

        foreach ($annotations as $ann) {
            echo sprintf(
                "  %-22s  rect=(%.0f, %.0f, %.0f×%.0f)\n",
                $typeLabel($ann->type),
                $ann->x,
                $ann->y,
                $ann->width,
                $ann->height,
            );

            if ($ann->contents !== null) {
                echo "    contents : \"{$ann->contents}\"" . PHP_EOL;
            }

            if ($ann->title !== null) {
                echo "    title    : \"{$ann->title}\"" . PHP_EOL;
            }

            if ($ann->color !== null) {
                echo '    color    : ' . $colorStr($ann->color) . PHP_EOL;
            }

            if ($ann->interiorColor !== null) {
                echo '    fill     : ' . $colorStr($ann->interiorColor) . PHP_EOL;
            }

            if ($ann->borderWidth > 0.0) {
                echo "    border-w : {$ann->borderWidth}" . PHP_EOL;
            }

            if ($ann->uri !== null) {
                echo "    uri      : {$ann->uri}" . PHP_EOL;
            }

            if ($ann->open) {
                echo '    open     : true' . PHP_EOL;
            }

            if ($ann->quadPoints !== null) {
                $nQuads = count($ann->quadPoints) / 8;
                echo "    quads    : {$nQuads} quad(s)" . PHP_EOL;
            }

            if ($ann->type === PdfAnnotationType::Link && $ann->uri === null) {
                echo '    action   : GoTo (internal)' . PHP_EOL;
            }

            echo PHP_EOL;
        }
    }

    echo str_repeat('─', 60) . PHP_EOL;
    echo "Total: {$total} annotation(s) across {$doc->getPageCount()} page(s)." . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 3: Filter demo — only highlights and sticky notes.
    // -------------------------------------------------------------------------

    echo PHP_EOL . 'Highlights and sticky notes only:' . PHP_EOL;

    foreach ($byPage as $pageIdx => $annotations) {
        foreach ($annotations as $ann) {
            if ($ann->type !== PdfAnnotationType::Highlight && $ann->type !== PdfAnnotationType::Text) {
                continue;
            }

            $detail = $ann->type === PdfAnnotationType::Text
                ? " \"{$ann->contents}\" (by {$ann->title})"
                : ' color=' . $colorStr($ann->color);
            echo sprintf("  p%d  %-10s%s\n", $pageIdx + 1, $ann->type->value, $detail);
        }
    }
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
