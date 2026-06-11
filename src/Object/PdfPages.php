<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The PDF page tree node that lists the pages in the document.
 *
 * Holds an array of indirect references to PdfPage dictionaries. The Count
 * entry is derived automatically from the length of the $kids array. For
 * large documents a multi-level page tree improves random-access performance,
 * but this implementation uses a single flat node.
 */
final class PdfPages extends PdfDictionary
{
    /** @param list<\PhpPdf\Object\PdfIndirectReference> $kids */
    public function __construct(array $kids)
    {
        parent::__construct([
            'Count' => new PdfInteger(count($kids)),
            'Kids' => new PdfArray($kids),
            'Type' => new PdfName('Pages'),
        ]);
    }
}
