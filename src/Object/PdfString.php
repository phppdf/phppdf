<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF literal string type, serialized in parentheses.
 *
 * Used for human-readable text values such as document metadata (title,
 * author), annotation content, and form field values. Special characters
 * are escaped by the serializer. For binary data or character codes that
 * would be unsafe in a literal string, use PdfHexString instead.
 */
final class PdfString implements PdfObject
{
    public function __construct(private readonly string $value)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeString($this);
    }
}
