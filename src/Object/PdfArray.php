<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF array type, serialized as a space-separated list between square brackets.
 *
 * Arrays hold an ordered sequence of PDF objects of any type and are used
 * throughout PDF for rectangles, color components, font width tables,
 * annotation borders, destination parameters, and many other structures
 * that require an ordered collection.
 */
class PdfArray implements PdfObject
{
    /** @param list<\PhpPdf\Object\PdfObject> $items */
    public function __construct(private readonly array $items)
    {
    }

    /** @return list<\PhpPdf\Object\PdfObject> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeArray($this);
    }
}
