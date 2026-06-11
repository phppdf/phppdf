<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF null object, serialized as the literal 'null'.
 *
 * Represents the explicit absence of a value. Used in arrays and dictionaries
 * to indicate that an optional entry is intentionally absent rather than
 * simply omitted, when the PDF specification requires the key to be present.
 */
final class PdfNull implements PdfObject
{
    public function toPdfString(): string
    {
        return 'null';
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeRawObject(new PdfRawObject('null'));
    }
}
