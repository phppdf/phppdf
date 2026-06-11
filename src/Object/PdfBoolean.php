<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF boolean type, serialized as the literal 'true' or 'false'.
 *
 * Used in dictionary entries where a flag must be expressed as a boolean
 * literal, such as the EncryptMetadata entry in an Encrypt dictionary or
 * the NeedAppearances entry in an AcroForm dictionary.
 */
final class PdfBoolean implements PdfObject
{
    public function __construct(private readonly bool $value)
    {
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeBoolean($this);
    }
}
