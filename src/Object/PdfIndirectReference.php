<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * A PDF indirect reference: the 'n g R' token that points to an indirect object.
 *
 * References allow one PDF object to refer to another by its object and
 * generation number without embedding the target's content inline. A reference
 * is itself a PdfObject so it can appear as a value in dictionaries and arrays,
 * and it is also the handle used to retrieve the underlying object from the registry.
 */
final class PdfIndirectReference implements PdfObject
{
    public function __construct(private readonly int $objectNumber, private readonly int $generationNumber)
    {
    }

    public function getObjectNumber(): int
    {
        return $this->objectNumber;
    }

    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeIndirectReference($this);
    }
}
