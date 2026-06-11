<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF encoding dictionary mapping character codes to glyph names.
 *
 * Extends a base encoding (e.g. WinAnsiEncoding) with a Differences array
 * that overrides specific character codes. Each difference entry is a starting
 * code followed by glyph names that replace codes from that position onward.
 * Use when a font's built-in encoding does not match the required character set.
 */
final class PdfEncoding extends PdfDictionary
{
    /** @param array<int, string> $differences */
    public function __construct(string $baseEncoding = 'WinAnsiEncoding', array $differences = [])
    {
        $differenceEntries = [];

        foreach ($differences as $code => $glyphName) {
            $differenceEntries[] = new PdfInteger($code);
            $differenceEntries[] = new PdfName($glyphName);
        }

        parent::__construct([
            'BaseEncoding' => new PdfName($baseEncoding),
            'Differences' => new PdfArray($differenceEntries),
            'Type' => new PdfName('Encoding'),
        ]);
    }
}
