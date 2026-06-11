<?php

declare(strict_types=1);

namespace PhpPdf\Document;

use InvalidArgumentException;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use RuntimeException;

use function assert;

/**
 * Combines multiple PdfDocument objects into a single output document.
 *
 * Each source document's objects are deep-cloned into a shared registry with
 * renumbered object IDs so there are no conflicts. A new flat page tree and
 * catalog are built from the collected pages; the source catalogs and page
 * tree nodes become unreachable (orphan) objects that PDF viewers ignore.
 *
 * Encryption and digital signatures from the source documents are NOT carried
 * over — the merged output is an unencrypted, unsigned document. Outlines
 * (bookmarks) are also dropped; if you need them, rebuild them with
 * PdfDocumentBuilder::outline() on the merged result. The output PDF version
 * is the highest version found across all source documents.
 *
 * Usage:
 *
 *   $merged = (new PdfDocumentMerger())
 *       ->add($docA->build())
 *       ->add($docB->build())
 *       ->build();
 *
 *   $output = new PdfMemoryOutput();
 *   (new PdfDocumentSerializer($output))->writeDocument($merged);
 */
final class PdfDocumentMerger
{
    /** @var list<\PhpPdf\Document\PdfDocument> */
    private array $sources = [];

    /**
     * Adds a document to the merge queue.
     *
     * Documents appear in the output in the order they were added. The merger
     * owns the reference but does not mutate the source document.
     */
    public function add(PdfDocument $document): self
    {
        $this->sources[] = $document;

        return $this;
    }

    /**
     * Merges all added documents into a single new PdfDocument.
     *
     * @throws \InvalidArgumentException when no documents have been added.
     */
    public function build(): PdfDocument
    {
        if ($this->sources === []) {
            throw new InvalidArgumentException('PdfDocumentMerger::build() requires at least one document.');
        }

        $outRegistry = new PdfObjectRegistry();
        $allPageRefs = [];

        $version = $this->resolveVersion();

        foreach ($this->sources as $doc) {
            $srcRegistry = $doc->getObjects();
            $srcAll = $srcRegistry->all(); // ascending object-number order

            // offset = current object count in the output registry.
            // Because PdfObjectRegistry numbers objects 1..N in insertion order,
            // registering source object with old_num K in a registry that already
            // has $offset objects will yield new_num = K + offset.
            $offset = count($outRegistry->all());

            // Build the renumber table before touching the output registry.
            $remap = [];

            foreach ($srcAll as $indirect) {
                $remap[$indirect->getObjectNumber()] = $indirect->getObjectNumber() + $offset;
            }

            // Identify which source object numbers are leaf pages.
            $srcCatalog = $srcRegistry->get($doc->getCatalog());
            $srcPagesRef = $this->requireDictRef($srcCatalog, 'Pages');
            $srcPageNums = array_flip($this->collectLeafPageNums($srcRegistry, $srcPagesRef));

            // Clone every source object into the output registry.
            // Registration order matches ascending object number, so the
            // auto-assigned new number always equals old_number + offset.
            foreach ($srcAll as $indirect) {
                $cloned = $this->cloneObject($indirect->getObject(), $remap);
                $newRef = $outRegistry->register($cloned);

                if (!isset($srcPageNums[$indirect->getObjectNumber()])) {
                    continue;
                }

                $allPageRefs[] = $newRef;
            }
        }

        // Build the merged (flat) page tree.
        $pagesDict = new PdfDictionary([
            'Count' => new PdfInteger(count($allPageRefs)),
            'Kids' => new PdfArray($allPageRefs),
            'Type' => new PdfName('Pages'),
        ]);
        $pagesRef = $outRegistry->register($pagesDict);

        // Fix /Parent in every page dict to reference the merged Pages node.
        foreach ($allPageRefs as $pageRef) {
            $pageDict = $outRegistry->get($pageRef);
            assert($pageDict instanceof PdfDictionary);
            $pageDict->set('Parent', $pagesRef);
        }

        // Build the merged catalog.
        $catalogDict = new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]);
        $catalogRef = $outRegistry->register($catalogDict);

        return new PdfDocument(
            $outRegistry,
            $version,
            $catalogRef,
            null, // no merged Info dictionary
            random_bytes(16), // fresh document ID
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Deep-clones a PdfObject tree, rewriting every PdfIndirectReference
     * through $remap. Mutable container objects (PdfDictionary, PdfArray,
     * PdfStream) are always recreated as base-class instances so the source
     * document's objects are never mutated. Immutable leaf objects are
     * returned as-is.
     *
     * @param array<int,int> $remap [sourceObjectNumber => mergedObjectNumber]
     */
    private function cloneObject(PdfObject $obj, array $remap): PdfObject
    {
        if ($obj instanceof PdfIndirectReference) {
            return new PdfIndirectReference(
                $remap[$obj->getObjectNumber()] ?? $obj->getObjectNumber(),
                0,
            );
        }

        // PdfStream must be checked before PdfDictionary — it is NOT a
        // PdfDictionary subclass, but it contains a mutable PdfDictionary.
        if ($obj instanceof PdfStream) {
            $clonedDict = $this->cloneObject($obj->getDictionary(), $remap);
            assert($clonedDict instanceof PdfDictionary);

            return new PdfStream($clonedDict, $obj->getData());
        }

        if ($obj instanceof PdfDictionary) {
            $entries = [];

            foreach ($obj->getEntries() as $key => $value) {
                $entries[$key] = $this->cloneObject($value, $remap);
            }

            return new PdfDictionary($entries);
        }

        if ($obj instanceof PdfArray) {
            $items = [];

            foreach ($obj->getItems() as $item) {
                $items[] = $this->cloneObject($item, $remap);
            }

            return new PdfArray($items);
        }

        // Immutable leaves: PdfInteger, PdfReal, PdfName, PdfString,
        // PdfHexString, PdfBoolean, PdfDate, PdfRawObject — safe to share.
        return $obj;
    }

    /**
     * Recursively collects the object numbers of all leaf /Page dicts under
     * the given page-tree node reference. Handles nested /Pages subtrees.
     *
     * @return list<int>
     */
    private function collectLeafPageNums(PdfObjectRegistry $registry, PdfIndirectReference $nodeRef): array
    {
        $node = $registry->get($nodeRef);

        if (!$node instanceof PdfDictionary) {
            return [];
        }

        $type = $node->get('Type');
        $kids = $node->get('Kids');

        // A node without /Type or with /Type /Page is treated as a leaf.
        if (!$type instanceof PdfName || $type->getValue() === 'Page') {
            return [$nodeRef->getObjectNumber()];
        }

        // /Pages node: descend into each kid.
        $result = [];

        if ($kids instanceof PdfArray) {
            foreach ($kids->getItems() as $kidRef) {
                if (!($kidRef instanceof PdfIndirectReference)) {
                    continue;
                }

                foreach ($this->collectLeafPageNums($registry, $kidRef) as $num) {
                    $result[] = $num;
                }
            }
        }

        return $result;
    }

    /**
     * Finds an entry in a dictionary by name and asserts it is an indirect reference.
     *
     * @throws \RuntimeException when the key is absent or not a reference.
     */
    private function requireDictRef(PdfObject $obj, string $key): PdfIndirectReference
    {
        if (!$obj instanceof PdfDictionary) {
            throw new RuntimeException("Expected a PdfDictionary, got " . $obj::class);
        }

        $value = $obj->get($key);

        if ($value === null) {
            throw new RuntimeException("Required key '$key' not found in dictionary.");
        }

        if (!$value instanceof PdfIndirectReference) {
            throw new RuntimeException("Dictionary entry '$key' is not an indirect reference.");
        }

        return $value;
    }

    /**
     * Returns the highest PDF version found across all source documents.
     */
    private function resolveVersion(): PdfVersion
    {
        $max = $this->sources[0]->getVersion();

        foreach (array_slice($this->sources, 1) as $doc) {
            if (!version_compare($doc->getVersion()->value, $max->value, '>')) {
                continue;
            }

            $max = $doc->getVersion();
        }

        return $max;
    }
}
