<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A TrueType font dictionary for simple (single-byte encoding) font embedding.
 *
 * Supports single-byte character encodings where each character code maps to
 * one glyph. $firstChar and $lastChar define the range of codes covered by
 * the $widths array. Requires a PdfFontDescriptor (which references the
 * embedded binary via PdfFontFile2) and a PdfFontWidths array. Use
 * PdfType0Font for Unicode or multi-byte text.
 */
final class PdfTrueTypeFont extends PdfDictionary
{
    public function __construct(
        string $baseFont,
        PdfIndirectReference $fontDescriptor,
        PdfFontWidths $widths,
        int $firstChar = 32,
        int $lastChar = 255,
        ?PdfObject $encoding = null,
    ) {
        parent::__construct([
            'BaseFont' => new PdfName($baseFont),
            'Encoding' => $encoding ?? new PdfName('WinAnsiEncoding'),
            'FirstChar' => new PdfInteger($firstChar),
            'FontDescriptor' => $fontDescriptor,
            'LastChar' => new PdfInteger($lastChar),
            'Subtype' => new PdfName('TrueType'),
            'Type' => new PdfName('Font'),
            'Widths' => $widths,
        ]);
    }
}
