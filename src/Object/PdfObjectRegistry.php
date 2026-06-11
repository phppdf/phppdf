<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Object\Exception\ObjectRegistryNotFound;

/**
 * The central store for all indirect PDF objects in a document.
 *
 * Assigns object numbers, holds the live objects during document construction,
 * and supports incremental updates through generation numbers. The registry
 * is the source of truth for the cross-reference table written at the end
 * of the PDF file.
 */
final class PdfObjectRegistry
{
    /** @var array<int, array<int, \PhpPdf\Object\PdfIndirectObject>> */
    private array $objects;

    private int $nextObjectNumber;

    public function __construct()
    {
        $this->objects = [];
        $this->nextObjectNumber = 1;
    }

    /**
     * Retrieves the indirect object for a given reference.
     *
     * @throws \PhpPdf\Object\Exception\ObjectRegistryNotFound if the referenced object does not exist.
     */
    public function get(PdfIndirectReference $reference): PdfObject
    {
        $objectNumber = $reference->getObjectNumber();
        $generationNumber = $reference->getGenerationNumber();

        $object = $this->objects[$objectNumber][$generationNumber] ?? null;

        if ($object === null) {
            throw ObjectRegistryNotFound::forReference($reference);
        }

        return $object->getObject();
    }

    /**
     * Registers a new PDF object and returns a reference to it.
     *
     * Assigns the next available object number with generation number 0. Use
     * this for any object that must be referenced from elsewhere in the document
     * (pages, fonts, images, annotations, etc.).
     */
    public function register(PdfObject $object): PdfIndirectReference
    {
        $objectNumber = $this->nextObjectNumber++;

        $indirect = new PdfIndirectObject($objectNumber, 0, $object);

        $this->objects[$objectNumber][0] = $indirect;

        return new PdfIndirectReference($objectNumber, 0);
    }

    /**
     * Replaces an existing object with a new version, incrementing its generation number.
     *
     * Used for incremental updates where an existing object must be replaced
     * without renumbering it. Returns a new reference pointing to the latest
     * generation of that object number.
     *
     * @throws \PhpPdf\Object\Exception\ObjectRegistryNotFound if the object number does not exist.
     */
    public function update(PdfIndirectReference $reference, PdfObject $newObject,): PdfIndirectReference
    {
        $objectNumber = $reference->getObjectNumber();

        if (!isset($this->objects[$objectNumber])) {
            throw ObjectRegistryNotFound::forObjectNumber($objectNumber);
        }

        $newGeneration = $this->getLatestGeneration($objectNumber) + 1;

        $indirect = new PdfIndirectObject($objectNumber, $newGeneration, $newObject);

        $this->objects[$objectNumber][$newGeneration] = $indirect;

        return new PdfIndirectReference($objectNumber, $newGeneration);
    }

    /**
     * Returns the highest generation number recorded for the given object number.
     */
    public function getLatestGeneration(int $objectNumber): int
    {
        $generations = array_keys($this->objects[$objectNumber]);

        if ($generations === []) {
            throw ObjectRegistryNotFound::forObjectNumber($objectNumber);
        }

        return max($generations);
    }

    /**
     * Returns all registered indirect objects as a flat list, one per object number.
     *
     * When an object has multiple generations (incremental update), only the
     * latest generation is included. Used by the serializer to write the object
     * body and cross-reference table.
     *
     * @return list<\PhpPdf\Object\PdfIndirectObject>
     */
    public function all(): array
    {
        $result = [];

        foreach ($this->objects as $generations) {
            $generationNumbers = array_keys($generations);

            if ($generationNumbers === []) {
                continue;
            }

            $latestGeneration = max($generationNumbers);
            $result[] = $generations[$latestGeneration];
        }

        return $result;
    }
}
