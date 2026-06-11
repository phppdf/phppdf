<?php

declare(strict_types=1);

namespace PhpPdf\Font;

/**
 * Provides glyph advance widths for a specific font face, enabling text
 * measurement for word-wrapping and text-box layout.
 *
 * All widths are returned in glyph-space units (1/1000 of the text size unit),
 * matching the convention used in PDF font dictionaries and AFM files.
 * Multiply by (fontSize / 1000) to convert to page-coordinate points.
 */
interface FontMetrics
{
    /**
     * Returns the advance width of the glyph for the given Unicode code point.
     *
     * Returns a reasonable fallback width (typically the average character
     * width for the font) for unmapped code points.
     */
    public function charWidth(int $codePoint): float;

    /**
     * Returns the total advance width of a UTF-8 string.
     *
     * This is the sum of all character widths with no additional spacing.
     * The result is in glyph-space units; multiply by (fontSize / 1000) to
     * convert to points.
     */
    public function stringWidth(string $text): float;
}
