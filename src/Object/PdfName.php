<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF name type, serialized with a leading solidus (e.g. /Type).
 *
 * Names are atomic tokens used as dictionary keys, type identifiers, and
 * enumerated values throughout PDF. They are case-sensitive. Special
 * characters are percent-encoded by the serializer.
 */
class PdfName implements PdfObject
{
    public function __construct(private readonly string $name)
    {
    }

    public function getValue(): string
    {
        return $this->name;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeName($this);
    }
}
