<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A FontDescriptor dictionary containing metrics and embedding information for a font.
 *
 * Required for all embedded fonts. The $flags bitmask encodes font
 * characteristics (bit 1 = FixedPitch, bit 3 = Symbolic, bit 6 = Nonsymbolic,
 * bit 7 = Italic, per PDF spec Table 123). Metrics ($ascent, $descent,
 * $capHeight, $stemV) are in glyph-space units (1/1000 of the text size).
 * The FontFile2 entry referencing the embedded binary is added separately
 * via set().
 */
final class PdfFontDescriptor extends PdfDictionary
{
    public function __construct(
        string $fontName,
        int $flags,
        int $italicAngle,
        int $ascent,
        int $descent,
        int $capHeight,
        PdfArray $fontBBox,
        int $stemV = 80,
    ) {
        parent::__construct([
            'Ascent' => new PdfInteger($ascent),
            'CapHeight' => new PdfInteger($capHeight),
            'Descent' => new PdfInteger($descent),
            'Flags' => new PdfInteger($flags),
            'FontBBox' => $fontBBox,
            'FontName' => new PdfName($fontName),
            'ItalicAngle' => new PdfInteger($italicAngle),
            'StemV' => new PdfInteger($stemV),
            'Type' => new PdfName('FontDescriptor'),
        ]);
    }
}
