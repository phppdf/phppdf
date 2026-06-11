<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The PDF document catalog, the root object of every PDF document.
 *
 * Referenced from the trailer dictionary and serves as the entry point for
 * the document object graph. Always carries Type=Catalog and a reference to
 * the page tree root. Optional entries — outlines, AcroForm, metadata, names
 * — can be added via set() after construction.
 */
final class PdfCatalog extends PdfDictionary
{
    public function __construct(PdfIndirectReference $pages)
    {
        parent::__construct([
            'Pages' => $pages,
            'Type' => new PdfName('Catalog'),
        ]);
    }
}
