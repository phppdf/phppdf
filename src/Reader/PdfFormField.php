<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

/**
 * Represents one AcroForm field read from a PDF.
 *
 * The objectNumber / generationNumber pair uniquely identifies the field's
 * indirect object in the file; PdfAcroFormFiller uses it to write the
 * replacement object in an incremental update.
 */
final class PdfFormField
{
    /** @param list<string> $options Choice fields only: the list of /Opt values. */
    public function __construct(
        public readonly int $objectNumber,
        public readonly int $generationNumber,
        public readonly string $name,
        public readonly string $fullName,
        public readonly PdfFormFieldType $type,
        public readonly string|bool|null $value,
        public readonly array $options = [],
        public readonly bool $readOnly = false,
        public readonly bool $multiLine = false,
    ) {
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function isMultiLine(): bool
    {
        return $this->multiLine;
    }
}
