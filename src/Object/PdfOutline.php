<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The PDF document outline (bookmarks) root dictionary.
 *
 * Referenced from the document catalog's /Outlines entry. First, Last, and
 * Count are back-filled via set() once all outline items have been registered.
 */
final class PdfOutline extends PdfDictionary
{
    public function __construct()
    {
        parent::__construct([
            'Count' => new PdfInteger(0),
            'Type' => new PdfName('Outlines'),
        ]);
    }
}
