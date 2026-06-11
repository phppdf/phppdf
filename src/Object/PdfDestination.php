<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF explicit destination: a [page fitType] array.
 *
 * Defines where the viewer should navigate: which page and how it should be
 * displayed. The default fit type 'Fit' scales the page to fill the window.
 * Other types include 'FitH' (fit width), 'XYZ' (specific coordinates and
 * zoom), and 'FitR' (fit a rectangle). Used by PdfGoToAction and PdfOutlineItem.
 */
final class PdfDestination extends PdfArray
{
    public function __construct(PdfIndirectReference $page, string $type = 'Fit')
    {
        parent::__construct([
            $page,
            new PdfName($type),
        ]);
    }
}
