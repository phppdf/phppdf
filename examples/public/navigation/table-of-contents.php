<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfOutlineBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Returns the baseline Y for every ToC row.
 *
 * Each entry is an array: [title, pageIndex, displayPage, level].
 * level 0 = chapter (bold, larger), level 1 = section (regular, indented).
 *
 * @param  list<array{string,int,int,int}> $entries
 * @return list<float>
 */
function computeRowYs(array $entries, float $startY): array
{
    $y  = $startY;
    $ys = [];

    foreach ($entries as $i => [, , , $level]) {
        if ($i > 0 && $level === 0) {
            $y -= 8.0; // extra breathing room before every chapter (except the first)
        }
        $ys[] = $y;
        $y   -= $level === 0 ? 24.0 : 20.0;
    }

    return $ys;
}

/**
 * Appends a content page (introduction, chapter, conclusion) to the builder.
 *
 * @param list<string> $bodyLines
 */
function addContentPage(
    PdfDocumentBuilder $builder,
    int $pageW,
    int $pageH,
    string $title,
    int $displayPage,
    array $bodyLines,
    Type1FontMetrics $helv,
): void {
    $builder->page(function (PdfPageBuilder $page) use (
        $pageW,
        $pageH,
        $title,
        $displayPage,
        $bodyLines,
        $helv,
    ): void {
        $page->size($pageW, $pageH)
             ->useType1Font('F1', 'Helvetica')
             ->useType1Font('F2', 'Helvetica-Bold')
             ->content(function (PdfContentStreamBuilder $s) use (
                 $pageW,
                 $pageH,
                 $title,
                 $displayPage,
                 $bodyLines,
                 $helv,
             ): void {
                 $ml        = 72.0;
                 $rightEdge = (float) $pageW - 72.0;

                 // Chapter heading
                 $s->beginText()
                   ->setFont('F2', 18)
                   ->setTextMatrix(Matrix::translate($ml, $pageH - 80.0))
                   ->showText($title)
                   ->endText();

                 // Separator line
                 $s->saveGraphicsState()
                   ->setLineWidth(0.5)
                   ->strokeColor(Color::gray(0.4))
                   ->moveTo($ml, $pageH - 92.0)
                   ->lineTo($rightEdge, $pageH - 92.0)
                   ->stroke()
                   ->restoreGraphicsState();

                 // Body text — blank lines produce visual paragraph breaks
                 $y = $pageH - 114.0;
                foreach ($bodyLines as $line) {
                    if ($line !== '') {
                        $s->beginText()
                          ->setFont('F1', 11)
                          ->setTextMatrix(Matrix::translate($ml, $y))
                          ->showText($line)
                          ->endText();
                    }
                    $y -= 17.0;
                }

                 // Centred page number at the bottom
                 $pageStr  = (string) $displayPage;
                 $numWidth = $helv->stringWidth($pageStr) * 9.0 / 1000.0;
                 $s->beginText()
                   ->setFont('F1', 9)
                   ->setTextMatrix(Matrix::translate($pageW / 2.0 - $numWidth / 2.0, 36.0))
                   ->showText($pageStr)
                   ->endText();
             });
    });
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;

    $helv  = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $ml        = 72.0;
    $rightEdge = (float) $pageW - 72.0;

    // Document structure.
    // Each entry: [title, pageIndex (0-based), displayPage (1-based), level].
    // Page 0 is the ToC; content pages start at index 1.
    // Sections that share a page with their chapter point to the same pageIndex.
    $entries = [
        ['Introduction',               1, 1, 0],
        ['Chapter 1: Getting Started', 2, 2, 0],
        ['1.1  Installation',          2, 2, 1],
        ['1.2  Configuration',         2, 2, 1],
        ['Chapter 2: Core Concepts',   3, 3, 0],
        ['2.1  Data Model',            3, 3, 1],
        ['Chapter 3: Advanced Topics', 4, 4, 0],
        ['Conclusion',                 5, 5, 0],
    ];

    // Pre-compute row positions so we can register link annotations and draw
    // text at the same Y coordinates without calculating twice.
    $tocStartY = (float) $pageH - 110.0;
    $rowYs     = computeRowYs($entries, $tocStartY);

    $builder = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Table of Contents Example')->author('phppdf'));

    // ── Page 0: Table of Contents ──────────────────────────────────────────
    $builder->page(function (PdfPageBuilder $page) use (
        $pageW,
        $pageH,
        $ml,
        $rightEdge,
        $entries,
        $rowYs,
        $helv,
        $helvB,
    ): void {
        $page->size($pageW, $pageH)
             ->useType1Font('F1', 'Helvetica')
             ->useType1Font('F2', 'Helvetica-Bold');

        // Register a clickable link annotation for each row.
        foreach ($entries as $i => [, $pageIndex, , $level]) {
            $rowH = $level === 0 ? 18.0 : 16.0;
            $page->addPageLink(
                x: $ml,
                y: $rowYs[$i] - 4.0,
                width: $rightEdge - $ml,
                height: $rowH,
                pageIndex: $pageIndex,
            );
        }

        $page->content(function (PdfContentStreamBuilder $s) use (
            $pageW,
            $pageH,
            $ml,
            $rightEdge,
            $entries,
            $rowYs,
            $helv,
            $helvB,
        ): void {
            // Page title
            $s->beginText()
              ->setFont('F2', 20)
              ->setTextMatrix(Matrix::translate($ml, (float) $pageH - 80.0))
              ->showText('Table of Contents')
              ->endText();

            // Separator line below the page title
            $s->saveGraphicsState()
              ->setLineWidth(0.5)
              ->strokeColor(Color::gray(0.4))
              ->moveTo($ml, (float) $pageH - 91.0)
              ->lineTo($rightEdge, (float) $pageH - 91.0)
              ->stroke()
              ->restoreGraphicsState();

            foreach ($entries as $i => [$title, , $displayPage, $level]) {
                $y        = $rowYs[$i];
                $indent   = $level === 0 ? 0.0 : 20.0;
                $fontSize = $level === 0 ? 12.0 : 11.0;
                $fontName = $level === 0 ? 'F2' : 'F1';
                $metrics  = $level === 0 ? $helvB : $helv;
                $titleX   = $ml + $indent;
                $pageStr  = (string) $displayPage;

                $titleW   = $metrics->stringWidth($title) * $fontSize / 1000.0;
                $pageNumW = $helv->stringWidth($pageStr) * 10.0 / 1000.0;

                // Entry title
                $s->beginText()
                  ->setFont($fontName, $fontSize)
                  ->setTextMatrix(Matrix::translate($titleX, $y))
                  ->showText($title)
                  ->endText();

                // Dotted leader line
                $leaderX1 = $titleX + $titleW + 5.0;
                $leaderX2 = $rightEdge - $pageNumW - 5.0;

                if ($leaderX2 > $leaderX1 + 8.0) {
                    $s->saveGraphicsState()
                      ->strokeColor(Color::gray(0.55))
                      ->setLineWidth(0.5)
                      ->setDashPattern([1.0, 3.0], 0.0)
                      ->moveTo($leaderX1, $y + 2.5)
                      ->lineTo($leaderX2, $y + 2.5)
                      ->stroke()
                      ->restoreGraphicsState();
                }

                // Page number, right-aligned
                $s->beginText()
                  ->setFont('F1', 10.0)
                  ->setTextMatrix(Matrix::translate($rightEdge - $pageNumW, $y))
                  ->showText($pageStr)
                  ->endText();
            }
        });
    });

    // ── Content pages ──────────────────────────────────────────────────────

    addContentPage($builder, $pageW, $pageH, 'Introduction', 1, [
        'This document demonstrates a Table of Contents generated with phppdf.',
        'Each entry in the ToC is a clickable link that navigates directly to',
        'the referenced page within the document.',
        '',
        'The ToC is built by pre-computing the Y position of each row, then',
        'registering addPageLink() annotations at those same coordinates.',
        'Dotted leader lines are drawn with setDashPattern() to connect each',
        'title to its page number.',
    ], $helv);

    addContentPage($builder, $pageW, $pageH, 'Chapter 1: Getting Started', 2, [
        'This chapter covers the first steps needed to begin working with the',
        'library. Topics include installation and environment configuration.',
        '',
        '1.1  Installation',
        'Install the package via Composer:',
        '  composer require vendor/phppdf',
        '',
        '1.2  Configuration',
        'Copy config/defaults.php to config/local.php and adjust the settings',
        'to match your environment before running the application.',
    ], $helv);

    addContentPage($builder, $pageW, $pageH, 'Chapter 2: Core Concepts', 3, [
        'Understanding the core concepts helps you build well-structured documents.',
        '',
        '2.1  Data Model',
        'The document model consists of pages, content streams, and resource',
        'dictionaries. Each page carries its own font and image resources.',
        'Content streams are append-only sequences of drawing operations.',
    ], $helv);

    addContentPage($builder, $pageW, $pageH, 'Chapter 3: Advanced Topics', 4, [
        'Advanced features include transparency, blend modes, embedded fonts,',
        'digital signatures, encryption, and PDF/A compliance.',
        '',
        'Each topic builds on the foundation established in earlier chapters.',
        'Refer to the respective example files for working demonstrations.',
    ], $helv);

    addContentPage($builder, $pageW, $pageH, 'Conclusion', 5, [
        'This document showed how to build a Table of Contents in phppdf.',
        '',
        'Key techniques used:',
        '  - Pre-computing row Y positions for a consistent layout',
        '  - addPageLink() for clickable ToC entries',
        '  - Dotted leader lines via setDashPattern()',
        '  - Type1FontMetrics::stringWidth() for precise text measurement',
        '  - PdfOutlineBuilder for the PDF bookmarks navigation panel',
    ], $helv);

    // ── PDF bookmarks (viewer outline panel) ──────────────────────────────
    $builder->outline(function (PdfOutlineBuilder $o): void {
        $o->item('Introduction', 1)
          ->item('Chapter 1: Getting Started', 2, function (PdfOutlineBuilder $ch): void {
              $ch->item('1.1  Installation', 2)
                 ->item('1.2  Configuration', 2);
          })
          ->item('Chapter 2: Core Concepts', 3, function (PdfOutlineBuilder $ch): void {
              $ch->item('2.1  Data Model', 3);
          })
          ->item('Chapter 3: Advanced Topics', 4)
          ->item('Conclusion', 5);
    });

    $document = $builder->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $output->getContent();
}

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
