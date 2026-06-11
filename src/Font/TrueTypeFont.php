<?php

declare(strict_types=1);

namespace PhpPdf\Font;

use RuntimeException;

/**
 * Parses a TrueType (.ttf), OpenType (.otf), or TrueType Collection (.ttc)
 * font file and exposes the data needed to embed it in a PDF as a composite
 * (Type0 / CIDFont) font.
 *
 * Supports:
 *   - cmap format 4 (BMP Unicode, used by most Latin and CJK fonts)
 *   - cmap format 12 (full Unicode, preferred when present)
 *   - TTC containers (via the $fontIndex parameter)
 *   - TrueType outlines (sfVersion 0x00010000) → CIDFontType2 / FontFile2
 *   - OpenType/CFF outlines (sfVersion 'OTTO') → CIDFontType0 / FontFile3
 *
 * Usage:
 *   $font = TrueTypeFont::fromFile('/usr/share/fonts/truetype/noto/NotoSansMono-Regular.ttf');
 *   $font = TrueTypeFont::fromFile('/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc', 0);
 */
final class TrueTypeFont
{
    private string $fontName;
    private int $unitsPerEm;
    private int $numGlyphs;
    private int $fontIndex = 0;

    /** @var array<int,int> codePoint → glyphId */
    private array $cmap;

    /** @var array<int,int> glyphId → advance width (font units) */
    private array $advanceWidths;

    private int $ascent;
    private int $descent;
    private int $capHeight;
    private float $italicAngle;
    private int $xMin;
    private int $yMin;
    private int $xMax;
    private int $yMax;
    private int $flags;
    private int $stemV;
    private bool $isCff;

    private function __construct(private readonly string $rawData,)
    {
    }

    public static function fromFile(string $path, int $fontIndex = 0): self
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Font file not readable: {$path}");
        }

        $data = file_get_contents($path);

        if ($data === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Failed to read font file: {$path}");
            // @codeCoverageIgnoreEnd
        }

        return self::fromData($data, $fontIndex);
    }

    public static function fromData(string $data, int $fontIndex = 0): self
    {
        $font = new self($data);
        $font->fontIndex = $fontIndex;
        $font->parse($data, $fontIndex);

        return $font;
    }

    // -------------------------------------------------------------------------
    // Public API used by PdfPageBuilder
    // -------------------------------------------------------------------------

    public function getFontName(): string
    {
        return $this->fontName;
    }

    public function getUnitsPerEm(): int
    {
        return $this->unitsPerEm;
    }

    public function getAscent(): int
    {
        return $this->ascent;
    }

    public function getDescent(): int
    {
        return $this->descent;
    }

    public function getCapHeight(): int
    {
        return $this->capHeight;
    }

    public function getItalicAngle(): float
    {
        return $this->italicAngle;
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} */
    public function getFontBBox(): array
    {
        return [$this->xMin, $this->yMin, $this->xMax, $this->yMax];
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getStemV(): int
    {
        return $this->stemV;
    }

    public function getRawData(): string
    {
        return $this->rawData;
    }

    public function isCff(): bool
    {
        return $this->isCff;
    }

    /**
     * Returns a minimal font binary containing only the glyphs in $usedGlyphs,
     * plus .notdef and any composite component glyphs they depend on.
     *
     * For TrueType-outline fonts this rebuilds glyf, loca, hmtx, maxp, hhea,
     * and head. For CFF/OpenType fonts the full program is returned unchanged
     * because CFF subsetting is not yet implemented.
     *
     * @param array<int,int> $usedGlyphs [glyphId => codePoint]
     */
    public function subset(array $usedGlyphs): string
    {
        return TrueTypeSubsetter::subset($this->rawData, $usedGlyphs, $this->isCff, $this->fontIndex);
    }

    /** Looks up the glyph ID for a Unicode code point. Returns 0 (missing glyph) if not found. */
    public function getGlyphId(int $codePoint): int
    {
        return $this->cmap[$codePoint] ?? 0;
    }

    /** Returns the advance width of a glyph in font units. */
    public function getAdvanceWidth(int $glyphId): int
    {
        return $this->advanceWidths[$glyphId] ?? ($this->advanceWidths[0] ?? 0);
    }

    /**
     * Converts a font-unit value to PDF glyph space units (1/1000 of the text
     * size unit, so that the result can be used directly in a PDF font dict).
     */
    public function toPdfUnits(int $fontUnits): int
    {
        return (int) round($fontUnits * 1000 / $this->unitsPerEm);
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    private function parse(string $data, int $fontIndex): void
    {
        // TrueType Collection: magic = 'ttcf'
        $offset = 0;

        if (substr($data, 0, 4) === 'ttcf') {
            $numFonts = self::uint32($data, 8);

            if ($fontIndex >= $numFonts) {
                throw new RuntimeException("Font index {$fontIndex} out of range (TTC has {$numFonts} fonts).");
            }

            $offset = self::uint32($data, 12 + $fontIndex * 4);
        }

        $sfVersion = substr($data, $offset, 4);
        $this->isCff = ($sfVersion === 'OTTO');

        $numTables = self::uint16($data, $offset + 4);

        $tables = [];

        for ($i = 0; $i < $numTables; $i++) {
            $pos = $offset + 12 + $i * 16;
            $tag = substr($data, $pos, 4);
            $toff = self::uint32($data, $pos + 8);
            $tlen = self::uint32($data, $pos + 12);
            $tables[$tag] = ['offset' => $toff, 'length' => $tlen];
        }

        $this->parseHead($data, $tables['head']['offset']);
        $this->numGlyphs = self::uint16($data, $tables['maxp']['offset'] + 4);

        $numberOfHMetrics = self::uint16($data, $tables['hhea']['offset'] + 34);
        $this->advanceWidths = $this->parseHmtx($data, $tables['hmtx']['offset'], $numberOfHMetrics);

        $this->cmap = $this->parseCmap($data, $tables['cmap']['offset']);

        $this->stemV = 80; // default; parseOs2 may raise this to 120 for bold fonts
        $this->parseOs2($data, $tables['OS/2']['offset']);

        $this->italicAngle = isset($tables['post'])
            ? self::int32($data, $tables['post']['offset'] + 4) / 65536.0
            : 0.0;

        $this->fontName = isset($tables['name'])
            ? $this->parseName($data, $tables['name']['offset'])
            : 'UnknownFont';

        $isItalic = abs($this->italicAngle) > 0.1;

        $this->flags = 32; // Nonsymbolic

        if (!$isItalic) {
            return;
        }

        $this->flags |= 0x40;
    }

    private function parseHead(string $data, int $off): void
    {
        $this->unitsPerEm = self::uint16($data, $off + 18);
        $this->xMin = self::int16($data, $off + 36);
        $this->yMin = self::int16($data, $off + 38);
        $this->xMax = self::int16($data, $off + 40);
        $this->yMax = self::int16($data, $off + 42);
    }

    private function parseOs2(string $data, int $off): void
    {
        $version = self::uint16($data, $off);
        $this->ascent = self::int16($data, $off + 68);
        $this->descent = self::int16($data, $off + 70);

        if ($version >= 2 && strlen($data) > $off + 92) {
            $cap = self::int16($data, $off + 90);
            $this->capHeight = $cap > 0
                ? $cap
                : (int) ($this->ascent * 0.7);
        } else {
            $this->capHeight = (int) ($this->ascent * 0.7);
        }

        // Bold flag from usWeightClass (offset 4)
        $weight = self::uint16($data, $off + 4);

        if ($weight < 700) {
            return;
        }

        $this->stemV = 120;
    }

    /** @return array<int, int> */
    private function parseHmtx(string $data, int $off, int $numberOfHMetrics): array
    {
        $widths = [];
        $last = 0;

        for ($i = 0; $i < $numberOfHMetrics; $i++) {
            $last = self::uint16($data, $off + $i * 4);
            $widths[$i] = $last;
        }

        // Remaining glyphs inherit the last recorded advance width.
        for ($i = $numberOfHMetrics; $i < $this->numGlyphs; $i++) {
            $widths[$i] = $last;
        }

        return $widths;
    }

    /** @return array<int, int> */
    private function parseCmap(string $data, int $cmapOff): array
    {
        $numSubtables = self::uint16($data, $cmapOff + 2);

        // Collect candidate subtables ordered by preference.
        $format12Offset = null;
        $format4Offset = null;

        for ($i = 0; $i < $numSubtables; $i++) {
            $pos = $cmapOff + 4 + $i * 8;
            $platId = self::uint16($data, $pos);
            $encId = self::uint16($data, $pos + 2);
            $relOff = self::uint32($data, $pos + 4);
            $absOff = $cmapOff + $relOff;
            $fmt = self::uint16($data, $absOff);

            if ($fmt === 12 && $platId === 3 && $encId === 10 && $format12Offset === null) {
                $format12Offset = $absOff;
            }

            if ($fmt !== 4 || $platId !== 3 || $encId !== 1 || $format4Offset !== null) {
                continue;
            }

            $format4Offset = $absOff;
        }

        if ($format12Offset !== null) {
            return $this->parseCmapFormat12($data, $format12Offset);
        }

        if ($format4Offset !== null) {
            return $this->parseCmapFormat4($data, $format4Offset);
        }

        return [];
    }

    /** @return array<int, int> */
    private function parseCmapFormat4(string $data, int $base): array
    {
        $segCount = self::uint16($data, $base + 6) >> 1;

        $endBase = $base + 14;
        $startBase = $endBase + $segCount * 2 + 2; // +2 = reservedPad
        $deltaBase = $startBase + $segCount * 2;
        $rangeBase = $deltaBase + $segCount * 2;

        $cmap = [];

        for ($seg = 0; $seg < $segCount; $seg++) {
            $end = self::uint16($data, $endBase + $seg * 2);
            $start = self::uint16($data, $startBase + $seg * 2);
            $delta = self::int16($data, $deltaBase + $seg * 2);
            $range = self::uint16($data, $rangeBase + $seg * 2);

            if ($end === 0xFFFF) {
                break;
            }

            for ($cp = $start; $cp <= $end; $cp++) {
                if ($range === 0) {
                    $gid = $cp + $delta & 0xFFFF;
                } else {
                    $gidPos = $rangeBase + $seg * 2 + $range + ($cp - $start) * 2;
                    $gid = self::uint16($data, $gidPos);

                    if ($gid !== 0) {
                        $gid = $gid + $delta & 0xFFFF;
                    }
                }

                if ($gid === 0) {
                    continue;
                }

                $cmap[$cp] = $gid;
            }
        }

        return $cmap;
    }

    /** @return array<int, int> */
    private function parseCmapFormat12(string $data, int $base): array
    {
        $nGroups = self::uint32($data, $base + 12);
        $cmap = [];

        for ($i = 0; $i < $nGroups; $i++) {
            $pos = $base + 16 + $i * 12;
            $start = self::uint32($data, $pos);
            $end = self::uint32($data, $pos + 4);
            $gid = self::uint32($data, $pos + 8);

            for ($cp = $start; $cp <= $end; $cp++) {
                $cmap[$cp] = $gid + $cp - $start;
            }
        }

        return $cmap;
    }

    private function parseName(string $data, int $off): string
    {
        $count = self::uint16($data, $off + 2);
        $strOff = self::uint16($data, $off + 4);
        $psName = '';
        $fullName = '';

        for ($i = 0; $i < $count; $i++) {
            $pos = $off + 6 + $i * 12;
            $platId = self::uint16($data, $pos);
            $encId = self::uint16($data, $pos + 2);
            $nameId = self::uint16($data, $pos + 6);
            $len = self::uint16($data, $pos + 8);
            $nOff = self::uint16($data, $pos + 10);

            if ($nameId !== 6 && $nameId !== 4) {
                continue;
            }

            $raw = substr($data, $off + $strOff + $nOff, $len);

            if ($platId === 3 && $encId === 1) {
                $decoded = mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');

                if ($nameId === 6) {
                    $psName = $decoded;
                } elseif ($fullName === '') {
                    $fullName = $decoded;
                }
            } elseif ($platId === 1 && $psName === '' && $fullName === '') {
                if ($nameId === 6) {
                    $psName = $raw;
                }

                if ($nameId === 4) {
                    $fullName = $raw;
                }
            }
        }

        $name = $psName !== ''
            ? $psName
            : ($fullName !== '' ? $fullName : 'Font');

        // PDF names may not contain whitespace.
        return preg_replace('/\s+/', '-', $name) ?? $name;
    }

    // -------------------------------------------------------------------------
    // Binary helpers
    // -------------------------------------------------------------------------

    private static function uint16(string $d, int $o): int
    {
        $unpacked = unpack('n', substr($d, $o, 2));

        if ($unpacked === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to unpack uint16.');
            // @codeCoverageIgnoreEnd
        }

        $value = $unpacked[1];

        if (!is_int($value)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to unpack uint16.');
            // @codeCoverageIgnoreEnd
        }

        return $value;
    }

    private static function int16(string $d, int $o): int
    {
        $v = self::uint16($d, $o);

        return $v >= 0x8000
            ? $v - 0x10000
            : $v;
    }

    private static function uint32(string $d, int $o): int
    {
        $unpacked = unpack('N', substr($d, $o, 4));

        if ($unpacked === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to unpack uint32.');
            // @codeCoverageIgnoreEnd
        }

        $value = $unpacked[1];

        if (!is_int($value)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to unpack uint32.');
            // @codeCoverageIgnoreEnd
        }

        return $value;
    }

    private static function int32(string $d, int $o): int
    {
        $v = self::uint32($d, $o);

        return $v >= 0x80000000
            ? $v - 0x100000000
            : $v;
    }
}
