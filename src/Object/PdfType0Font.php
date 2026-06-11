<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A Type 0 (composite) font dictionary supporting multi-byte character encodings.
 *
 * Type 0 fonts use CMap-based encoding and support the full Unicode range,
 * making them suitable for non-Latin scripts and documents requiring Unicode
 * text extraction. The encoding is fixed to Identity-H (horizontal writing,
 * CID as glyph index). The descendant CIDFont carries glyph metrics; the
 * ToUnicode CMap enables text selection and search in PDF viewers.
 */
final class PdfType0Font extends PdfDictionary
{
    public function __construct(string $baseFont, PdfIndirectReference $descendantFont, PdfIndirectReference $toUnicode)
    {
        parent::__construct([
            'BaseFont' => new PdfName($baseFont),
            'DescendantFonts' => new PdfArray([
                $descendantFont,
            ]),
            'Encoding' => new PdfName('Identity-H'),
            'Subtype' => new PdfName('Type0'),
            'ToUnicode' => $toUnicode,
            'Type' => new PdfName('Font'),
        ]);
    }
}
