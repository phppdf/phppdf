<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF link annotation: a clickable rectangle that triggers an action.
 *
 * Placed on a page via the page dictionary's Annots array. The rectangle
 * defines the clickable area in page coordinates (origin at bottom-left).
 * The action is either a PdfGoToAction (internal navigation) or a
 * PdfUriAction (external URL). The border defaults to invisible [0 0 0].
 */
final class PdfLinkAnnotation extends PdfDictionary
{
    public function __construct(PdfArray $rect, PdfDictionary $action)
    {
        parent::__construct([
            'A' => $action,
            'Border' => new PdfArray([
                new PdfInteger(0),
                new PdfInteger(0),
                new PdfInteger(0),
            ]),
            'Rect' => $rect,
            'Subtype' => new PdfName('Link'),
            'Type' => new PdfName('Annot'),
        ]);
    }
}
