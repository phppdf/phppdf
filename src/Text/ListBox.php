<?php

declare(strict_types=1);

namespace PhpPdf\Text;

use PhpPdf\Font\FontMetrics;

/**
 * A laid-out list of items ready for rendering with
 * PdfContentStreamBuilder::drawListBox().
 *
 * Each item is word-wrapped independently within the available text width
 * (maxWidth − indent). Continuation lines of a multi-line item are indented
 * to the same column as the first line.
 *
 * Usage — bullet list:
 *
 *   $metrics = Type1FontMetrics::helvetica();
 *   $list = ListBox::bullet(
 *       items:    ['First item', 'Second item', 'A longer item that wraps.'],
 *       metrics:  $metrics,
 *       fontSize: 11,
 *       maxWidth: 300,
 *   );
 *   $stream->drawListBox($list, fontName: 'F1', x: 72, y: 700);
 *
 * Usage — numbered list:
 *
 *   $list = ListBox::numbered(
 *       items:    ['First item', 'Second item', 'Third item'],
 *       metrics:  $metrics,
 *       fontSize: 11,
 *       maxWidth: 300,
 *   );
 */
final class ListBox
{
    /** @var list<ListItem> */
    private array $items;

    /** @param list<ListItem> $items */
    private function __construct(
        private readonly float $indent,
        private readonly float $fontSize,
        private readonly float $lineHeight,
        private readonly float $itemSpacing,
        array $items,
    ) {
        $this->items = $items;
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    /**
     * Creates a bullet list.
     *
     * @param list<string> $items       Item text strings (UTF-8).
     * @param float        $maxWidth    Total column width in points (marker + indent + text).
     * @param string       $bullet      Bullet character; defaults to a Unicode bullet '•'.
     * @param float        $indent      Distance from the list x to where body text begins.
     *                                  Defaults to twice the font size.
     * @param float        $lineHeight  Baseline-to-baseline distance. Defaults to fontSize × 1.2.
     * @param float        $itemSpacing Extra vertical gap inserted between items (points).
     */
    public static function bullet(
        array $items,
        FontMetrics $metrics,
        float $fontSize,
        float $maxWidth,
        string $bullet = '•',
        float $indent = 0.0,
        float $lineHeight = 0.0,
        float $itemSpacing = 0.0,
    ): self {
        $lineHeight = $lineHeight > 0 ? $lineHeight : $fontSize * 1.2;
        $indent     = $indent     > 0 ? $indent     : $fontSize * 2.0;
        $textWidth  = $maxWidth - $indent;

        $listItems = [];
        foreach ($items as $text) {
            $lines      = self::wrap((string) $text, $metrics, $fontSize, $textWidth);
            $listItems[] = new ListItem($bullet, $lines, $lineHeight);
        }

        return new self($indent, $fontSize, $lineHeight, $itemSpacing, $listItems);
    }

    /**
     * Creates a numbered list.
     *
     * @param list<string> $items        Item text strings (UTF-8).
     * @param float        $maxWidth     Total column width in points.
     * @param float        $indent       Distance from the list x to where body text begins.
     *                                   When 0, it is computed automatically from the width of
     *                                   the widest marker (e.g. "10." for a 10-item list).
     * @param float        $lineHeight   Baseline-to-baseline distance. Defaults to fontSize × 1.2.
     * @param float        $itemSpacing  Extra vertical gap inserted between items (points).
     * @param int          $startAt      First item number (default 1).
     * @param string       $numberFormat sprintf format for the marker; %d is the item number.
     */
    public static function numbered(
        array $items,
        FontMetrics $metrics,
        float $fontSize,
        float $maxWidth,
        float $indent = 0.0,
        float $lineHeight = 0.0,
        float $itemSpacing = 0.0,
        int $startAt = 1,
        string $numberFormat = '%d.',
    ): self {
        $lineHeight = $lineHeight > 0 ? $lineHeight : $fontSize * 1.2;
        $count      = count($items);

        if ($indent <= 0.0) {
            // Auto-indent: measure the widest marker and add a small gap.
            $widestMarker = sprintf($numberFormat, $startAt + $count - 1);
            $markerPt     = $metrics->stringWidth($widestMarker) * $fontSize / 1000;
            $indent       = $markerPt + $fontSize * 0.6;
        }

        $textWidth  = $maxWidth - $indent;
        $listItems  = [];

        foreach ($items as $i => $text) {
            $marker      = sprintf($numberFormat, $startAt + $i);
            $lines       = self::wrap((string) $text, $metrics, $fontSize, $textWidth);
            $listItems[] = new ListItem($marker, $lines, $lineHeight);
        }

        return new self($indent, $fontSize, $lineHeight, $itemSpacing, $listItems);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** @return list<ListItem> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** Distance in points from the list's x position to the text body column. */
    public function getIndent(): float
    {
        return $this->indent;
    }

    public function getFontSize(): float
    {
        return $this->fontSize;
    }
    public function getLineHeight(): float
    {
        return $this->lineHeight;
    }
    public function getItemSpacing(): float
    {
        return $this->itemSpacing;
    }

    /** Total height in points consumed by all items including inter-item spacing. */
    public function getHeight(): float
    {
        $h = 0.0;
        foreach ($this->items as $i => $item) {
            $h += $item->getHeight();
            if ($i < count($this->items) - 1) {
                $h += $this->itemSpacing;
            }
        }
        return $h;
    }

    // -------------------------------------------------------------------------
    // Internal word-wrap
    // -------------------------------------------------------------------------

    /**
     * Greedy word-wrap, matching the algorithm used by TextBox.
     *
     * @return list<string>
     */
    private static function wrap(string $text, FontMetrics $metrics, float $fontSize, float $maxWidth): array
    {
        $maxUnits   = $maxWidth * 1000 / $fontSize;
        $spaceWidth = $metrics->charWidth(32);
        $words      = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines      = [];
        $current    = '';
        $currentW   = 0.0;

        foreach ($words as $word) {
            $wordW = $metrics->stringWidth($word);

            if ($current === '') {
                $current  = $word;
                $currentW = $wordW;
            } elseif ($currentW + $spaceWidth + $wordW <= $maxUnits) {
                $current  .= ' ' . $word;
                $currentW += $spaceWidth + $wordW;
            } else {
                $lines[]  = $current;
                $current  = $word;
                $currentW = $wordW;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }
}
