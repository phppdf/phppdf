<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The glyph advance widths array for a simple (single-byte) font.
 *
 * Each element is the advance width of the corresponding character code in
 * units of 1/1000 of the text size (glyph space). The array spans the range
 * from FirstChar to LastChar as declared in the font dictionary. Required for
 * correct text layout in embedded TrueType fonts.
 */
final class PdfFontWidths extends PdfArray
{
    /** @param list<int> $widths */
    public function __construct(array $widths)
    {
        parent::__construct(array_map(
            static fn (int $width): PdfInteger => new PdfInteger($width),
            $widths,
        ));
    }
}
