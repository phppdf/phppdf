<?php

declare(strict_types=1);

namespace PhpPdf\Text;

use PhpPdf\Font\FontMetrics;

/**
 * A run of text with a consistent font, used to compose a RichTextBox.
 *
 * Each span carries its own font resource name, size, and metrics so that
 * multiple spans within a single text box can each use a different typeface
 * or size — for example, mixing regular and bold text on the same line.
 */
final class TextSpan
{
    /** Use TextSpan::create() to construct instances. */
    private function __construct(
        private readonly string $text,
        private readonly string $fontName,
        private readonly float $fontSize,
        private readonly FontMetrics $metrics,
    ) {
    }

    /**
     * Creates a span with the given text, font resource name, size, and metrics.
     *
     * $fontName must match a font registered on the page via useType1Font() or
     * useEmbeddedFont(). $fontSize is the size in points.
     */
    public static function create(string $text, string $fontName, float $fontSize, FontMetrics $metrics,): self
    {
        return new self($text, $fontName, $fontSize, $metrics);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** Returns the span's text content. */
    public function getText(): string
    {
        return $this->text;
    }

    /** Returns the PDF resource name of the font. */
    public function getFontName(): string
    {
        return $this->fontName;
    }

    /** Returns the font size in points. */
    public function getFontSize(): float
    {
        return $this->fontSize;
    }

    /** Returns the font metrics for glyph-width measurement. */
    public function getMetrics(): FontMetrics
    {
        return $this->metrics;
    }

    /** Returns the advance width of the span's text in points. */
    public function widthPt(): float
    {
        return $this->metrics->stringWidth($this->text) * $this->fontSize / 1000;
    }
}
