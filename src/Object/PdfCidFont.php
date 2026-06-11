<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A CIDFontType2 dictionary: the TrueType descendant of a Type 0 font.
 *
 * Holds the glyph metrics for a composite font. Always paired with a
 * PdfType0Font (its parent) and a PdfFontDescriptor (which references the
 * embedded font binary). The CIDSystemInfo describes the character collection;
 * for arbitrary Unicode fonts this is typically Adobe-Identity.
 */
final class PdfCidFont extends PdfDictionary
{
    public function __construct(string $baseFont, PdfCidSystemInfo $systemInfo, PdfIndirectReference $fontDescriptor)
    {
        parent::__construct([
            'BaseFont' => new PdfName($baseFont),
            'CIDSystemInfo' => $systemInfo,
            'FontDescriptor' => $fontDescriptor,
            'Subtype' => new PdfName('CIDFontType2'),
            'Type' => new PdfName('Font'),
        ]);
    }
}
