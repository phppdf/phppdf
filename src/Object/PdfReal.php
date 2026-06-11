<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF real (floating-point) number type, formatted to four decimal places.
 *
 * Trailing zeros and a trailing decimal point are stripped so that 1.5000
 * serializes as '1.5' and 1.0000 as '1'. Use for values that require
 * fractional precision, such as alpha values, font metrics, and
 * transformation matrix components.
 */
final class PdfReal implements PdfObject
{
    public function __construct(private readonly float $value,)
    {
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function toPdfString(): string
    {
        return rtrim(rtrim(sprintf('%.4F', $this->value), '0'), '.');
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeReal($this);
    }
}
