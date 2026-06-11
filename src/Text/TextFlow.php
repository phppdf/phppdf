<?php

declare(strict_types=1);

namespace PhpPdf\Text;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;

/**
 * Multi-page text flow engine.
 *
 * Distributes a TextBox across as many pages as its content requires, calling
 * the caller-supplied $configure callback to set up each new page (size, fonts,
 * any additional drawings). The text cursor wraps automatically at $maxHeight
 * and a fresh page is appended to $document for each overflow.
 *
 * Usage:
 *
 *   $box = TextBox::create($longText, $metrics, fontSize: 11, maxWidth: 451, lineHeight: 14);
 *
 *   TextFlow::pour(
 *       box: $box,
 *       document: $document,
 *       configure: fn(PdfPageBuilder $p) => $p
 *           ->size(...PdfPageSize::A4)
 *           ->useType1Font('F1', 'Helvetica'),
 *       fontName: 'F1',
 *       x: 72.0,
 *       y: 770.0,
 *       maxHeight: 698.0,
 *   );
 *
 * Pages are appended in order. Call pour() before PdfDocumentBuilder::build().
 * If the text is empty no pages are added.
 */
final class TextFlow
{
    /**
     * Flows $box across one or more pages of $document.
     *
     * @param callable(\PhpPdf\Builder\PdfPageBuilder): void $configure
     *   Receives each new page builder. Set the page size, register fonts, add
     *   borders, watermarks, or any other per-page drawing here. The text
     *   content is appended automatically after this callback runs — do not
     *   call content() inside $configure for the body text.
     * @param string $fontName  Resource name matching a font registered inside $configure.
     * @param float $x         Left edge of the text column in points.
     * @param float $y         Baseline of the first line on each page in points
     *                                  (PDF y-axis: origin at bottom-left, increasing upward).
     * @param float $maxHeight Maximum vertical space for text per page in points.
     *                          Lines that would exceed this are pushed to the next page.
     */
    public static function pour(
        TextBox $box,
        PdfDocumentBuilder $document,
        callable $configure,
        string $fontName,
        float $x,
        float $y,
        float $maxHeight,
    ): void {
        $remaining = $box;

        while ($remaining->getLines() !== []) {
            $pageLineCount = count($remaining->linesFor($maxHeight));

            // @codeCoverageIgnoreStart
            if ($pageLineCount === 0) {
                break; // linesFor() always returns ≥1 when getLines() !== []
            }

            // @codeCoverageIgnoreEnd

            $slice = $remaining;

            $document->page(
                static function (PdfPageBuilder $page) use ($configure, $slice, $fontName, $x, $y, $maxHeight): void {
                    $configure($page);
                    $page->content(
                        static function (PdfContentStreamBuilder $stream) use (
                            $slice,
                            $fontName,
                            $x,
                            $y,
                            $maxHeight,
                        ): void {
                            $stream->drawTextBox($slice, $fontName, $x, $y, $maxHeight); // @codeCoverageIgnore
                        },
                    );
                },
            );

            $remaining = $remaining->skip($pageLineCount);
        }
    }
}
