<?php

declare(strict_types=1);

namespace PhpPdf\Text;

/**
 * A single item inside a ListBox: a marker string and its wrapped text lines.
 *
 * @internal Created by ListBox; not intended for direct construction.
 */
final class ListItem
{
    /**
     * @param string $marker     The bullet character or number label (e.g. '•', '1.').
     * @param array<string> $lines      Word-wrapped text lines for this item's body.
     * @param float $lineHeight Baseline-to-baseline distance in points.
     */
    public function __construct(
        public readonly string $marker,
        public readonly array $lines,
        public readonly float $lineHeight,
    ) {
    }

    /** Total vertical height consumed by this item in points. */
    public function getHeight(): float
    {
        return count($this->lines) * $this->lineHeight;
    }
}
