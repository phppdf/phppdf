<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * The PDF dictionary type, serialized as key/value pairs between << and >>.
 *
 * Dictionaries are the primary structuring mechanism in PDF. Nearly every
 * major object — pages, fonts, images, annotations — is expressed as a
 * dictionary. Per the PDF specification, keys are always names; they are
 * stored here as plain strings (without the leading slash).
 */
class PdfDictionary implements PdfObject
{
    /** @var array<string, \PhpPdf\Object\PdfObject> */
    private array $entries = [];

    /** @param array<string, \PhpPdf\Object\PdfObject> $entries */
    public function __construct(array $entries = [])
    {
        $this->entries = $entries;
    }

    /** @return array<string, \PhpPdf\Object\PdfObject> */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function get(string $key): ?PdfObject
    {
        return $this->entries[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->entries[$key]);
    }

    /**
     * Sets or replaces a dictionary entry by key name.
     */
    public function set(string $key, PdfObject $value): void
    {
        $this->entries[$key] = $value;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeDictionary($this);
    }
}
