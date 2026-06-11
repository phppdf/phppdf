<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

/**
 * Standard PDF page size presets in points (1 pt = 1/72 inch).
 *
 * Each constant is a [width, height] pair in portrait orientation.
 * Use with PdfPageBuilder::size() via named-argument spread:
 *
 *   $page->size(...PdfPageSize::A4);
 */
final class PdfPageSize
{
    /** ISO A0: 841 × 1189 mm */
    public const array A0 = [2384, 3371];

    /** ISO A1: 594 × 841 mm */
    public const array A1 = [1684, 2384];

    /** ISO A2: 420 × 594 mm */
    public const array A2 = [1191, 1684];

    /** ISO A3: 297 × 420 mm */
    public const array A3 = [842, 1191];

    /** ISO A4: 210 × 297 mm */
    public const array A4 = [595, 842];

    /** ISO A5: 148 × 210 mm */
    public const array A5 = [420, 595];

    /** US Letter: 8.5 × 11 in */
    public const array LETTER = [612, 792];

    /** US Legal: 8.5 × 14 in */
    public const array LEGAL = [612, 1008];

    /** US Tabloid / Ledger: 11 × 17 in */
    public const array TABLOID = [792, 1224];
}
