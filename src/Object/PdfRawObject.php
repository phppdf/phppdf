<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * A pre-serialized PDF object whose syntax is emitted verbatim.
 *
 * Use only when the required PDF syntax cannot be expressed through the
 * structured object types — for example, when embedding hand-crafted
 * PostScript fragments or other opaque byte sequences. The value is written
 * to the output without any escaping or transformation.
 */
final class PdfRawObject implements PdfObject
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
        $serializer->writeRawObject($this);
    }
}
