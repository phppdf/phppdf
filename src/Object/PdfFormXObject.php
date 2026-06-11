<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF Form XObject: a reusable content stream embedded in the document.
 *
 * Encapsulates a self-contained piece of content — graphics, text, or images
 * — that can be referenced from multiple pages via the Do operator. The BBox
 * defines the bounding box in the form's own coordinate space. Register this
 * object and reference it from a page's XObject resource dictionary.
 */
final class PdfFormXObject extends PdfStream
{
    public function __construct(PdfStreamData $content, PdfRectangle $bbox,)
    {
        parent::__construct(
            new PdfDictionary([
                'BBox' => $bbox,
                'Subtype' => new PdfName('Form'),
                'Type' => new PdfName('XObject'),
            ]),
            $content,
        );
    }
}
