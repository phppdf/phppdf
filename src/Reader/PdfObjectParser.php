<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Reader\Exception\PdfReadException;

/**
 * Parses PDF objects from a PdfLexer token stream.
 *
 * Produces standard PdfObject instances (the same types used during writing),
 * so parsed documents can be re-serialized or merged with new content.
 * Stream data is decoded eagerly using the /Filter chain so the stored bytes
 * are always raw (uncompressed) and the serializer can re-apply compression.
 */
final class PdfObjectParser
{
    public function __construct(
        private readonly PdfLexer $lexer,
        private readonly ?PdfEncryptionContext $decryptionContext = null,
        private readonly int $objectNumber = 0,
        private readonly int $generationNumber = 0,
    ) {
    }

    public function parseObject(): PdfObject
    {
        $token = $this->lexer->readToken();

        switch ($token->type) {
            case PdfTokenType::Integer:
                return $this->maybeIndirectReference($token);
            case PdfTokenType::Real:
                return new PdfReal((float) $token->value);
            case PdfTokenType::String:
                $value = $token->value;
                if ($this->decryptionContext !== null && $this->shouldDecrypt()) {
                    $value = $this->decryptionContext->decrypt(
                        $value,
                        $this->objectNumber,
                        $this->generationNumber,
                    );
                }
                return new PdfString($value);
            case PdfTokenType::Name:
                return new PdfName($token->value);
            case PdfTokenType::ArrayStart:
                return $this->parseArray();
            case PdfTokenType::DictStart:
                return $this->parseDictionaryOrStream();
            case PdfTokenType::Keyword:
                return $this->parseKeyword($token->value);
            default:
                throw PdfReadException::unexpectedToken('object', $token->value ?: 'EOF');
        }
    }

    /**
     * Parses an indirect object wrapper: `n g obj <value> endobj`.
     *
     * @return array{int, int, PdfObject}|null null at EOF
     */
    public function parseIndirectObject(): ?array
    {
        $t1 = $this->lexer->readToken();
        if ($t1->type === PdfTokenType::Eof) {
            return null;
        }
        if ($t1->type !== PdfTokenType::Integer) {
            throw PdfReadException::unexpectedToken('object number', $t1->value);
        }

        $t2 = $this->lexer->readToken();
        if ($t2->type !== PdfTokenType::Integer) {
            throw PdfReadException::unexpectedToken('generation number', $t2->value);
        }

        $tObj = $this->lexer->readToken();
        if ($tObj->type !== PdfTokenType::Keyword || $tObj->value !== 'obj') {
            throw PdfReadException::unexpectedToken('obj', $tObj->value);
        }

        $object = $this->parseObject();

        // Consume 'endobj'. For streams 'endstream' was consumed during parseStream()
        // so 'endobj' is the very next token. Be lenient about malformed files.
        $end = $this->lexer->readToken();
        if ($end->type !== PdfTokenType::Keyword || $end->value !== 'endobj') {
            // Not a fatal error — some PDF generators omit endobj.
        }

        return [(int) $t1->value, (int) $t2->value, $object];
    }

    // -------------------------------------------------------------------------
    // Private parsing helpers
    // -------------------------------------------------------------------------

    private function maybeIndirectReference(PdfToken $intToken): PdfObject
    {
        // Lookahead 2 tokens: if they are <integer> R then it's an indirect ref.
        $t2 = $this->lexer->peekToken(1);
        if ($t2->type !== PdfTokenType::Integer) {
            return new PdfInteger((int) $intToken->value);
        }

        $t3 = $this->lexer->peekToken(2);
        if ($t3->type !== PdfTokenType::Keyword || $t3->value !== 'R') {
            return new PdfInteger((int) $intToken->value);
        }

        $this->lexer->readToken(); // consume generation number
        $this->lexer->readToken(); // consume R

        return new PdfIndirectReference((int) $intToken->value, (int) $t2->value);
    }

    private function parseArray(): PdfArray
    {
        $items = [];
        while (true) {
            $peek = $this->lexer->peekToken(1);
            if ($peek->type === PdfTokenType::ArrayEnd) {
                $this->lexer->readToken();
                break;
            }
            if ($peek->type === PdfTokenType::Eof) {
                throw PdfReadException::unexpectedEndOfFile();
            }
            $items[] = $this->parseObject();
        }
        return new PdfArray($items);
    }

    private function parseDictionaryOrStream(): PdfDictionary|PdfStream
    {
        $dict = $this->parseDictionaryEntries();

        $peek = $this->lexer->peekToken(1);
        if ($peek->type === PdfTokenType::Keyword && $peek->value === 'stream') {
            $this->lexer->readToken(); // consume 'stream'
            return $this->parseStream($dict);
        }

        return $dict;
    }

    private function parseDictionaryEntries(): PdfDictionary
    {
        $entries = [];
        while (true) {
            $peek = $this->lexer->peekToken(1);
            if ($peek->type === PdfTokenType::DictEnd) {
                $this->lexer->readToken();
                break;
            }
            if ($peek->type === PdfTokenType::Eof) {
                throw PdfReadException::unexpectedEndOfFile();
            }

            $keyToken = $this->lexer->readToken();
            if ($keyToken->type !== PdfTokenType::Name) {
                throw PdfReadException::unexpectedToken('name key', $keyToken->value);
            }

            $value = $this->parseObject();
            $entries[$keyToken->value] = $value;
        }
        return new PdfDictionary($entries);
    }

    private function parseStream(PdfDictionary $dict): PdfStream
    {
        // The PDF spec mandates either \r\n or \n immediately after 'stream'.
        $this->lexer->consumeStreamNewline();

        $length = $this->getStreamLength($dict);
        $rawData = $this->readStreamContent($length);

        // Consume 'endstream' (skipWhitespace handles any \r\n before it).
        $endToken = $this->lexer->readToken();
        if ($endToken->type !== PdfTokenType::Keyword || $endToken->value !== 'endstream') {
            // Lenient: some generators have quirks around endstream placement.
        }

        // Per spec §7.6.5: decrypt BEFORE decompressing (writer compresses then encrypts).
        if ($this->decryptionContext !== null && $this->shouldDecrypt()) {
            $rawData = $this->decryptionContext->decrypt(
                $rawData,
                $this->objectNumber,
                $this->generationNumber,
            );
        }

        $decoded = $this->decodeStreamData($rawData, $dict);
        $cleanDict = $this->stripStreamFilters($dict);

        return new PdfStream($cleanDict, new PdfRawStreamData($decoded));
    }

    private function getStreamLength(PdfDictionary $dict): int
    {
        $val = $dict->get('Length');
        if ($val === null) {
            return 0;
        }
        if ($val instanceof PdfInteger) {
            return $val->getValue();
        }
        // Indirect reference to length: can't resolve here, use fallback.
        return 0;
    }

    private function readStreamContent(int $length): string
    {
        if ($length > 0) {
            return $this->lexer->readRawBytes($length);
        }

        // Fallback: scan byte-by-byte until 'endstream'.
        // This is slow but handles the rare case of an indirect /Length reference.
        $data = '';
        $marker = 'endstream';
        $markerLen = strlen($marker);

        while (true) {
            $byte = $this->lexer->readRawBytes(1);
            if ($byte === '') {
                break;
            }
            $data .= $byte;
            if (str_ends_with($data, $marker)) {
                $data = rtrim(substr($data, 0, -$markerLen), "\r\n");
                // Push a synthetic token so the caller's readToken() still sees 'endstream'.
                $this->lexer->pushToken(new PdfToken(PdfTokenType::Keyword, 'endstream'));
                break;
            }
        }

        return $data;
    }

    private function decodeStreamData(string $data, PdfDictionary $dict): string
    {
        $filter = $this->getDictEntry($dict, 'Filter');
        if ($filter === null) {
            return $data;
        }

        $filters = $filter instanceof PdfArray ? $filter->getItems() : [$filter];

        foreach ($filters as $f) {
            if (!$f instanceof PdfName) {
                continue;
            }
            $data = $this->applyFilter($data, $f->getValue());
        }

        return $data;
    }

    private function applyFilter(string $data, string $filterName): string
    {
        switch ($filterName) {
            case 'FlateDecode':
                $decoded = @gzuncompress($data);
                if ($decoded === false) {
                    // Try raw deflate without the zlib wrapper.
                    $decoded = @gzinflate($data);
                }
                if ($decoded === false) {
                    throw PdfReadException::streamDecodeFailed($filterName);
                }
                return $decoded;

            case 'ASCIIHexDecode':
                $hex = preg_replace('/\s+/', '', $data);
                $hex = rtrim($hex ?? '', '>');
                if (strlen($hex) % 2 !== 0) {
                    $hex .= '0';
                }
                return hex2bin($hex) ?: '';

            case 'ASCII85Decode':
                return $this->decodeAscii85($data);

            default:
                // Unknown or unsupported filter: return as-is and let the caller decide.
                return $data;
        }
    }

    private function decodeAscii85(string $data): string
    {
        $data = rtrim($data, "~> \r\n");
        $result = '';
        $group = '';

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $c = $data[$i];
            if ($c === 'z') {
                $result .= "\x00\x00\x00\x00";
                continue;
            }
            $ord = ord($c);
            if ($ord < 33 || $ord > 117) {
                continue; // whitespace or out-of-range: skip
            }
            $group .= $c;
            if (strlen($group) === 5) {
                $value = 0;
                for ($j = 0; $j < 5; $j++) {
                    $value = $value * 85 + (ord($group[$j]) - 33);
                }
                $result .= pack('N', $value);
                $group = '';
            }
        }

        if ($group !== '') {
            $padded = str_pad($group, 5, 'u');
            $value = 0;
            for ($j = 0; $j < 5; $j++) {
                $value = $value * 85 + (ord($padded[$j]) - 33);
            }
            $result .= substr(pack('N', $value), 0, strlen($group) - 1);
        }

        return $result;
    }

    private function stripStreamFilters(PdfDictionary $dict): PdfDictionary
    {
        $strip = ['Filter', 'DecodeParms', 'Length'];
        $entries = array_filter(
            $dict->getEntries(),
            static fn ($value, $key) => !in_array($key, $strip, true),
            ARRAY_FILTER_USE_BOTH,
        );
        return new PdfDictionary($entries);
    }

    private function getDictEntry(PdfDictionary $dict, string $key): ?PdfObject
    {
        return $dict->get($key);
    }

    private function shouldDecrypt(): bool
    {
        return $this->decryptionContext !== null
            && $this->decryptionContext->shouldEncryptObject($this->objectNumber);
    }

    private function parseKeyword(string $keyword): PdfObject
    {
        return match ($keyword) {
            'true' => new PdfBoolean(true),
            'false' => new PdfBoolean(false),
            'null' => new PdfNull(),
            default => throw PdfReadException::unexpectedToken('object value', $keyword),
        };
    }
}
