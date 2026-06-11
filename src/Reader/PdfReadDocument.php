<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfObjectSource;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Serialization\PdfStreamSerializer;
use RuntimeException;
use Throwable;

/**
 * A parsed PDF document with lazy object loading.
 *
 * Objects are loaded from the file on first access and cached in memory.
 * Indirect references are resolved through the cross-reference table that was
 * built during parsing. This class does not hold the entire document in RAM;
 * only accessed objects are materialised.
 *
 * @phpstan-type XRefEntry array{offset?: int, generation?: int, streamObj?: int, index?: int, type: string}
 */
final class PdfReadDocument implements PdfObjectSource
{
    /** @var array<int, \PhpPdf\Object\PdfObject> */
    private array $cache = [];

    /** @var array<int, array<int, \PhpPdf\Object\PdfObject>> Parsed object streams: keyed by ObjStm object number, then by index */
    private array $objStreamCache = [];

    /** @param array<int, XRefEntry> $xref */
    public function __construct(
        private readonly PdfLexer $lexer,
        private readonly array $xref,
        private readonly PdfDictionary $trailer,
        private readonly PdfVersion $version,
        private readonly ?PdfEncryptionContext $decryptionContext = null,
        private readonly int $startXRefOffset = 0,
    ) {
    }

    public function getVersion(): PdfVersion
    {
        return $this->version;
    }

    public function getTrailer(): PdfDictionary
    {
        return $this->trailer;
    }

    public function getStartXRefOffset(): int
    {
        return $this->startXRefOffset;
    }

    public function getDecryptionContext(): ?PdfEncryptionContext
    {
        return $this->decryptionContext;
    }

    /** @return array<int, XRefEntry> */
    public function getXref(): array
    {
        return $this->xref;
    }

    /**
     * Resolves an indirect reference and returns the underlying PDF object.
     * Loads and caches the object from the file on first access.
     */
    public function getObject(PdfIndirectReference $reference): PdfObject
    {
        return $this->loadObject($reference->getObjectNumber());
    }

    /**
     * If $object is an indirect reference, resolves it; otherwise returns it as-is.
     */
    public function resolveObject(PdfObject $object): PdfObject
    {
        if ($object instanceof PdfIndirectReference) {
            return $this->getObject($object);
        }

        return $object;
    }

    /**
     * Returns the document catalog dictionary (/Root in the trailer).
     *
     * @throws \RuntimeException if the catalog cannot be located.
     */
    public function getCatalog(): PdfDictionary
    {
        $root = $this->getTrailerEntry('Root');

        if (!$root instanceof PdfIndirectReference) {
            throw new RuntimeException('PDF trailer does not contain a valid /Root entry');
        }

        $catalog = $this->getObject($root);

        if (!$catalog instanceof PdfDictionary) {
            throw new RuntimeException('PDF catalog is not a dictionary');
        }

        return $catalog;
    }

    /**
     * Returns the document information dictionary (/Info in the trailer), or null.
     */
    public function getInfo(): ?PdfDictionary
    {
        $info = $this->getTrailerEntry('Info');

        if (!$info instanceof PdfIndirectReference) {
            return null;
        }

        $resolved = $this->getObject($info);

        return $resolved instanceof PdfDictionary
            ? $resolved
            : null;
    }

    /** Returns the total number of pages in the document. */
    public function getPageCount(): int
    {
        return count($this->collectPages());
    }

    /**
     * Returns the page at the given zero-based index.
     *
     * @throws \PhpPdf\Reader\Exception\PdfReadException if the index is out of bounds.
     */
    public function getPage(int $index): PdfReadPage
    {
        $pages = $this->collectPages();
        $count = count($pages);

        if ($index < 0 || $index >= $count) {
            throw PdfReadException::pageIndexOutOfBounds($index, $count);
        }

        return new PdfReadPage($pages[$index], $this);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function loadObject(int $objectNumber): PdfObject
    {
        if (isset($this->cache[$objectNumber])) {
            return $this->cache[$objectNumber];
        }

        if (!isset($this->xref[$objectNumber])) {
            return new PdfNull();
        }

        $entry = $this->xref[$objectNumber];

        if ($entry['type'] === 's') {
            $streamObjNum = $entry['streamObj'] ?? 0;
            $indexInStream = $entry['index'] ?? 0;
            $object = $this->loadFromObjectStream($streamObjNum, $indexInStream);
            $this->cache[$objectNumber] = $object;

            return $object;
        }

        if ($entry['type'] !== 'n') {
            return new PdfNull();
        }

        $offset = $entry['offset'] ?? 0;
        $this->lexer->seekTo($offset);

        $parser = new PdfObjectParser($this->lexer, $this->decryptionContext, $objectNumber, $entry['generation'] ?? 0);
        $result = $parser->parseIndirectObject();

        if ($result === null) {
            return new PdfNull();
        }

        [, , $object] = $result;
        $this->cache[$objectNumber] = $object;

        return $object;
    }

    private function loadFromObjectStream(int $streamObjNum, int $indexInStream): PdfObject
    {
        if (isset($this->objStreamCache[$streamObjNum][$indexInStream])) {
            return $this->objStreamCache[$streamObjNum][$indexInStream];
        }

        $streamObj = $this->loadObject($streamObjNum);

        if (!$streamObj instanceof PdfStream) {
            return new PdfNull();
        }

        $dict = $streamObj->getDictionary();
        $n = ($nVal = $dict->get('N')) instanceof PdfInteger
            ? $nVal->getValue()
            : 0;
        $first = ($firstVal = $dict->get('First')) instanceof PdfInteger
            ? $firstVal->getValue()
            : 0;

        $content = $streamObj->getData()->serialize(new PdfStreamSerializer());

        // Parse the header: N pairs of (objectNumber, byteOffset).
        // ObjStm objects use the stream's own object number/generation for decryption (ISO 32000-1 §7.6.5).
        $headerLexer = PdfLexer::fromString(substr($content, 0, $first));
        $byteOffsets = [];

        for ($i = 0; $i < $n; $i++) {
            $numToken = $headerLexer->readToken();
            $offToken = $headerLexer->readToken();

            if ($numToken->type !== PdfTokenType::Integer || $offToken->type !== PdfTokenType::Integer) {
                break;
            }

            $byteOffsets[$i] = (int) $offToken->value;
        }

        // Parse each object from the stream body and cache the whole ObjStm at once.
        if (!isset($this->objStreamCache[$streamObjNum])) {
            $this->objStreamCache[$streamObjNum] = [];
        }

        foreach ($byteOffsets as $idx => $byteOffset) {
            $objLexer = PdfLexer::fromString(substr($content, $first + $byteOffset));
            $objParser = new PdfObjectParser(
                $objLexer,
                $this->decryptionContext,
                $streamObjNum, // ObjStm number used as the encryption object context per spec
                0, // ObjStm generation is always 0
            );

            try {
                $this->objStreamCache[$streamObjNum][$idx] = $objParser->parseObject();
            } catch (Throwable) {
                $this->objStreamCache[$streamObjNum][$idx] = new PdfNull();
            }
        }

        return $this->objStreamCache[$streamObjNum][$indexInStream] ?? new PdfNull();
    }

    /** @return list<\PhpPdf\Object\PdfDictionary> */
    private function collectPages(): array
    {
        $catalog = $this->getCatalog();
        $pagesRef = $this->getDictValue($catalog, 'Pages');

        if ($pagesRef === null) {
            return [];
        }

        $pagesDict = $this->resolveObject($pagesRef);

        if (!$pagesDict instanceof PdfDictionary) {
            return [];
        }

        $pages = [];
        $this->traversePageTree($pagesDict, $pages);

        return $pages;
    }

    /** @param list<\PhpPdf\Object\PdfDictionary> $pages */
    private function traversePageTree(PdfDictionary $node, array &$pages): void
    {
        $type = $this->getNameValue($node, 'Type');

        if ($type === 'Page') {
            $pages[] = $node;

            return;
        }

        $kids = $this->getDictValue($node, 'Kids');

        if ($kids === null) {
            return;
        }

        $kidsResolved = $this->resolveObject($kids);

        if (!$kidsResolved instanceof PdfArray) {
            return;
        }

        foreach ($kidsResolved->getItems() as $kidRef) {
            $kid = $this->resolveObject($kidRef);

            if (!($kid instanceof PdfDictionary)) {
                continue;
            }

            $this->traversePageTree($kid, $pages);
        }
    }

    private function getDictValue(PdfDictionary $dict, string $key): ?PdfObject
    {
        return $dict->get($key);
    }

    private function getNameValue(PdfDictionary $dict, string $key): ?string
    {
        $val = $this->getDictValue($dict, $key);

        return $val instanceof PdfName
            ? $val->getValue()
            : null;
    }

    private function getTrailerEntry(string $key): ?PdfObject
    {
        return $this->getDictValue($this->trailer, $key);
    }
}
