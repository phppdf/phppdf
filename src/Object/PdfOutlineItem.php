<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A single entry in the PDF document outline (a bookmark).
 *
 * Stores a display title and a destination. The linked-list entries (Parent,
 * Prev, Next, First, Last, Count) are added via set() once all sibling and
 * child items have been registered in the object registry.
 */
final class PdfOutlineItem extends PdfDictionary
{
    public function __construct(string $title, PdfDestination $destination)
    {
        parent::__construct([
            'Dest' => $destination,
            'Title' => new PdfString($title),
        ]);
    }
}
