<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Reader\Exception\PdfReadException;

/**
 * Low-level PDF tokenizer that reads from a file handle.
 *
 * Supports both token-based reading (for structured PDF objects) and raw byte
 * reading (for stream content). Maintains a lookahead token buffer so callers
 * can peek ahead without consuming tokens.
 */
final class PdfLexer
{
    /** @var resource */
    private $handle;
    private int $size;
    /** @var list<PdfToken> */
    private array $tokenBuffer = [];

    /** @param resource $handle */
    private function __construct($handle, int $size)
    {
        $this->handle = $handle;
        $this->size = $size;
    }

    public static function openFile(string $filePath): self
    {
        if (!is_file($filePath)) {
            throw PdfReadException::fileNotFound($filePath);
        }
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw PdfReadException::cannotOpenFile($filePath);
        }
        return new self($handle, (int) filesize($filePath));
    }

    public static function fromString(string $content): self
    {
        $handle = fopen('php://memory', 'r+b');
        if ($handle === false) {
            throw PdfReadException::cannotOpenFile('php://memory');
        }
        fwrite($handle, $content);
        fseek($handle, 0);
        return new self($handle, strlen($content));
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    public function position(): int
    {
        return (int) ftell($this->handle);
    }

    public function size(): int
    {
        return $this->size;
    }

    public function seekTo(int $offset): void
    {
        $this->tokenBuffer = [];
        fseek($this->handle, $offset);
    }

    /**
     * Reads exactly $length bytes from the current file position without tokenizing.
     * Clears the token buffer because raw I/O and token buffering are incompatible.
     */
    public function readRawBytes(int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        $this->tokenBuffer = [];
        $data = fread($this->handle, $length);
        return $data === false ? '' : $data;
    }

    /**
     * Reads $length bytes from $offset without changing the current position.
     */
    public function readBytesAt(int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }
        $saved = $this->position();
        fseek($this->handle, $offset);
        $data = fread($this->handle, $length);
        fseek($this->handle, $saved);
        return $data === false ? '' : $data;
    }

    /**
     * Skips to the end of the current line (consuming the line-ending bytes).
     * Used to advance past fixed-format headers before reading raw xref entries.
     */
    public function skipLine(): void
    {
        $this->tokenBuffer = [];
        while (true) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '' || $byte === "\n") {
                return;
            }
            if ($byte === "\r") {
                $next = fread($this->handle, 1);
                if ($next !== false && $next !== '' && $next !== "\n") {
                    fseek($this->handle, -1, SEEK_CUR);
                }
                return;
            }
        }
    }

    /**
     * Consumes the mandatory end-of-line marker (\\r\\n or \\n) that follows
     * the 'stream' keyword. Must be called before readRawBytes() for a stream.
     */
    public function consumeStreamNewline(): void
    {
        $this->tokenBuffer = [];
        $byte = fread($this->handle, 1);
        if ($byte === "\r") {
            $next = fread($this->handle, 1);
            if ($next !== false && $next !== '' && $next !== "\n") {
                fseek($this->handle, -1, SEEK_CUR);
            }
        } elseif ($byte !== false && $byte !== '' && $byte !== "\n") {
            fseek($this->handle, -1, SEEK_CUR);
        }
    }

    /** Reads the next token, consuming it from the stream. */
    public function readToken(): PdfToken
    {
        if ($this->tokenBuffer !== []) {
            return array_shift($this->tokenBuffer);
        }
        return $this->readNextToken();
    }

    /**
     * Peeks at a token without consuming it.
     * $ahead=1 peeks at the next token, $ahead=2 at the one after that, etc.
     */
    public function peekToken(int $ahead = 1): PdfToken
    {
        while (count($this->tokenBuffer) < $ahead) {
            $this->tokenBuffer[] = $this->readNextToken();
        }
        return $this->tokenBuffer[$ahead - 1];
    }

    /** Pushes a token back to the front of the buffer. */
    public function pushToken(PdfToken $token): void
    {
        array_unshift($this->tokenBuffer, $token);
    }

    /**
     * Scans backwards from the end of the file to locate the startxref offset.
     * Searches within the last 1 KiB per the PDF specification's recommendation.
     */
    public function findStartXRef(): int
    {
        $readSize = min(1024, $this->size);
        $offset = max(0, $this->size - $readSize);
        $chunk = $this->readBytesAt($offset, $readSize);

        $pos = strrpos($chunk, 'startxref');
        if ($pos === false) {
            throw PdfReadException::invalidXRef('startxref marker not found in last 1 KiB');
        }

        $after = substr($chunk, $pos + strlen('startxref'));
        if (!preg_match('/\s+(\d+)/', $after, $matches)) {
            throw PdfReadException::invalidXRef('integer offset not found after startxref');
        }

        return (int) $matches[1];
    }

    // -------------------------------------------------------------------------
    // Private tokenization
    // -------------------------------------------------------------------------

    private function readNextToken(): PdfToken
    {
        $this->skipWhitespaceAndComments();

        $byte = fread($this->handle, 1);
        if ($byte === false || $byte === '') {
            return new PdfToken(PdfTokenType::Eof, '');
        }

        if ($byte === '(') {
            return new PdfToken(PdfTokenType::String, $this->readLiteralString());
        }

        if ($byte === '<') {
            $next = fread($this->handle, 1);
            if ($next === '<') {
                return new PdfToken(PdfTokenType::DictStart, '<<');
            }
            if ($next !== false && $next !== '') {
                fseek($this->handle, -1, SEEK_CUR);
            }
            return new PdfToken(PdfTokenType::String, $this->readHexString());
        }

        if ($byte === '>') {
            $next = fread($this->handle, 1);
            if ($next === '>') {
                return new PdfToken(PdfTokenType::DictEnd, '>>');
            }
            if ($next !== false && $next !== '') {
                fseek($this->handle, -1, SEEK_CUR);
            }
            return new PdfToken(PdfTokenType::Keyword, '>');
        }

        if ($byte === '[') {
            return new PdfToken(PdfTokenType::ArrayStart, '[');
        }

        if ($byte === ']') {
            return new PdfToken(PdfTokenType::ArrayEnd, ']');
        }

        if ($byte === '/') {
            return new PdfToken(PdfTokenType::Name, $this->readName());
        }

        if ($byte === '+' || $byte === '-' || $byte === '.' || ctype_digit($byte)) {
            return $this->readNumber($byte);
        }

        if (ctype_alpha($byte) || $byte === '_' || $byte === "'" || $byte === '"') {
            return $this->readKeyword($byte);
        }

        return new PdfToken(PdfTokenType::Keyword, $byte);
    }

    private function skipWhitespaceAndComments(): void
    {
        while (true) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '') {
                return;
            }

            $ord = ord($byte);
            if ($ord === 0x00 || $ord === 0x09 || $ord === 0x0A || $ord === 0x0C || $ord === 0x0D || $ord === 0x20) {
                continue;
            }

            if ($byte === '%') {
                while (true) {
                    $c = fread($this->handle, 1);
                    if ($c === false || $c === '' || $c === "\r" || $c === "\n") {
                        break;
                    }
                }
                continue;
            }

            fseek($this->handle, -1, SEEK_CUR);
            return;
        }
    }

    private function readLiteralString(): string
    {
        $result = '';
        $depth = 1;

        while ($depth > 0) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '') {
                throw PdfReadException::unexpectedEndOfFile();
            }

            if ($byte === '\\') {
                $next = fread($this->handle, 1);
                if ($next === false || $next === '') {
                    break;
                }
                switch ($next) {
                    case 'n':
                        $result .= "\n";
                        break;
                    case 'r':
                        $result .= "\r";
                        break;
                    case 't':
                        $result .= "\t";
                        break;
                    case 'b':
                        $result .= "\x08";
                        break;
                    case 'f':
                        $result .= "\x0C";
                        break;
                    case '(':
                        $result .= '(';
                        break;
                    case ')':
                        $result .= ')';
                        break;
                    case '\\':
                        $result .= '\\';
                        break;
                    case "\r":
                        // Line continuation: skip optional LF
                        $maybeNL = fread($this->handle, 1);
                        if ($maybeNL !== false && $maybeNL !== '' && $maybeNL !== "\n") {
                            fseek($this->handle, -1, SEEK_CUR);
                        }
                        break;
                    case "\n":
                        // Line continuation
                        break;
                    default:
                        if (ctype_digit($next) && $next <= '7') {
                            $result .= $this->readOctalEscape($next);
                        } else {
                            $result .= $next;
                        }
                        break;
                }
                continue;
            }

            if ($byte === '(') {
                $depth++;
                $result .= '(';
            } elseif ($byte === ')') {
                $depth--;
                if ($depth > 0) {
                    $result .= ')';
                }
            } else {
                $result .= $byte;
            }
        }

        return $result;
    }

    private function readOctalEscape(string $first): string
    {
        $octal = $first;
        for ($i = 0; $i < 2; $i++) {
            $c = fread($this->handle, 1);
            if ($c === false || $c === '' || !ctype_digit($c) || $c > '7') {
                if ($c !== false && $c !== '') {
                    fseek($this->handle, -1, SEEK_CUR);
                }
                break;
            }
            $octal .= $c;
        }
        return chr((int) octdec($octal) & 0xFF);
    }

    private function readHexString(): string
    {
        $hex = '';
        while (true) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '') {
                throw PdfReadException::unexpectedEndOfFile();
            }
            if ($byte === '>') {
                break;
            }
            $ord = ord($byte);
            if ($ord === 0x00 || $ord === 0x09 || $ord === 0x0A || $ord === 0x0C || $ord === 0x0D || $ord === 0x20) {
                continue;
            }
            $hex .= $byte;
        }

        if (strlen($hex) % 2 !== 0) {
            $hex .= '0';
        }

        return hex2bin($hex) ?: '';
    }

    private function readName(): string
    {
        $name = '';
        while (true) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '') {
                break;
            }
            $ord = ord($byte);
            if ($ord === 0x00 || $ord === 0x09 || $ord === 0x0A || $ord === 0x0C || $ord === 0x0D || $ord === 0x20) {
                break;
            }
            if (
                $byte === '(' || $byte === ')' || $byte === '<' || $byte === '>'
                || $byte === '[' || $byte === ']' || $byte === '{' || $byte === '}'
                || $byte === '/' || $byte === '%'
            ) {
                fseek($this->handle, -1, SEEK_CUR);
                break;
            }
            if ($byte === '#') {
                $h1 = fread($this->handle, 1);
                $h2 = fread($this->handle, 1);
                if ($h1 !== false && $h2 !== false && $h1 !== '' && $h2 !== '') {
                    $name .= chr((int) hexdec($h1 . $h2) & 0xFF);
                }
            } else {
                $name .= $byte;
            }
        }
        return $name;
    }

    private function readNumber(string $first): PdfToken
    {
        $raw = $first;
        $isReal = ($first === '.');

        while (true) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '') {
                break;
            }
            if (ctype_digit($byte)) {
                $raw .= $byte;
            } elseif ($byte === '.' && !$isReal) {
                $isReal = true;
                $raw .= $byte;
            } elseif ($byte === 'e' || $byte === 'E') {
                $isReal = true;
                $raw .= $byte;
                $sign = fread($this->handle, 1);
                if ($sign !== false && $sign !== '' && ($sign === '+' || $sign === '-')) {
                    $raw .= $sign;
                } elseif ($sign !== false && $sign !== '') {
                    fseek($this->handle, -1, SEEK_CUR);
                }
            } else {
                fseek($this->handle, -1, SEEK_CUR);
                break;
            }
        }

        if ($raw === '+' || $raw === '-') {
            return new PdfToken(PdfTokenType::Keyword, $raw);
        }

        return $isReal
            ? new PdfToken(PdfTokenType::Real, $raw)
            : new PdfToken(PdfTokenType::Integer, $raw);
    }

    private function readKeyword(string $first): PdfToken
    {
        // Single-character operators ' and " are always standalone.
        if ($first === "'" || $first === '"') {
            return new PdfToken(PdfTokenType::Keyword, $first);
        }

        $word = $first;
        while (true) {
            $byte = fread($this->handle, 1);
            if ($byte === false || $byte === '') {
                break;
            }
            if (ctype_alnum($byte) || $byte === '*' || $byte === '_') {
                $word .= $byte;
            } else {
                fseek($this->handle, -1, SEEK_CUR);
                break;
            }
        }
        return new PdfToken(PdfTokenType::Keyword, $word);
    }
}
