<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF GoTo action that navigates to a destination within the document.
 *
 * When activated (e.g. by clicking a link annotation), the viewer scrolls
 * to and displays the page and view specified by the destination. Use with
 * PdfLinkAnnotation to create internal navigation links.
 */
final class PdfGoToAction extends PdfDictionary
{
    public function __construct(PdfDestination $destination)
    {
        parent::__construct([
            'D' => $destination,
            'S' => new PdfName('GoTo'),
        ]);
    }
}
