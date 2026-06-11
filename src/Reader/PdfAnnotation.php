<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

/**
 * One annotation read from a PDF page's /Annots array.
 *
 * Rect coordinates (x, y, width, height) are in PDF user units with the
 * origin at the bottom-left of the page (standard PDF coordinate system).
 * (x, y) is the bottom-left corner of the annotation rectangle.
 *
 * Color values are in the [0.0, 1.0] range per PDF channel.
 *
 * QuadPoints (for Highlight, Underline, StrikeOut, Squiggly) is the raw flat
 * array from the /QuadPoints entry: eight numbers per quad in the order
 * [x1 y1 x2 y2 x3 y3 x4 y4] representing the four corners of one text span.
 * Multiple quads are concatenated (useful for multi-line highlights).
 */
final class PdfAnnotation
{
    /**
     * @param array{float, float, float}|null $color         /C — annotation colour [R G B] in 0–1
     * @param array{float, float, float}|null $interiorColor /IC — fill colour for Square/Circle
     * @param list<float>|null $quadPoints    /QuadPoints flat array (Highlight etc.)
     */
    public function __construct(
        public readonly PdfAnnotationType $type,
        public readonly float $x,
        public readonly float $y,
        public readonly float $width,
        public readonly float $height,
        public readonly ?string $contents = null,
        public readonly ?string $title = null,
        public readonly ?array $color = null,
        public readonly ?array $interiorColor = null,
        public readonly ?array $quadPoints = null,
        public readonly ?string $uri = null,
        public readonly bool $open = false,
        public readonly float $borderWidth = 0.0,
    ) {
    }

    /** Returns true when this is a markup annotation spanning text (Highlight, Underline, StrikeOut, Squiggly). */
    public function isMarkup(): bool
    {
        return match ($this->type) {
            PdfAnnotationType::Highlight,
            PdfAnnotationType::Underline,
            PdfAnnotationType::StrikeOut,
            PdfAnnotationType::Squiggly => true,
            default => false,
        };
    }

    /** Returns true for Link annotations that carry a URI target. */
    public function isUriLink(): bool
    {
        return $this->type === PdfAnnotationType::Link && $this->uri !== null;
    }
}
