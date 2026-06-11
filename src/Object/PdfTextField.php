<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF text field widget annotation (AcroForm field type Tx).
 *
 * Represents a single-line or multi-line text input in an interactive form.
 * $name is the partial field name used to identify the field in form data.
 * The rectangle defines the field's position and size on the page.
 */
final class PdfTextField extends PdfDictionary
{
    public function __construct(string $name, PdfRectangle $rectangle)
    {
        parent::__construct([
            'FT' => new PdfName('Tx'),
            'Rect' => $rectangle,
            'T' => new PdfString($name),
        ]);
    }
}
