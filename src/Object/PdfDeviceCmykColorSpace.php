<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * The /DeviceCMYK color space name.
 *
 * A named-constant subclass of PdfName. Use as the ColorSpace entry in
 * image XObjects or as the operand of a CS/cs operator when the color space
 * must be referenced as a named resource rather than set implicitly.
 */
final class PdfDeviceCmykColorSpace extends PdfName
{
    public function __construct()
    {
        parent::__construct('DeviceCMYK');
    }
}
