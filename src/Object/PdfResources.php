<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF resource dictionary associating named resources with a page or form.
 *
 * Resources allow content streams to reference objects by short local names
 * (e.g. 'F1' for a font). Currently builds a Font sub-dictionary from the
 * supplied name-to-reference map. Other resource types (XObject, ExtGState,
 * ColorSpace, Pattern, Shading) can be added via set().
 */
final class PdfResources extends PdfDictionary
{
    /** @param array<string, \PhpPdf\Object\PdfIndirectReference> $fonts */
    public function __construct(array $fonts = [])
    {
        $fontDictionary = [];

        foreach ($fonts as $name => $reference) {
            $fontDictionary[$name] = $reference;
        }

        parent::__construct([
            'Font' => new PdfDictionary($fontDictionary),
        ]);
    }
}
