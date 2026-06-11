<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF integer type, serialized as a plain decimal integer.
 *
 * Used for all whole-number values in PDF dictionaries and arrays, such as
 * page count, object numbers, glyph widths, bit depths, and flag bitmasks.
 */
final class PdfInteger implements PdfObject
{
    public function __construct(private readonly int $value)
    {
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeInteger($this);
    }
}
