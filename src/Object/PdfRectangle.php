<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF rectangle expressed as a [left bottom right top] array.
 *
 * The coordinate order follows the PDF convention: lower-left corner
 * (left, bottom) first, then upper-right corner (right, top). Used for
 * page media boxes, crop boxes, annotation bounds, and font bounding boxes.
 */
final class PdfRectangle extends PdfArray
{
    public function __construct(int $left, int $bottom, int $right, int $top)
    {
        parent::__construct([
            new PdfInteger($left),
            new PdfInteger($bottom),
            new PdfInteger($right),
            new PdfInteger($top),
        ]);
    }
}
