<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A Type 1 (PostScript) font dictionary referencing one of the standard 14 fonts.
 *
 * The 14 standard base fonts (Helvetica, Times-Roman, Courier, Symbol, etc.)
 * do not require embedding — every PDF viewer must supply them. Use this for
 * quick access to those fonts. For custom or non-Latin fonts, use
 * PdfTrueTypeFont or PdfType0Font instead.
 */
final class PdfType1Font extends PdfDictionary
{
    public function __construct(string $baseFont = 'Helvetica')
    {
        parent::__construct([
            'BaseFont' => new PdfName($baseFont),
            'Subtype' => new PdfName('Type1'),
            'Type' => new PdfName('Font'),
        ]);
    }
}
