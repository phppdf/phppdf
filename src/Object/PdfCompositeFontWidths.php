<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The per-CID glyph advance widths array for a CIDFont (composite font).
 *
 * Encodes the W (Widths) array used by CID fonts, where each entry is a
 * starting CID followed by an array of widths for that CID. This class uses
 * the one-CID-per-entry form for simplicity. Widths are in glyph-space units
 * (1/1000 of the text size).
 */
final class PdfCompositeFontWidths extends PdfArray
{
    /** @param array<int, int> $widths */
    public function __construct(array $widths)
    {
        $entries = [];

        foreach ($widths as $cid => $width) {
            $entries[] = new PdfInteger($cid);
            $entries[] = new PdfArray([
                new PdfInteger($width),
            ]);
        }

        parent::__construct($entries);
    }
}
