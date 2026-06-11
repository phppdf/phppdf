<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfStream;
use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * Extracts plain text from the pages of a parsed PDF document.
 *
 * Text extraction is inherently imprecise for PDFs that use custom font
 * encodings, complex layout, or glyph substitution. This extractor handles:
 * - Type0 (CID) fonts via their ToUnicode CMap (covers embedded TrueType)
 * - Simple fonts (Type1, TrueType) via WinAnsiEncoding / Latin-1
 * - Standard text showing operators: Tj, TJ, ', "
 * - Line-break heuristics from Td, TD, Tm, T*
 */
final class PdfTextExtractor
{
    public function __construct(private readonly PdfReadDocument $document)
    {
    }

    /**
     * Returns the plain text of the page at the given zero-based index.
     */
    public function getTextForPage(int $pageIndex): string
    {
        $page = $this->document->getPage($pageIndex);
        $resources = $page->getResources();
        $fontCmaps = $this->loadFontCmaps($resources);

        $state = new PdfTextExtractionState();

        foreach ($page->getContentStreams() as $stream) {
            $this->processContentStream($stream, $state, $fontCmaps);
        }

        return trim($state->getText());
    }

    // -------------------------------------------------------------------------
    // Font / CMap loading
    // -------------------------------------------------------------------------

    /**
     * Returns a map of PDF font resource name → font info array.
     *
     * @return array<string, array{type: string, cmap: array<int, string>|null}>
     */
    private function loadFontCmaps(PdfDictionary $resources): array
    {
        $fontEntry = $this->getDictValue($resources, 'Font');
        if ($fontEntry === null) {
            return [];
        }

        $fontDict = $this->document->resolveObject($fontEntry);
        if (!$fontDict instanceof PdfDictionary) {
            return [];
        }

        $result = [];

        foreach ($fontDict->getEntries() as $fontName => $fontRef) {
            $fontObj = $this->document->resolveObject($fontRef);

            if (!$fontObj instanceof PdfDictionary) {
                continue;
            }

            $subtype = $this->getNameValue($fontObj, 'Subtype');

            if ($subtype === 'Type0') {
                $cmap = $this->loadToUnicodeCmap($fontObj);
                $result[$fontName] = ['type' => 'type0', 'cmap' => $cmap];
            } else {
                // Type1, TrueType (non-embedded), Type3, etc. — use encoding heuristic.
                $result[$fontName] = ['type' => 'simple', 'cmap' => null];
            }
        }

        return $result;
    }

    /** @return array<int, string>|null */
    private function loadToUnicodeCmap(PdfDictionary $fontDict): ?array
    {
        $tuRef = $this->getDictValue($fontDict, 'ToUnicode');
        if ($tuRef === null) {
            return null;
        }

        $stream = $this->document->resolveObject($tuRef);
        if (!$stream instanceof PdfStream) {
            return null;
        }

        $content = $stream->getData()->serialize(new PdfStreamSerializer());
        return $this->parseCmapContent($content);
    }

    /**
     * Parses the text content of a ToUnicode CMap stream and returns a mapping
     * from integer character codes to UTF-8 strings.
     *
     * @return array<int, string>
     */
    private function parseCmapContent(string $content): array
    {
        $mappings = [];

        // beginbfchar...endbfchar: one-to-one mappings.
        if (preg_match_all('/beginbfchar\s+(.*?)\s*endbfchar/s', $content, $sections)) {
            foreach ($sections[1] as $section) {
                preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $section, $pairs, PREG_SET_ORDER);
                foreach ($pairs as $pair) {
                    $code = (int) hexdec($pair[1]);
                    $unicode = mb_convert_encoding(hex2bin($pair[2]) ?: '', 'UTF-8', 'UTF-16BE');
                    $mappings[$code] = $unicode;
                }
            }
        }

        // beginbfrange...endbfrange: contiguous ranges.
        if (preg_match_all('/beginbfrange\s+(.*?)\s*endbfrange/s', $content, $sections)) {
            foreach ($sections[1] as $section) {
                preg_match_all(
                    '/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/',
                    $section,
                    $ranges,
                    PREG_SET_ORDER,
                );
                foreach ($ranges as $range) {
                    $startCode = (int) hexdec($range[1]);
                    $endCode = (int) hexdec($range[2]);
                    $startUnicode = (int) hexdec($range[3]);
                    for ($code = $startCode; $code <= $endCode; $code++) {
                        $char = mb_chr($startUnicode + ($code - $startCode), 'UTF-8');
                        if ($char !== false) {
                            $mappings[$code] = $char;
                        }
                    }
                }
            }
        }

        return $mappings;
    }

    // -------------------------------------------------------------------------
    // Content stream processing
    // -------------------------------------------------------------------------

    /**
     * @param array<string, array{type: string, cmap: array<int, string>|null}> $fontCmaps
     */
    private function processContentStream(
        string $content,
        PdfTextExtractionState $state,
        array $fontCmaps,
    ): void {
        $tokens = $this->tokenizeContentStream($content);
        $operands = [];

        foreach ($tokens as $token) {
            $type = $token[0];

            if ($type === 'array_start') {
                $operands[] = ['array_start', null];
                continue;
            }

            if ($type === 'array_end') {
                $items = [];
                while (!empty($operands) && $operands[count($operands) - 1][0] !== 'array_start') {
                    array_unshift($items, array_pop($operands));
                }
                if (!empty($operands)) {
                    array_pop($operands); // remove 'array_start' sentinel
                }
                $operands[] = ['array', $items];
                continue;
            }

            if ($type !== 'op') {
                $operands[] = $token;
                continue;
            }

            $op = $token[1];

            switch ($op) {
                case 'BT':
                    $state->setInText(true);
                    $state->setTextMatrix([1, 0, 0, 1, 0, 0]);
                    break;

                case 'ET':
                    $state->setInText(false);
                    break;

                case 'Tf':
                    if (count($operands) >= 1 && $operands[0][0] === 'name') {
                        $state->setCurrentFont($this->tokenString($operands[0]));
                    }
                    break;

                case 'Tj':
                    if ($state->isInText() && count($operands) >= 1) {
                        $last = end($operands);
                        if ($last !== false && $last[0] === 'string') {
                            $str = $this->tokenString($last);
                            $state->appendText($this->decodeString($str, $state->getCurrentFont(), $fontCmaps));
                        }
                    }
                    break;

                case 'TJ':
                    if ($state->isInText() && count($operands) >= 1) {
                        $last = end($operands);
                        if ($last !== false && $last[0] === 'array' && is_array($last[1])) {
                            foreach ($last[1] as $item) {
                                if (!is_array($item) || !isset($item[0])) {
                                    continue;
                                }
                                if ($item[0] === 'string') {
                                    $str = $this->tokenString($item);
                                    $state->appendText($this->decodeString(
                                        $str,
                                        $state->getCurrentFont(),
                                        $fontCmaps,
                                    ));
                                } elseif (
                                    ($item[0] === 'integer' || $item[0] === 'real')
                                    && $this->tokenNumber($item) < -200
                                ) {
                                    // Large negative kerning = word break.
                                    $state->appendText(' ');
                                }
                            }
                        }
                    }
                    break;

                case "'":
                    // Move to next line and show text.
                    $state->appendText("\n");
                    if ($state->isInText() && count($operands) >= 1) {
                        $last = end($operands);
                        if ($last !== false && $last[0] === 'string') {
                            $str = $this->tokenString($last);
                            $state->appendText($this->decodeString($str, $state->getCurrentFont(), $fontCmaps));
                        }
                    }
                    break;

                case '"':
                    // Set spacing, move to next line, show text.
                    $state->appendText("\n");
                    if ($state->isInText() && count($operands) >= 3) {
                        $str = $operands[2];
                        if ($str[0] === 'string') {
                            $decoded = $this->tokenString($str);
                            $state->appendText($this->decodeString($decoded, $state->getCurrentFont(), $fontCmaps));
                        }
                    }
                    break;

                case 'Td':
                case 'TD':
                    // Text position move: insert newline when y changes.
                    if ($state->isInText() && count($operands) >= 2) {
                        $ty = $this->tokenNumber($operands[1]);
                        if ($ty < -0.5) {
                            $state->appendText("\n");
                        } elseif (abs($ty) > 0.5) {
                            $state->appendText("\n");
                        }
                    }
                    break;

                case 'Tm':
                    // Full text matrix: detect line changes via y component.
                    if ($state->isInText() && count($operands) >= 6) {
                        $newY  = $this->tokenNumber($operands[5]);
                        $prevY = $state->getTextMatrix()[5];
                        if (abs($newY - $prevY) > 0.5) {
                            $state->appendText("\n");
                        }
                        $state->setTextMatrix([
                            $this->tokenNumber($operands[0]),
                            $this->tokenNumber($operands[1]),
                            $this->tokenNumber($operands[2]),
                            $this->tokenNumber($operands[3]),
                            $this->tokenNumber($operands[4]),
                            $newY,
                        ]);
                    }
                    break;

                case 'T*':
                    if ($state->isInText()) {
                        $state->appendText("\n");
                    }
                    break;
            }

            $operands = [];
        }
    }

    /**
     * @param array<mixed> $token
     */
    private function tokenString(array $token): string
    {
        return isset($token[1]) && is_string($token[1]) ? $token[1] : '';
    }

    /**
     * @param array<mixed> $token
     */
    private function tokenNumber(array $token): float
    {
        $value = $token[1] ?? null;
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        return 0.0;
    }

    // -------------------------------------------------------------------------
    // Content stream tokenizer (string-based, not file-based)
    // -------------------------------------------------------------------------

    /**
     * Tokenizes a content stream string into a flat list of typed tokens.
     * Each token is [type, value] where type is one of:
     * 'string', 'name', 'integer', 'real', 'op', 'array_start', 'array_end'.
     *
     * @return list<array{string, mixed}>
     */
    private function tokenizeContentStream(string $content): array
    {
        $tokens = [];
        $len = strlen($content);
        $i = 0;

        while ($i < $len) {
            $c = $content[$i];

            // Whitespace
            if ($c === ' ' || $c === "\t" || $c === "\r" || $c === "\n" || $c === "\x00" || $c === "\x0C") {
                $i++;
                continue;
            }

            // Comment
            if ($c === '%') {
                while ($i < $len && $content[$i] !== "\n" && $content[$i] !== "\r") {
                    $i++;
                }
                continue;
            }

            // Literal string
            if ($c === '(') {
                [$str, $i] = $this->readLiteralStringAt($content, $i + 1);
                $tokens[] = ['string', $str];
                continue;
            }

            // Hex string or dict start
            if ($c === '<') {
                if ($i + 1 < $len && $content[$i + 1] === '<') {
                    $tokens[] = ['dict_start', '<<'];
                    $i += 2;
                } else {
                    [$str, $i] = $this->readHexStringAt($content, $i + 1);
                    $tokens[] = ['string', $str];
                }
                continue;
            }

            // Dict end
            if ($c === '>' && $i + 1 < $len && $content[$i + 1] === '>') {
                $tokens[] = ['dict_end', '>>'];
                $i += 2;
                continue;
            }

            // Array delimiters
            if ($c === '[') {
                $tokens[] = ['array_start', '['];
                $i++;
                continue;
            }
            if ($c === ']') {
                $tokens[] = ['array_end', ']'];
                $i++;
                continue;
            }

            // Name
            if ($c === '/') {
                [$name, $i] = $this->readNameAt($content, $i + 1, $len);
                $tokens[] = ['name', $name];
                continue;
            }

            // Single-character operators
            if ($c === "'" || $c === '"') {
                $tokens[] = ['op', $c];
                $i++;
                continue;
            }

            // Number or keyword
            $start = $i;
            while ($i < $len) {
                $ch = $content[$i];
                if (
                    $ch === ' ' || $ch === "\t" || $ch === "\r" || $ch === "\n"
                    || $ch === '(' || $ch === ')' || $ch === '<' || $ch === '>'
                    || $ch === '[' || $ch === ']' || $ch === '{' || $ch === '}'
                    || $ch === '/' || $ch === '%'
                ) {
                    break;
                }
                $i++;
            }

            $word = substr($content, $start, $i - $start);
            if ($word === '') {
                $i++;
                continue;
            }

            if (is_numeric($word)) {
                if (str_contains($word, '.') || str_contains($word, 'e') || str_contains($word, 'E')) {
                    $tokens[] = ['real', (float) $word];
                } else {
                    $tokens[] = ['integer', (int) $word];
                }
            } else {
                $tokens[] = ['op', $word];
            }
        }

        return $tokens;
    }

    /**
     * @return array{string, int} [decoded string, next position after closing ')']
     */
    private function readLiteralStringAt(string $content, int $i): array
    {
        $result = '';
        $depth = 1;
        $len = strlen($content);

        while ($i < $len && $depth > 0) {
            $c = $content[$i];

            if ($c === '\\' && $i + 1 < $len) {
                $next = $content[$i + 1];
                $i += 2;
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
                        if ($i < $len && $content[$i] === "\n") {
                            $i++;
                        }
                        break;
                    case "\n":
                        break;
                    default:
                        if ($next >= '0' && $next <= '7') {
                            $octal = $next;
                            for ($j = 0; $j < 2 && $i < $len && $content[$i] >= '0' && $content[$i] <= '7'; $j++) {
                                $octal .= $content[$i++];
                            }
                            $result .= chr((int) octdec($octal) & 0xFF);
                        } else {
                            $result .= $next;
                        }
                }
                continue;
            }

            if ($c === '(') {
                $depth++;
                $result .= '(';
            } elseif ($c === ')') {
                $depth--;
                if ($depth > 0) {
                    $result .= ')';
                }
            } else {
                $result .= $c;
            }
            $i++;
        }

        return [$result, $i];
    }

    /**
     * @return array{string, int} [decoded bytes, next position after closing '>']
     */
    private function readHexStringAt(string $content, int $i): array
    {
        $hex = '';
        $len = strlen($content);

        while ($i < $len && $content[$i] !== '>') {
            $c = $content[$i++];
            $ord = ord($c);
            if ($ord === 0x20 || $ord === 0x09 || $ord === 0x0A || $ord === 0x0C || $ord === 0x0D) {
                continue;
            }
            $hex .= $c;
        }
        $i++; // skip '>'

        if (strlen($hex) % 2 !== 0) {
            $hex .= '0';
        }

        return [hex2bin($hex) ?: '', $i];
    }

    /**
     * @return array{string, int} [name string (without /), next position]
     */
    private function readNameAt(string $content, int $i, int $len): array
    {
        $name = '';
        while ($i < $len) {
            $c = $content[$i];
            $ord = ord($c);
            if (
                $ord <= 0x20 || $c === '(' || $c === ')' || $c === '<' || $c === '>'
                || $c === '[' || $c === ']' || $c === '{' || $c === '}' || $c === '/' || $c === '%'
            ) {
                break;
            }
            if ($c === '#' && $i + 2 < $len) {
                $name .= chr((int) hexdec($content[$i + 1] . $content[$i + 2]) & 0xFF);
                $i += 3;
            } else {
                $name .= $c;
                $i++;
            }
        }
        return [$name, $i];
    }

    // -------------------------------------------------------------------------
    // String decoding
    // -------------------------------------------------------------------------

    /**
     * @param array<string, array{type: string, cmap: array<int, string>|null}> $fontCmaps
     */
    private function decodeString(string $str, ?string $fontName, array $fontCmaps): string
    {
        if ($fontName !== null && isset($fontCmaps[$fontName])) {
            $font = $fontCmaps[$fontName];
            if ($font['type'] === 'type0' && $font['cmap'] !== null) {
                return $this->decodeWithCmap($str, $font['cmap']);
            }
        }

        // Fallback: treat as WinAnsi / Latin-1.
        return $this->decodeSimpleEncoding($str);
    }

    /** @param array<int, string> $cmap */
    private function decodeWithCmap(string $str, array $cmap): string
    {
        $result = '';
        $len = strlen($str);

        for ($i = 0; $i + 1 < $len; $i += 2) {
            $code = (ord($str[$i]) << 8) | ord($str[$i + 1]);
            $result .= $cmap[$code] ?? '';
        }

        // Odd final byte (shouldn't happen for Type0 but be safe).
        if ($len % 2 !== 0) {
            $code = ord($str[$len - 1]);
            $result .= $cmap[$code] ?? '';
        }

        return $result;
    }

    private function decodeSimpleEncoding(string $str): string
    {
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        return mb_convert_encoding($str, 'UTF-8', 'Windows-1252');
    }

    // -------------------------------------------------------------------------
    // Dict helpers
    // -------------------------------------------------------------------------

    private function getDictValue(PdfDictionary $dict, string $key): ?PdfObject
    {
        return $dict->get($key);
    }

    private function getNameValue(PdfDictionary $dict, string $key): ?string
    {
        $val = $this->getDictValue($dict, $key);
        return $val instanceof PdfName ? $val->getValue() : null;
    }
}
