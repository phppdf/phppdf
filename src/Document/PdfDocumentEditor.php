<?php

declare(strict_types=1);

namespace PhpPdf\Document;

use InvalidArgumentException;
use OutOfBoundsException;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfObjectSource;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use RuntimeException;

use function assert;

/**
 * Post-build page-level editor for compiled PdfDocument objects.
 *
 * Maintains an ordered plan of (source document, page reference) pairs.
 * Operations (remove, move, insert) rewrite the plan; build() deep-clones
 * all required objects into a fresh registry, renumbers indirect references,
 * and returns a new PdfDocument. Source documents are never mutated.
 *
 * This editor works on compiled documents — including the output of
 * PdfDocumentMerger — so pages from different source documents can be
 * freely mixed in a single output. To edit a PDF file read from disk,
 * use the fromReadDocument() factory instead of the constructor.
 *
 * Usage:
 *
 *   $editor = new PdfDocumentEditor($compiledDocument);
 *   $editor
 *       ->removePage(0) // drop cover page
 *       ->movePage(2, 0) // bring chapter 3 to front
 *       ->insertPagesFrom($appendix, before: 1) // splice in all appendix pages
 *   ;
 *   $result = $editor->build();
 *
 * Notes:
 *   - Encryption, digital signatures, and outlines from source documents
 *     are not preserved in the output (same limitation as PdfDocumentMerger).
 *   - Orphan objects (unreachable from any page) are included in the output
 *     unchanged; they carry no cost beyond a few extra bytes.
 *   - The output PDF version is the highest version found across all source
 *     documents referenced by the final plan.
 */
final class PdfDocumentEditor
{
    /**
     * @var list<array{
     *     doc: \PhpPdf\Document\PdfDocument,
     *     ref: \PhpPdf\Object\PdfIndirectReference,
     *     rotate: int|null,
     *     cropBox: array{0: float, 1: float, 2: float, 3: float}|null,
     * }>
     */
    private array $plan;

    /** @var array<string, string> Local font name → base Type1 font name for header/footer. */
    private array $hfType1Fonts = [];

    /** @var array<string, \PhpPdf\Font\TrueTypeFont> Local font name → embedded font for header/footer. */
    private array $hfEmbeddedFonts = [];

    /** @var callable|null */
    private mixed $headerTemplate = null;

    /** @var callable|null */
    private mixed $footerTemplate = null;

    /**
     * Initialises the editor with all pages of the source document in their
     * original order.
     */
    public function __construct(PdfDocument $source)
    {
        $this->plan = [];

        foreach ($this->leafPageRefs($source) as $ref) {
            $this->plan[] = ['doc' => $source, 'ref' => $ref, 'rotate' => null, 'cropBox' => null];
        }
    }

    /**
     * Opens an existing PDF file and returns an editor ready to operate on it.
     *
     * All objects reachable from the document catalog are imported into a fresh
     * in-memory registry (via PdfObjectImporter), so the file handle is no
     * longer needed after this call. For large files this means the full object
     * graph is loaded into RAM — the same trade-off made by any editing operation.
     *
     * Encryption and digital signatures from the source file are not preserved
     * in the output (same limitation as the constructor).
     *
     * @throws \RuntimeException if the file cannot be opened or has no /Root entry.
     */
    public static function fromReadDocument(PdfObjectSource $source): self
    {
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($source, $registry);
        $trailer = $source->getTrailer();

        $catalogSrcRef = $trailer->get('Root');

        if (!$catalogSrcRef instanceof PdfIndirectReference) {
            throw new RuntimeException('PDF trailer does not contain a valid /Root entry.');
        }

        $catalogRef = $importer->importObject($catalogSrcRef);
        assert($catalogRef instanceof PdfIndirectReference);

        $infoRef = null;
        $infoSrcRef = $trailer->get('Info');

        if ($infoSrcRef instanceof PdfIndirectReference) {
            $infoRef = $importer->importObject($infoSrcRef);
            assert($infoRef instanceof PdfIndirectReference);
        }

        $doc = new PdfDocument(
            $registry,
            $source->getVersion(),
            $catalogRef,
            $infoRef,
        );

        return new self($doc);
    }

    // -------------------------------------------------------------------------
    // Plan operations (all return $this for fluent chaining)
    // -------------------------------------------------------------------------

    /**
     * Returns the number of pages currently in the plan.
     */
    public function getPageCount(): int
    {
        return count($this->plan);
    }

    /**
     * Removes the page at the given 0-based index from the plan.
     *
     * All subsequent pages shift one position toward the front. The page's
     * objects remain in the source document but are not copied to the output
     * unless another plan entry references the same source document (in which
     * case ALL objects from that source are still copied — only the page itself
     * is excluded from the page tree).
     *
     * @throws \OutOfBoundsException
     */
    public function removePage(int $index): self
    {
        $this->assertIndex($index, 'Remove');
        array_splice($this->plan, $index, 1);

        return $this;
    }

    /**
     * Moves the page currently at $from to position $to.
     *
     * $to is the final 0-based index the page will occupy. Both indices are
     * measured against the current plan (before the move). Moving a page to
     * its current position is a no-op.
     *
     * Example — reverse a two-page document:
     *   $editor->movePage(1, 0)
     *
     * @throws \OutOfBoundsException
     */
    public function movePage(int $from, int $to): self
    {
        $this->assertIndex($from, 'Source');
        $this->assertIndex($to, 'Target');

        [$entry] = array_splice($this->plan, $from, 1);
        array_splice($this->plan, $to, 0, [$entry]);

        return $this;
    }

    /**
     * Inserts all pages from $source before the given position.
     *
     * Pass getPageCount() as $before to append the source pages at the end.
     * Pages from $source are appended in their natural order (as defined by
     * the source document's page tree).
     *
     * @throws \OutOfBoundsException when $before is outside [0, getPageCount()].
     */
    public function insertPagesFrom(PdfDocument $source, int $before): self
    {
        $count = count($this->plan);

        if ($before < 0 || $before > $count) {
            throw new OutOfBoundsException("Insert position $before is out of range [0, $count].");
        }

        $entries = [];

        foreach ($this->leafPageRefs($source) as $ref) {
            $entries[] = ['doc' => $source, 'ref' => $ref, 'rotate' => null, 'cropBox' => null];
        }

        array_splice($this->plan, $before, 0, $entries);

        return $this;
    }

    /**
     * Sets the clockwise display rotation for the page at the given index.
     *
     * $degrees must be 0, 90, 180, or 270. The value is written as a /Rotate
     * entry in the output page dictionary. The page's content stream is
     * unchanged — only the viewer's rendering orientation is affected.
     *
     * Calling this method again for the same index replaces the earlier value.
     *
     * @throws \InvalidArgumentException when $degrees is not in {0, 90, 180, 270}.
     * @throws \OutOfBoundsException when $index is out of range.
     */
    public function rotatePage(int $index, int $degrees): self
    {
        $this->assertIndex($index, 'Rotate');

        if (!in_array($degrees, [0, 90, 180, 270], true)) {
            throw new InvalidArgumentException("Rotation must be 0, 90, 180, or 270; got {$degrees}.");
        }

        $this->plan[$index]['rotate'] = $degrees;

        return $this;
    }

    /**
     * Applies a /CropBox to the page at the given index.
     *
     * The crop box is expressed in the page's own coordinate system (PDF user
     * units, origin at the bottom-left of the page). Only the intersection of
     * the crop box and the /MediaBox is visible in the viewer; content outside
     * the crop box is clipped but not removed from the file.
     *
     * (x, y) is the bottom-left corner of the crop window. Coordinates are
     * clamped to the page boundaries by compliant viewers.
     *
     * Calling this method again for the same index replaces the earlier value.
     *
     * @throws \OutOfBoundsException when $index is out of range.
     */
    public function cropPage(int $index, float $x, float $y, float $width, float $height): self
    {
        $this->assertIndex($index, 'Crop');

        $this->plan[$index]['cropBox'] = [$x, $y, $x + $width, $y + $height];

        return $this;
    }

    // -------------------------------------------------------------------------
    // Header / footer
    // -------------------------------------------------------------------------

    /**
     * Registers a standard Type1 font available in header/footer templates.
     *
     * $localName is the short resource name used in setFont() calls inside the
     * template callback. $baseFont must be one of the 14 standard PDF fonts.
     */
    public function useType1Font(string $localName, string $baseFont): self
    {
        $this->hfType1Fonts[$localName] = $baseFont;

        return $this;
    }

    /**
     * Registers an embedded TrueType/OpenType font for header/footer templates.
     *
     * The font is compiled once (with subsetting for glyphs used across all
     * pages) and shared by every page's header and footer stream.
     */
    public function useEmbeddedFont(string $localName, TrueTypeFont $font): self
    {
        $this->hfEmbeddedFonts[$localName] = $font;

        return $this;
    }

    /**
     * Sets the header template drawn at the top of every page.
     *
     * The callable receives:
     *   - PdfContentStreamBuilder $s — add drawing operators to it
     *   - int $pageNumber — 1-based current page number
     *   - int $totalPages — total number of pages in the output
     *   - float $pageWidth — page width in points
     *   - float $pageHeight — page height in points
     *
     * @param callable(\PhpPdf\Content\PdfContentStreamBuilder, int, int, float, float): void $template
     */
    public function header(callable $template): self
    {
        $this->headerTemplate = $template;

        return $this;
    }

    /**
     * Sets the footer template drawn at the bottom of every page.
     *
     * Same signature as header().
     *
     * @param callable(\PhpPdf\Content\PdfContentStreamBuilder, int, int, float, float): void $template
     */
    public function footer(callable $template): self
    {
        $this->footerTemplate = $template;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Build
    // -------------------------------------------------------------------------

    /**
     * Materialises the plan into a new PdfDocument.
     *
     * For each unique source document referenced by the plan, only the objects
     * reachable from the pages that appear in the plan are copied into the
     * output registry. This means objects belonging exclusively to removed pages
     * (content streams, per-page font programs, etc.) are not carried over,
     * keeping the output as compact as possible.
     *
     * The reachability traversal skips /Parent entries to avoid pulling in the
     * source document's entire page tree structure. A new flat page tree and
     * catalog are built from the plan's ordered page references, and all page
     * /Parent entries are updated to point to the new tree root.
     */
    public function build(): PdfDocument
    {
        $outRegistry = new PdfObjectRegistry();

        // remap table per source document, keyed by spl_object_id.
        /** @var array<int, array<int,int>> $remaps */
        $remaps = [];

        // Process each unique source document exactly once, in first-seen order.
        foreach ($this->plan as ['doc' => $doc]) {
            $docId = spl_object_id($doc);

            if (isset($remaps[$docId])) {
                continue;
            }

            $srcRegistry = $doc->getObjects();

            // Step 1: Traverse from every plan page in this source document to
            // collect the complete set of reachable object numbers. /Parent is
            // excluded so sibling pages (and the Pages tree root) are not pulled
            // in transitively.
            $needed = []; // [objectNumber => true]

            foreach ($this->plan as ['doc' => $d, 'ref' => $planRef]) {
                if (spl_object_id($d) !== $docId) {
                    continue;
                }

                $this->collectReachable($srcRegistry, $planRef, $needed);
            }

            // Step 2: Build a remap for the needed objects only, assigning
            // sequential new numbers starting right after the current output.
            $nextNew = count($outRegistry->all()) + 1;
            $remap = [];

            foreach ($srcRegistry->all() as $indirect) {
                if (!isset($needed[$indirect->getObjectNumber()])) {
                    continue;
                }

                $remap[$indirect->getObjectNumber()] = $nextNew++;
            }

            // Step 3: Register clones of needed objects in ascending old-number
            // order so the auto-assigned new numbers match the remap exactly.
            foreach ($srcRegistry->all() as $indirect) {
                if (!isset($needed[$indirect->getObjectNumber()])) {
                    continue;
                }

                $outRegistry->register(
                    $this->cloneObject($indirect->getObject(), $remap),
                );
            }

            $remaps[$docId] = $remap;
        }

        // Build the ordered list of new page references from the plan.
        $allPageRefs = [];

        foreach ($this->plan as ['doc' => $doc, 'ref' => $ref]) {
            $remap = $remaps[spl_object_id($doc)];
            $newNum = $remap[$ref->getObjectNumber()];
            $allPageRefs[] = new PdfIndirectReference($newNum, 0);
        }

        // Build flat merged Pages tree.
        $pagesDict = new PdfDictionary([
            'Count' => new PdfInteger(count($allPageRefs)),
            'Kids' => new PdfArray($allPageRefs),
            'Type' => new PdfName('Pages'),
        ]);
        $pagesRef = $outRegistry->register($pagesDict);

        // Fix /Parent and apply per-page rotation/crop overrides.
        foreach ($allPageRefs as $i => $pageRef) {
            $pageDict = $outRegistry->get($pageRef);
            assert($pageDict instanceof PdfDictionary);
            $pageDict->set('Parent', $pagesRef);

            $entry = $this->plan[$i];

            if ($entry['rotate'] !== null) {
                $pageDict->set('Rotate', new PdfInteger($entry['rotate']));
            }

            if ($entry['cropBox'] === null) {
                continue;
            }

            [$x1, $y1, $x2, $y2] = $entry['cropBox'];
            $pageDict->set('CropBox', new PdfArray([
                new PdfReal($x1), new PdfReal($y1),
                new PdfReal($x2), new PdfReal($y2),
            ]));
        }

        if ($this->headerTemplate !== null || $this->footerTemplate !== null) {
            $this->applyHeaderFooter($outRegistry, $allPageRefs);
        }

        // Build catalog.
        $catalogDict = new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]);
        $catalogRef = $outRegistry->register($catalogDict);

        return new PdfDocument(
            $outRegistry,
            $this->resolveVersion(),
            $catalogRef,
            null,
            random_bytes(16),
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /** @throws \OutOfBoundsException */
    private function assertIndex(int $index, string $label): void
    {
        $count = count($this->plan);

        if ($index < 0 || $index >= $count) {
            throw new OutOfBoundsException("$label index $index is out of range [0, " . ($count - 1) . "].");
        }
    }

    /**
     * Returns the highest PDF version across all source documents in the plan,
     * falling back to PDF 1.7 when the plan is empty.
     */
    private function resolveVersion(): PdfVersion
    {
        $max = null;

        foreach ($this->plan as ['doc' => $doc]) {
            $v = $doc->getVersion();

            if ($max !== null && !version_compare($v->value, $max->value, '>')) {
                continue;
            }

            $max = $v;
        }

        return $max ?? PdfVersion::PDF_1_7;
    }

    /**
     * Returns an ordered list of indirect references to all leaf /Page dicts
     * found in the document's page tree.
     *
     * @return list<\PhpPdf\Object\PdfIndirectReference>
     */
    private function leafPageRefs(PdfDocument $doc): array
    {
        $registry = $doc->getObjects();
        $catalog = $registry->get($doc->getCatalog());
        $pagesRef = $this->requireDictRef($catalog, 'Pages');

        return $this->collectLeafPageRefs($registry, $pagesRef);
    }

    /**
     * Recursively collects leaf /Page references from a page-tree node.
     *
     * @return list<\PhpPdf\Object\PdfIndirectReference>
     */
    private function collectLeafPageRefs(PdfObjectRegistry $registry, PdfIndirectReference $nodeRef): array
    {
        $node = $registry->get($nodeRef);

        if (!$node instanceof PdfDictionary) {
            return [];
        }

        $type = $node->get('Type');
        $kids = $node->get('Kids');

        if (!$type instanceof PdfName || $type->getValue() === 'Page') {
            return [$nodeRef];
        }

        $result = [];

        if ($kids instanceof PdfArray) {
            foreach ($kids->getItems() as $kidRef) {
                if (!($kidRef instanceof PdfIndirectReference)) {
                    continue;
                }

                foreach ($this->collectLeafPageRefs($registry, $kidRef) as $ref) {
                    $result[] = $ref;
                }
            }
        }

        return $result;
    }

    /**
     * Recursively marks all objects reachable from $ref as needed.
     *
     * /Parent entries are intentionally skipped so the source document's
     * Pages tree node (and its other children) are not transitively included.
     *
     * @param array<int,bool> $seen Accumulated set of reachable object numbers.
     */
    private function collectReachable(PdfObjectRegistry $registry, PdfIndirectReference $ref, array &$seen,): void
    {
        $num = $ref->getObjectNumber();

        if (isset($seen[$num])) {
            return;
        }

        $seen[$num] = true;

        try {
            $obj = $registry->get($ref);
        } catch (InvalidArgumentException) {
            return; // dangling reference in the source — skip gracefully
        }

        $this->traverseRefs($obj, $registry, $seen);
    }

    /**
     * Walks a PdfObject tree to discover all indirect references.
     *
     * @param array<int,bool> $seen
     */
    private function traverseRefs(PdfObject $obj, PdfObjectRegistry $registry, array &$seen): void
    {
        if ($obj instanceof PdfIndirectReference) {
            $this->collectReachable($registry, $obj, $seen);

            return;
        }

        // PdfStream before PdfDictionary — same ordering reason as cloneObject.
        if ($obj instanceof PdfStream) {
            $this->traverseRefs($obj->getDictionary(), $registry, $seen);

            return;
        }

        if ($obj instanceof PdfDictionary) {
            foreach ($obj->getEntries() as $key => $value) {
                if ($key === 'Parent') {
                    continue; // break the page-tree cycle
                }

                $this->traverseRefs($value, $registry, $seen);
            }

            return;
        }

        if ($obj instanceof PdfArray) {
            foreach ($obj->getItems() as $item) {
                $this->traverseRefs($item, $registry, $seen);
            }
        }
        // Leaf types carry no further references.
    }

    /**
     * Deep-clones a PdfObject tree, rewriting every PdfIndirectReference via
     * $remap. Mutable containers are always recreated as fresh base-class
     * instances so source objects are never mutated. Immutable leaves are
     * returned as-is.
     *
     * @param array<int,int> $remap [sourceObjectNumber => outputObjectNumber]
     */
    private function cloneObject(PdfObject $obj, array $remap): PdfObject
    {
        if ($obj instanceof PdfIndirectReference) {
            return new PdfIndirectReference(
                $remap[$obj->getObjectNumber()] ?? $obj->getObjectNumber(),
                0,
            );
        }

        // PdfStream must be checked before PdfDictionary — it holds a
        // PdfDictionary but is not a subclass of it.
        if ($obj instanceof PdfStream) {
            $dict = $this->cloneObject($obj->getDictionary(), $remap);
            assert($dict instanceof PdfDictionary);

            return new PdfStream($dict, $obj->getData());
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
        // PdfHexString, PdfBoolean, PdfDate, PdfRawObject.
        return $obj;
    }

    /**
     * Injects header and/or footer content streams into every page.
     *
     * Two-pass approach for embedded fonts: first dry-run all callbacks to
     * accumulate usedGlyphs, compile fonts once, then rebuild real streams.
     *
     * @param list<\PhpPdf\Object\PdfIndirectReference> $allPageRefs
     */
    private function applyHeaderFooter(PdfObjectRegistry $outRegistry, array $allPageRefs): void
    {
        $totalPages = count($allPageRefs);
        $embFonts = $this->hfEmbeddedFonts;

        // Pass 1: dry-run to collect glyph usage across all pages.
        $allUsedGlyphs = array_fill_keys(array_keys($embFonts), []);

        foreach ($allPageRefs as $i => $pageRef) {
            [$w, $h] = $this->readPageDimensions($outRegistry, $pageRef);

            foreach ([$this->headerTemplate, $this->footerTemplate] as $tpl) {
                if ($tpl === null) {
                    continue;
                }

                $sb = new PdfContentStreamBuilder($embFonts);
                $tpl($sb, $i + 1, $totalPages, $w, $h);

                foreach ($sb->getUsedGlyphs() as $name => $glyphs) {
                    $allUsedGlyphs[$name] = array_replace($allUsedGlyphs[$name] ?? [], $glyphs);
                }
            }
        }

        // Compile fonts once.
        $compiledFontRefs = [];

        foreach ($this->hfType1Fonts as $localName => $baseFont) {
            $compiledFontRefs[$localName] = PdfFontCompiler::compileType1($outRegistry, $baseFont);
        }

        foreach ($embFonts as $localName => $font) {
            $compiledFontRefs[$localName] = PdfFontCompiler::compileEmbedded(
                $outRegistry,
                $font,
                $allUsedGlyphs[$localName] ?? [],
            );
        }

        // Pass 2: build real content streams and wire them into each page.
        foreach ($allPageRefs as $i => $pageRef) {
            $pageDict = $outRegistry->get($pageRef);
            assert($pageDict instanceof PdfDictionary);
            [$w, $h] = $this->readPageDimensions($outRegistry, $pageRef);

            $newContents = [];

            if ($this->headerTemplate !== null) {
                $sb = new PdfContentStreamBuilder($embFonts);
                ($this->headerTemplate)($sb, $i + 1, $totalPages, $w, $h);
                $newContents[] = $outRegistry->register(new PdfStream(
                    new PdfDictionary(),
                    new PdfContentStreamData($sb->build()),
                ));
            }

            $existing = $pageDict->get('Contents');

            if ($existing instanceof PdfArray) {
                foreach ($existing->getItems() as $item) {
                    $newContents[] = $item;
                }
            } elseif ($existing instanceof PdfIndirectReference) {
                $newContents[] = $existing;
            }

            if ($this->footerTemplate !== null) {
                $sb = new PdfContentStreamBuilder($embFonts);
                ($this->footerTemplate)($sb, $i + 1, $totalPages, $w, $h);
                $newContents[] = $outRegistry->register(new PdfStream(
                    new PdfDictionary(),
                    new PdfContentStreamData($sb->build()),
                ));
            }

            $pageDict->set('Contents', new PdfArray($newContents));

            // Merge header/footer fonts into the page's /Resources/Font.
            $fontDict = $this->getOrCreateFontDict($outRegistry, $pageDict);

            foreach ($compiledFontRefs as $localName => $ref) {
                $fontDict->set($localName, $ref);
            }
        }
    }

    /**
     * Reads the width and height of a page from its /MediaBox.
     *
     * @return array{float, float} [width, height] in points
     */
    private function readPageDimensions(PdfObjectRegistry $registry, PdfIndirectReference $pageRef): array
    {
        $pageDict = $registry->get($pageRef);

        if (!$pageDict instanceof PdfDictionary) {
            throw new RuntimeException(
                'Expected PdfDictionary for page reference ' . $pageRef->getObjectNumber() . '.',
            );
        }

        $mediaBox = $pageDict->get('MediaBox');

        if ($mediaBox instanceof PdfArray) {
            $items = $mediaBox->getItems();
            $x1 = $this->numValue($items[0] ?? null);
            $y1 = $this->numValue($items[1] ?? null);
            $x2 = $this->numValue($items[2] ?? null);
            $y2 = $this->numValue($items[3] ?? null);

            return [$x2 - $x1, $y2 - $y1];
        }

        return [595.28, 841.89];
    }

    /**
     * Navigates into /Resources /Font on $pageDict, creating sub-dicts when
     * absent, and returns the mutable Font sub-dictionary.
     *
     * Works whether /Resources is an inline PdfDictionary or an indirect
     * reference to one (the latter happens when importing external PDFs).
     */
    private function getOrCreateFontDict(PdfObjectRegistry $registry, PdfDictionary $pageDict): PdfDictionary
    {
        $resources = $pageDict->get('Resources');

        if ($resources instanceof PdfIndirectReference) {
            $resources = $registry->get($resources);
        }

        if (!$resources instanceof PdfDictionary) {
            $resources = new PdfDictionary();
            $pageDict->set('Resources', $resources);
        }

        $fontDict = $resources->get('Font');

        if ($fontDict instanceof PdfIndirectReference) {
            $fontDict = $registry->get($fontDict);
        }

        if (!$fontDict instanceof PdfDictionary) {
            $fontDict = new PdfDictionary();
            $resources->set('Font', $fontDict);
        }

        return $fontDict;
    }

    /**
     * Extracts a float value from a PdfInteger or PdfReal, returning 0.0 for
     * anything else.
     */
    private function numValue(?PdfObject $obj): float
    {
        if ($obj instanceof PdfInteger) {
            return (float) $obj->getValue();
        }

        if ($obj instanceof PdfReal) {
            return $obj->getValue();
        }

        return 0.0;
    }

    /**
     * Finds a named entry in a dictionary and asserts it is an indirect
     * reference.
     *
     * @throws \RuntimeException
     */
    private function requireDictRef(PdfObject $obj, string $key): PdfIndirectReference
    {
        if (!$obj instanceof PdfDictionary) {
            throw new RuntimeException('Expected PdfDictionary, got ' . $obj::class . '.');
        }

        $value = $obj->get($key);

        if ($value === null) {
            throw new RuntimeException("Required key '$key' not found in dictionary.");
        }

        if (!$value instanceof PdfIndirectReference) {
            throw new RuntimeException("Dictionary key '$key' is not a PdfIndirectReference.");
        }

        return $value;
    }
}
