<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The Font sub-dictionary of a PDF resource dictionary.
 *
 * Maps short local names (e.g. 'F1', 'F2') to indirect references to font
 * dictionaries. The local name is what appears as the first operand of the
 * Tf operator in a content stream (PdfContentStreamBuilder::setFont()).
 */
final class PdfFontResources extends PdfDictionary
{
    /** @param array<string, \PhpPdf\Object\PdfIndirectReference> $fonts */
    public function __construct(array $fonts)
    {
        parent::__construct($fonts);
    }
}
