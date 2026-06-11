<?php

declare(strict_types=1);

namespace PhpPdf\Font;

use RuntimeException;
use Throwable;

/**
 * Produces a minimal TrueType font binary that contains only the glyphs
 * actually used in a document, plus .notdef (GID 0) and any composite
 * component glyphs they depend on.
 *
 * Supports TrueType-outline fonts (sfVersion 0x00010000) and TTC containers.
 * CFF/OpenType fonts (sfVersion 'OTTO') are not subset — the full program is
 * returned unchanged because CFF subsetting requires a separate CFF parser.
 *
 * Tables rebuilt for the subset:
 *   glyf — only the needed glyph outlines
 *   loca — rewritten to match the new glyf layout
 *   hmtx — trimmed to maxGID + 1 entries
 *   maxp — numGlyphs updated
 *   hhea — numberOfHMetrics updated
 *   head — indexToLocFormat updated, checkSumAdjustment recalculated
 *
 * Tables kept verbatim (if present):
 *   OS/2, post, name, cvt, prep, fpgm, kern, gasp
 *
 * The cmap table is intentionally omitted: PDF viewers use the ToUnicode CMap
 * in the PDF structure for text extraction; the font's own cmap is not needed
 * when the encoding is Identity-H + CIDToGIDMap=Identity.
 */
final class TrueTypeSubsetter
{
    private function __construct()
    {
    }

    /**
     * Returns subsetted font binary data.
     *
     * Falls back to returning the full font (just the relevant font from a TTC)
     * if the font is CFF-based or if anything goes wrong during subsetting.
     *
     * @param array<int,int> $usedGlyphs [glyphId => codePoint]
     */
    public static function subset(string $fontData, array $usedGlyphs, bool $isCff, int $fontIndex): string
    {
        if ($isCff) {
            return $fontData; // CFF subsetting not implemented; embed full font
        }

        try {
            return self::subsetTrueType($fontData, $usedGlyphs, $fontIndex);
        } catch (Throwable) {
            return $fontData; // Subsetting failed; embed full font as fallback
        }
    }

    // -------------------------------------------------------------------------

    /** @param array<int,int> $usedGlyphs [glyphId => codePoint] */
    private static function subsetTrueType(string $fontData, array $usedGlyphs, int $fontIndex): string
    {
        $fontOffset = self::resolveFontOffset($fontData, $fontIndex);
        $tables = self::parseTables($fontData, $fontOffset);

        foreach (['glyf', 'loca', 'head', 'hhea', 'hmtx', 'maxp'] as $req) {
            if (!isset($tables[$req])) {
                throw new RuntimeException("Required table '{$req}' not found.");
            }
        }

        // head: indexToLocFormat (offset 50 within the table)
        $headData = substr($fontData, $tables['head']['offset'], $tables['head']['length']);
        $indexToLocFormat = self::int16($headData, 50);

        // maxp: numGlyphs (offset 4)
        $numGlyphs = self::uint16($fontData, $tables['maxp']['offset'] + 4);

        // Parse loca → array of byte offsets into glyf
        $loca = self::parseLoca($fontData, $tables['loca']['offset'], $numGlyphs, $indexToLocFormat);
        $glyfRaw = substr($fontData, $tables['glyf']['offset'], $tables['glyf']['length']);

        // Build initial glyph set: always include .notdef (GID 0)
        $glyphSet = [0 => true];

        foreach (array_keys($usedGlyphs) as $gid) {
            if ($gid <= 0 || $gid >= $numGlyphs) {
                continue;
            }

            $glyphSet[$gid] = true;
        }

        // Expand: composite glyphs reference component glyphs that must also be included
        $glyphSet = self::expandComposites($glyfRaw, $loca, $glyphSet, $numGlyphs);

        $maxGid = max(0, ...array_keys($glyphSet));
        $newNumGlyphs = $maxGid + 1;

        // Rebuild glyf and loca
        [$newGlyf, $newLoca] = self::buildGlyfAndLoca($glyfRaw, $loca, $glyphSet, $maxGid);

        // Choose loca format: short if all offsets fit in uint16 × 2
        $useShortLoca = (end($newLoca) <= 0x1FFFE);
        $newLocaData = self::encodeLocaTable($newLoca, $useShortLoca);

        // Trim hmtx
        $numberOfHMetrics = self::uint16($fontData, $tables['hhea']['offset'] + 34);
        [$newHmtx, $newNumberOfHMetrics] = self::buildHmtx(
            $fontData,
            $tables['hmtx']['offset'],
            $numberOfHMetrics,
            $numGlyphs,
            $newNumGlyphs,
        );

        // Patch head: clear checkSumAdjustment, update indexToLocFormat
        $headData = self::patch32($headData, 8, 0);
        $headData = self::patch16($headData, 50, $useShortLoca ? 0 : 1);

        // Patch maxp: numGlyphs
        $maxpData = substr($fontData, $tables['maxp']['offset'], $tables['maxp']['length']);
        $maxpData = self::patch16($maxpData, 4, $newNumGlyphs);

        // Patch hhea: numberOfHMetrics
        $hheaData = substr($fontData, $tables['hhea']['offset'], $tables['hhea']['length']);
        $hheaData = self::patch16($hheaData, 34, $newNumberOfHMetrics);

        // Assemble table set
        $subset = [];
        $subset['glyf'] = $newGlyf;
        $subset['loca'] = $newLocaData;
        $subset['head'] = $headData;
        $subset['hhea'] = $hheaData;
        $subset['hmtx'] = $newHmtx;
        $subset['maxp'] = $maxpData;

        foreach (['OS/2', 'post', 'name', 'cvt ', 'prep', 'fpgm', 'kern', 'gasp'] as $tag) {
            if (!isset($tables[$tag])) {
                continue;
            }

            $subset[$tag] = substr($fontData, $tables[$tag]['offset'], $tables[$tag]['length']);
        }

        return self::buildFont($subset);
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    private static function resolveFontOffset(string $data, int $fontIndex): int
    {
        if (substr($data, 0, 4) === 'ttcf') {
            $numFonts = self::uint32($data, 8);

            if ($fontIndex >= $numFonts) {
                throw new RuntimeException("TTC font index {$fontIndex} out of range ({$numFonts} fonts).");
            }

            return self::uint32($data, 12 + $fontIndex * 4);
        }

        return 0;
    }

    /** @return array<string, array{offset:int, length:int}> */
    private static function parseTables(string $data, int $fontOffset): array
    {
        $numTables = self::uint16($data, $fontOffset + 4);
        $tables = [];

        for ($i = 0; $i < $numTables; $i++) {
            $pos = $fontOffset + 12 + $i * 16;
            $tag = substr($data, $pos, 4);
            $tables[$tag] = [
                'length' => self::uint32($data, $pos + 12),
                'offset' => self::uint32($data, $pos + 8),
            ];
        }

        return $tables;
    }

    /**
     * Parses the loca table into an array of byte offsets into glyf.
     *
     * @return array<int,int> index = glyphId (0 … numGlyphs), value = byte offset
     */
    private static function parseLoca(string $data, int $off, int $numGlyphs, int $format): array
    {
        $loca = [];

        if ($format === 0) {
            // Short format: stored value × 2 = byte offset
            for ($i = 0; $i <= $numGlyphs; $i++) {
                $loca[$i] = self::uint16($data, $off + $i * 2) * 2;
            }
        } else {
            // Long format: stored value is the byte offset
            for ($i = 0; $i <= $numGlyphs; $i++) {
                $loca[$i] = self::uint32($data, $off + $i * 4);
            }
        }

        return $loca;
    }

    // -------------------------------------------------------------------------
    // Composite glyph expansion
    // -------------------------------------------------------------------------

    /**
     * Adds component GIDs of composite glyphs to $glyphSet, iterating until
     * the closure is stable (handles transitive dependencies).
     *
     * @param array<int,bool> $glyphSet
     * @param array<int,int> $loca
     * @return array<int,bool>
     */
    private static function expandComposites(string $glyfRaw, array $loca, array $glyphSet, int $numGlyphs,): array
    {
        $glyfLen = strlen($glyfRaw);
        $changed = true;

        while ($changed) {
            $changed = false;

            foreach (array_keys($glyphSet) as $gid) {
                if ($gid >= $numGlyphs) {
                    continue;
                }

                $start = $loca[$gid] ?? null;
                $end = $loca[$gid + 1] ?? null;

                if ($start === null || $end === null || $end <= $start || $start >= $glyfLen) {
                    continue; // Empty or out-of-range glyph
                }

                // numberOfContours < 0 means composite glyph
                if (self::int16($glyfRaw, $start) >= 0) {
                    continue;
                }

                // Parse component records (each starts with flags + glyphIndex)
                $pos = $start + 10; // skip 5 × int16 glyph header

                do {
                    if ($pos + 4 > $glyfLen) {
                        break;
                    }

                    $flags = self::uint16($glyfRaw, $pos);
                    $compGid = self::uint16($glyfRaw, $pos + 2);
                    $pos += 4;

                    if ($compGid < $numGlyphs && !isset($glyphSet[$compGid])) {
                        $glyphSet[$compGid] = true;
                        $changed = true;
                    }

                    // Skip arg1 + arg2
                    $pos += $flags & 0x0001
                        ? 4
                        : 2; // ARG_1_AND_2_ARE_WORDS → 4 bytes else 2

                    // Skip optional transform
                    if ($flags & 0x0008) { // WE_HAVE_A_SCALE
                        $pos += 2;
                    } elseif ($flags & 0x0040) { // WE_HAVE_AN_X_AND_Y_SCALE
                        $pos += 4;
                    } elseif ($flags & 0x0080) { // WE_HAVE_A_TWO_BY_TWO
                        $pos += 8;
                    }
                } while ($flags & 0x0020); // MORE_COMPONENTS
            }
        }

        return $glyphSet;
    }

    // -------------------------------------------------------------------------
    // Table construction
    // -------------------------------------------------------------------------

    /**
     * Builds the new glyf binary and the matching loca offset array.
     *
     * GIDs in $glyphSet get their real outlines; all other GIDs from 0 to
     * $maxGid receive empty entries (loca[i] == loca[i+1]). Glyph data is
     * aligned to 4-byte boundaries as required by the TrueType spec.
     *
     * @param array<int,bool> $glyphSet
     * @param array<int,int> $loca     original loca offsets
     * @return array{string, array<int,int>} [newGlyfData, newLocaOffsets]
     */
    private static function buildGlyfAndLoca(string $glyfRaw, array $loca, array $glyphSet, int $maxGid,): array
    {
        $newGlyf = '';
        $newLoca = [];
        $glyfLen = strlen($glyfRaw);

        for ($gid = 0; $gid <= $maxGid; $gid++) {
            $newLoca[] = strlen($newGlyf); // start of this glyph = current write position

            if (!isset($glyphSet[$gid])) {
                continue; // Empty slot: loca[gid] == loca[gid+1]
            }

            $start = $loca[$gid] ?? null;
            $end = $loca[$gid + 1] ?? null;

            if ($start === null || $end === null || $end <= $start || $start >= $glyfLen) {
                continue; // Empty glyph outline
            }

            $len = min($end, $glyfLen) - $start;
            $glyphData = substr($glyfRaw, $start, $len);
            $pad = (4 - ($len % 4)) % 4;
            $newGlyf .= $glyphData . str_repeat("\x00", $pad);
        }

        $newLoca[] = strlen($newGlyf); // sentinel (loca has numGlyphs+1 entries)

        return [$newGlyf, $newLoca];
    }

    /**
     * Encodes the loca offset array into binary.
     *
     * Short format (indexToLocFormat=0): stored value = offset / 2 (uint16).
     * Long format (indexToLocFormat=1): stored value = offset (uint32).
     *
     * @param array<int,int> $loca
     */
    private static function encodeLocaTable(array $loca, bool $useShort): string
    {
        $data = '';

        if ($useShort) {
            foreach ($loca as $offset) {
                $data .= pack('n', (int)($offset / 2));
            }
        } else {
            foreach ($loca as $offset) {
                $data .= pack('N', $offset);
            }
        }

        return $data;
    }

    /**
     * Trims the hmtx table to $newNumGlyphs entries, preserving the original
     * structure (full longHorMetric records up to numberOfHMetrics, then
     * lsb-only records for remaining glyphs).
     *
     * Returns [$trimmedHmtxData, $newNumberOfHMetrics].
     *
     * @return array{string, int}
     */
    private static function buildHmtx(
        string $fontData,
        int $hmtxOff,
        int $numberOfHMetrics,
        int $numGlyphs,
        int $newNumGlyphs,
    ): array {
        $newNumberOfHMetrics = min($numberOfHMetrics, $newNumGlyphs);

        // Full longHorMetric records (4 bytes each: advanceWidth + lsb)
        $data = substr($fontData, $hmtxOff, $newNumberOfHMetrics * 4);

        // lsb-only records for glyphs beyond numberOfHMetrics (2 bytes each)
        if ($newNumGlyphs > $numberOfHMetrics) {
            $lsbBase = $hmtxOff + $numberOfHMetrics * 4;
            $lsbCount = min($newNumGlyphs - $numberOfHMetrics, $numGlyphs - $numberOfHMetrics);

            if ($lsbCount > 0) {
                $data .= substr($fontData, $lsbBase, $lsbCount * 2);
            }
        }

        return [$data, $newNumberOfHMetrics];
    }

    // -------------------------------------------------------------------------
    // Font assembly
    // -------------------------------------------------------------------------

    /**
     * Assembles a new TrueType font binary from a map of tag → table data.
     *
     * Tables are sorted alphabetically (required by spec), padded to 4-byte
     * boundaries, and checksummed. The head.checkSumAdjustment is set so the
     * whole-file checksum equals 0xB1B0AFBA.
     *
     * @param array<string, string> $tables
     */
    private static function buildFont(array $tables): string
    {
        ksort($tables); // TrueType spec requires alphabetical order

        $n = count($tables);

        if ($n === 0) {
            return '';
        }

        $log2 = (int) floor(log($n, 2));
        $searchRange = (1 << $log2) * 16;
        $entrySelector = $log2;
        $rangeShift = $n * 16 - $searchRange;

        $dataStart = 12 + $n * 16;

        // Pad each table's data to a 4-byte boundary and compute layout
        $padded = [];
        $offsets = [];
        $cursor = $dataStart;

        foreach ($tables as $tag => $data) {
            $pad = (4 - (strlen($data) % 4)) % 4;
            $padded[$tag] = $data . str_repeat("\x00", $pad);
            $offsets[$tag] = $cursor;
            $cursor += strlen($padded[$tag]);
        }

        // Offset table
        $result = pack('N', 0x00010000) // sfVersion: TrueType
                . pack('n', $n)
                . pack('n', $searchRange)
                . pack('n', $entrySelector)
                . pack('n', $rangeShift);

        // Table records
        foreach ($tables as $tag => $data) {
            $checksum = self::tableChecksum($padded[$tag]);
            $result .= pack('a4', $tag)
                     . pack('NNN', $checksum, $offsets[$tag], strlen($data));
        }

        // Table data
        foreach ($tables as $tag => $_) {
            $result .= $padded[$tag];
        }

        // Patch head.checkSumAdjustment (offset 8 within head table)
        if (isset($offsets['head'])) {
            $adjPos = $offsets['head'] + 8;
            $fileSum = self::fileChecksum($result);
            $adjustment = 0xB1B0AFBA - $fileSum & 0xFFFFFFFF;
            $result = substr($result, 0, $adjPos)
                        . pack('N', $adjustment)
                        . substr($result, $adjPos + 4);
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Checksums
    // -------------------------------------------------------------------------

    private static function tableChecksum(string $data): int
    {
        // Data is already padded to a 4-byte boundary
        $sum = 0;
        $len = strlen($data);

        for ($i = 0; $i + 4 <= $len; $i += 4) {
            $sum = $sum + self::uint32($data, $i) & 0xFFFFFFFF;
        }

        return $sum;
    }

    private static function fileChecksum(string $data): int
    {
        $sum = 0;
        $len = strlen($data);
        $full = (int)($len / 4);

        for ($i = 0; $i < $full; $i++) {
            $sum = $sum + self::uint32($data, $i * 4) & 0xFFFFFFFF;
        }

        $rem = $len % 4;

        if ($rem > 0) {
            $tail = substr($data, $full * 4) . str_repeat("\x00", 4 - $rem);
            $unpacked = unpack('N', $tail);
            $tailValue = $unpacked !== false && is_int($unpacked[1]) ? $unpacked[1] : 0;
            $sum = $sum + $tailValue & 0xFFFFFFFF;
        }

        return $sum;
    }

    // -------------------------------------------------------------------------
    // In-place patching helpers (return modified copy of $data)
    // -------------------------------------------------------------------------

    private static function patch16(string $data, int $offset, int $value): string
    {
        return substr($data, 0, $offset) . pack('n', $value) . substr($data, $offset + 2);
    }

    private static function patch32(string $data, int $offset, int $value): string
    {
        return substr($data, 0, $offset) . pack('N', $value) . substr($data, $offset + 4);
    }

    // -------------------------------------------------------------------------
    // Binary read helpers
    // -------------------------------------------------------------------------

    private static function uint16(string $d, int $o): int
    {
        $unpacked = unpack('n', substr($d, $o, 2));

        return $unpacked !== false && is_int($unpacked[1]) ? $unpacked[1] : 0;
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

        return $unpacked !== false && is_int($unpacked[1]) ? $unpacked[1] : 0;
    }
}
