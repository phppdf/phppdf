<?php

declare(strict_types=1);

namespace PhpPdf\Document;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfObjectSource;
use PhpPdf\Object\PdfStream;

use function assert;

/**
 * Clones objects from a PdfObjectSource into a target PdfObjectRegistry.
 *
 * Used when importing a page from an external PDF as a Form XObject. Each
 * indirect reference encountered during traversal is resolved from the source
 * document, deep-cloned, and registered in the target registry. A memo table
 * prevents duplicate registrations if the same object is referenced multiple
 * times (e.g. a shared font), and detects cycles to avoid infinite recursion.
 *
 * Immutable leaf objects (integers, reals, strings, names, booleans) are
 * shared rather than copied, which is safe because they have no mutable state.
 */
final class PdfObjectImporter
{
    /** @var array<int, \PhpPdf\Object\PdfIndirectReference> Maps source object numbers to cloned target references. */
    private array $cloned = [];

    /** @var array<int, true> In-progress guard against cyclic object graphs. */
    private array $inProgress = [];

    public function __construct(private readonly PdfObjectSource $source, private readonly PdfObjectRegistry $target,)
    {
    }

    /**
     * Deep-clones $obj into the target registry.
     *
     * If $obj is an indirect reference, the referenced object is loaded from
     * the source document, cloned, and registered in the target. The returned
     * PdfIndirectReference points to the newly registered clone.
     *
     * If $obj is a dictionary or array, its entries are cloned recursively so
     * all nested indirect references are remapped.
     *
     * If $obj is an immutable leaf (integer, string, name, etc.) it is
     * returned as-is.
     */
    public function importObject(PdfObject $obj): PdfObject
    {
        if ($obj instanceof PdfIndirectReference) {
            return $this->importIndirectReference($obj);
        }

        return $this->deepClone($obj);
    }

    // -------------------------------------------------------------------------

    private function importIndirectReference(PdfIndirectReference $ref): PdfIndirectReference
    {
        $srcNum = $ref->getObjectNumber();

        if (isset($this->cloned[$srcNum])) {
            return $this->cloned[$srcNum];
        }

        if (isset($this->inProgress[$srcNum])) {
            // Cyclic reference (should not occur in well-formed PDFs).
            // Return a safe no-op reference rather than looping.
            return new PdfIndirectReference(0, 65535);
        }

        $this->inProgress[$srcNum] = true;

        $srcObj = $this->source->getObject($ref);
        $cloned = $this->deepClone($srcObj);
        $newRef = $this->target->register($cloned);
        $this->cloned[$srcNum] = $newRef;

        unset($this->inProgress[$srcNum]);

        return $newRef;
    }

    private function deepClone(PdfObject $obj): PdfObject
    {
        if ($obj instanceof PdfIndirectReference) {
            return $this->importIndirectReference($obj);
        }

        // PdfStream must be checked before PdfDictionary (not a subclass, but has one).
        if ($obj instanceof PdfStream) {
            $clonedDict = $this->deepClone($obj->getDictionary());
            assert($clonedDict instanceof PdfDictionary);

            return new PdfStream($clonedDict, $obj->getData());
        }

        if ($obj instanceof PdfDictionary) {
            $entries = [];

            foreach ($obj->getEntries() as $key => $value) {
                $entries[$key] = $this->deepClone($value);
            }

            return new PdfDictionary($entries);
        }

        if ($obj instanceof PdfArray) {
            $items = [];

            foreach ($obj->getItems() as $item) {
                $items[] = $this->deepClone($item);
            }

            return new PdfArray($items);
        }

        // Immutable leaf (PdfInteger, PdfReal, PdfString, PdfName, PdfBoolean,
        // PdfNull, PdfRawObject, etc.) — safe to share.
        return $obj;
    }
}
