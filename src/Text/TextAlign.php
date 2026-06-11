<?php

declare(strict_types=1);

namespace PhpPdf\Text;

/**
 * Horizontal alignment for TextBox lines.
 *
 * Left: lines start at the supplied x coordinate.
 * Center: each line is centred within the maxWidth.
 * Right: each line ends at x + maxWidth.
 */
enum TextAlign
{
    case Left;
    case Center;
    case Right;

    /**
     * Stretches every non-final line to fill the column width exactly by
     * distributing the remaining space evenly between words via PDF's Tw
     * (word spacing) operator. The last line of each paragraph and any
     * single-word line are rendered left-aligned, matching the convention
     * used in books and newspapers.
     */
    case Justify;
}
