<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfStream;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * Parses a traditional PDF cross-reference table and its trailer.
 *
 * Handles multi-subsection xref tables and follows the Prev chain for
 * incremental updates. Later (more-recent) entries take precedence over
 * earlier ones, matching the PDF specification's update semantics.
 *
 * PDF 1.5+ xref streams are detected but not yet supported.
 *
 * @phpstan-type XRefEntry array{offset?: int, generation?: int, streamObj?: int, index?: int, type: string}
 */
final class PdfXRefTable
{
    public function __construct(private readonly PdfLexer $lexer)
    {
    }

    /**
     * Parses the xref table(s) starting at $startXRefOffset.
     *
     * @param int $startXRefOffset byte offset returned by PdfLexer::findStartXRef()
     * @return array{array<int, XRefEntry>, \PhpPdf\Object\PdfDictionary}
     *         [xref entries indexed by object number, trailer dictionary]
     */
    public function parse(int $startXRefOffset): array
    {
        $allEntries = [];
        $firstTrailer = null;
        $visited = [];

        $currentOffset = $startXRefOffset;

        while (true) {
            if (in_array($currentOffset, $visited, true)) {
                break; // Guard against malformed circular Prev chains.
            }

            $visited[] = $currentOffset;

            $this->lexer->seekTo($currentOffset);
            [$entries, $trailer] = $this->parseOneSection();

            // Earlier entries (older revisions) do NOT override newer ones.
            foreach ($entries as $objNum => $entry) {
                if (isset($allEntries[$objNum])) {
                    continue;
                }

                $allEntries[$objNum] = $entry;
            }

            if ($firstTrailer === null) {
                $firstTrailer = $trailer;
            }

            $prev = $this->getIntEntry($trailer, 'Prev');

            if ($prev === null) {
                break;
            }

            $currentOffset = $prev;
        }

        return [$allEntries, $firstTrailer ?? new PdfDictionary([])];
    }

    // -------------------------------------------------------------------------

    /** @return array{array<int, XRefEntry>, \PhpPdf\Object\PdfDictionary} */
    private function parseOneSection(): array
    {
        $first = $this->lexer->readToken();

        // PDF 1.5+ uses a compressed xref stream instead of a plain 'xref' table.
        // That stream starts with an indirect object header: `n g obj`.
        if ($first->type === PdfTokenType::Integer) {
            $this->lexer->pushToken($first);

            return $this->parseXRefStreamSection();
        }

        if ($first->type !== PdfTokenType::Keyword || $first->value !== 'xref') {
            throw PdfReadException::invalidXRef("expected 'xref', got '{$first->value}'");
        }

        $entries = [];

        while (true) {
            $peek = $this->lexer->peekToken(1);

            if ($peek->type === PdfTokenType::Keyword && $peek->value === 'trailer') {
                $this->lexer->readToken(); // consume 'trailer'

                break;
            }

            if ($peek->type === PdfTokenType::Eof) {
                throw PdfReadException::unexpectedEndOfFile();
            }

            [$startObj, $count] = $this->parseSubsectionHeader();
            $subsection = $this->parseSubsectionEntries($startObj, $count);

            foreach ($subsection as $objNum => $entry) {
                $entries[$objNum] = $entry;
            }
        }

        $parser = new PdfObjectParser($this->lexer);
        $trailer = $parser->parseObject();

        if (!$trailer instanceof PdfDictionary) {
            throw PdfReadException::invalidXRef('trailer value is not a dictionary');
        }

        return [$entries, $trailer];
    }

    /** @return array{array<int, XRefEntry>, \PhpPdf\Object\PdfDictionary} */
    private function parseXRefStreamSection(): array
    {
        $parser = new PdfObjectParser($this->lexer);
        $parsed = $parser->parseIndirectObject();
        // parseIndirectObject is always called after the caller pushes the
        // xref stream's object-number token back; null return is impossible here.
        if ($parsed === null) {
            throw PdfReadException::unexpectedEndOfFile();
        }
        [, , $object] = $parsed;

        if (!$object instanceof PdfStream) {
            throw PdfReadException::invalidXRef('xref stream object is not a stream');
        }

        $dict = $object->getDictionary();
        $entries = $this->parseXRefStreamEntries($object, $dict);

        return [$entries, $dict];
    }

    /** @return array<int, XRefEntry> */
    private function parseXRefStreamEntries(PdfStream $stream, PdfDictionary $dict): array
    {
        $data = $stream->getData()->serialize(new PdfStreamSerializer());

        $wObj = $dict->get('W');

        if (!$wObj instanceof PdfArray) {
            throw PdfReadException::invalidXRef('/W array missing in xref stream');
        }

        $wItems = $wObj->getItems();
        $w = [
            isset($wItems[0]) && $wItems[0] instanceof PdfInteger ? $wItems[0]->getValue() : 0,
            isset($wItems[1]) && $wItems[1] instanceof PdfInteger ? $wItems[1]->getValue() : 0,
            isset($wItems[2]) && $wItems[2] instanceof PdfInteger ? $wItems[2]->getValue() : 0,
        ];
        $entrySize = $w[0] + $w[1] + $w[2];

        if ($entrySize === 0) {
            return [];
        }

        $sizeObj = $dict->get('Size');
        $size = $sizeObj instanceof PdfInteger
            ? $sizeObj->getValue()
            : 0;

        $indexRanges = [];
        $indexObj = $dict->get('Index');

        if ($indexObj instanceof PdfArray) {
            $indexItems = $indexObj->getItems();

            for ($i = 0; $i + 1 < count($indexItems); $i += 2) {
                $start = $indexItems[$i] instanceof PdfInteger
                    ? $indexItems[$i]->getValue()
                    : 0;
                $count = $indexItems[$i + 1] instanceof PdfInteger
                        ? $indexItems[$i + 1]->getValue()
                        : 0;
                $indexRanges[] = [$start, $count];
            }
        } else {
            $indexRanges[] = [0, $size];
        }

        $entries = [];
        $pos = 0;
        $dataLen = strlen($data);

        foreach ($indexRanges as [$startObj, $count]) {
            for ($i = 0; $i < $count; $i++) {
                if ($pos + $entrySize > $dataLen) {
                    break;
                }

                $f1 = $this->readUint($data, $pos, $w[0]);
                $f2 = $this->readUint($data, $pos + $w[0], $w[1]);
                $f3 = $this->readUint($data, $pos + $w[0] + $w[1], $w[2]);
                $pos += $entrySize;

                $type = $w[0] === 0
                    ? 1
                    : $f1;
                $objNum = $startObj + $i;

                if ($type === 1) {
                    $entries[$objNum] = ['offset' => $f2, 'generation' => $f3, 'type' => 'n'];
                } elseif ($type === 2) {
                    $entries[$objNum] = ['streamObj' => $f2, 'index' => $f3, 'type' => 's'];
                }
                // type 0 = free — skip
            }
        }

        return $entries;
    }

    private function readUint(string $data, int $offset, int $width): int
    {
        if ($width === 0) {
            return 0;
        }

        $value = 0;

        for ($i = 0; $i < $width; $i++) {
            $value = ($value << 8) | ord($data[$offset + $i]);
        }

        return $value;
    }

    /** @return array{int, int} [startObjectNumber, count] */
    private function parseSubsectionHeader(): array
    {
        $startToken = $this->lexer->readToken();
        $countToken = $this->lexer->readToken();

        if ($startToken->type !== PdfTokenType::Integer) {
            throw PdfReadException::invalidXRef('expected start object number in subsection header');
        }

        if ($countToken->type !== PdfTokenType::Integer) {
            throw PdfReadException::invalidXRef('expected entry count in subsection header');
        }

        return [(int) $startToken->value, (int) $countToken->value];
    }

    /**
     * Reads $count fixed-width (20-byte) xref entries starting at object $startObj.
     *
     * @return array<int, array{offset: int, generation: int, type: string}>
     */
    private function parseSubsectionEntries(int $startObj, int $count): array
    {
        // Skip the EOL after the subsection header — the token reader already consumed
        // the count integer but not the trailing end-of-line.
        $this->lexer->skipLine();

        $rawData = $this->lexer->readRawBytes($count * 20);
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            $entry = substr($rawData, $i * 20, 20);

            if (strlen($entry) < 18) {
                break; // Truncated file.
            }

            $offset = (int) substr($entry, 0, 10);
            $generation = (int) substr($entry, 11, 5);
            $type = $entry[17]; // 'n' (in-use) or 'f' (free)

            if ($type !== 'n') {
                continue;
            }

            $entries[$startObj + $i] = [
                'generation' => $generation,
                'offset' => $offset,
                'type' => 'n',
            ];
        }

        return $entries;
    }

    private function getIntEntry(PdfDictionary $dict, string $key): ?int
    {
        $val = $dict->get($key);

        if ($val instanceof PdfInteger) {
            return $val->getValue();
        }

        return null;
    }
}
