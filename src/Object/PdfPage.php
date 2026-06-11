<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF page dictionary representing a single page in the document.
 *
 * Contains the minimum required entries: a reference to the parent Pages
 * node, a reference to the content stream, and the media box defining page
 * dimensions. Additional entries such as Resources, Annots, CropBox, and
 * Rotate can be added via set() after construction.
 */
final class PdfPage extends PdfDictionary
{
    public function __construct(PdfIndirectReference $parent, PdfIndirectReference $contents, PdfArray $mediaBox)
    {
        parent::__construct([
            'Contents' => $contents,
            'MediaBox' => $mediaBox,
            'Parent' => $parent,
            'Type' => new PdfName('Page'),
        ]);
    }
}
