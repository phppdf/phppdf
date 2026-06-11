<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF indirect object: a numbered object definition (n g obj...endobj).
 *
 * Every object referenced from elsewhere in a PDF must be wrapped in an
 * indirect object. The object number and generation number together form
 * the object's identity. Generation numbers start at 0 and increment when
 * an object is replaced in an incremental update.
 */
final class PdfIndirectObject
{
    public function __construct(
        private readonly int $objectNumber,
        private readonly int $generationNumber,
        private readonly PdfObject $object,
    ) {
    }

    /**
     * Returns the 1-based object number assigned by the registry.
     */
    public function getObjectNumber(): int
    {
        return $this->objectNumber;
    }

    /**
     * Returns the generation number (0 for original objects, incremented on update).
     */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /**
     * Returns the wrapped PDF object.
     */
    public function getObject(): PdfObject
    {
        return $this->object;
    }
}
