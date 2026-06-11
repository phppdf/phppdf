<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The PDF AcroForm dictionary, the root of the interactive form.
 *
 * Referenced from the document catalog's AcroForm entry. The Fields array
 * holds indirect references to top-level form field widgets. Additional
 * options (DA, DR, NeedAppearances, SigFlags) can be added via set().
 */
final class PdfAcroForm extends PdfDictionary
{
    /** @param list<\PhpPdf\Object\PdfIndirectReference> $fields */
    public function __construct(array $fields)
    {
        parent::__construct([
            'Fields' => new PdfArray($fields),
        ]);
    }
}
