<?php

declare(strict_types=1);

namespace PhpPdf\Font;

/**
 * Builds minimal TrueType / OpenType font binaries for unit testing.
 *
 * Only the fields actually read by TrueTypeFont and TrueTypeSubsetter are
 * populated; all other bytes are zeroed.
 */
final class MinimalFontBuilder
{
    // -------------------------------------------------------------------------
    // Option helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a minimal TrueType font with every table that TrueTypeFont and
     * TrueTypeSubsetter need.
     *
     * @param array<string, mixed> $opts
     */
    public static function build(array $opts = []): string
    {
        $unitsPerEm = self::optInt($opts, 'unitsPerEm', 1000);
        $numGlyphs = self::optInt($opts, 'numGlyphs', 3);
        $ascent = self::optInt($opts, 'ascent', 800);
        $descent = self::optInt($opts, 'descent', -200);
        $capHeight = self::optInt($opts, 'capHeight', 0);
        $weight = self::optInt($opts, 'weight', 400);
        $italicAngle = self::optFloat($opts, 'italicAngle', 0.0);
        $os2Version = self::optInt($opts, 'os2Version', 4);
        $fontName = self::optString($opts, 'fontName', 'TestFont');
        $includeName = self::optBool($opts, 'includeName', true);
        $includePost = self::optBool($opts, 'includePost', true);
        $includeGlyf = self::optBool($opts, 'includeGlyf', true);
        $cmapFormat = self::optInt($opts, 'cmapFormat', 4);
        $locaFormat = self::optInt($opts, 'locaFormat', 0); // 0=short, 1=long
        $compositeGlyph = self::optBool($opts, 'compositeGlyph', false);
        $compositeFlags = self::optInt($opts, 'compositeFlags', 0x0000);

        $tables = [];

        // head
        $tables['head'] = self::buildHead($unitsPerEm, $ascent, $locaFormat);

        // maxp
        $tables['maxp'] = self::buildMaxp($numGlyphs);

        // hhea
        $tables['hhea'] = self::buildHhea($ascent, $descent, $numGlyphs);

        // hmtx
        $tables['hmtx'] = self::buildHmtx($numGlyphs, $unitsPerEm);

        // cmap
        $tables['cmap'] = self::buildCmapForFormat($cmapFormat);

        // OS/2
        $tables['OS/2'] = self::buildOs2($os2Version, $weight, $ascent, $descent, $capHeight);

        // optional post
        if ($includePost) {
            $tables['post'] = self::buildPost($italicAngle);
        }

        // optional name
        if ($includeName) {
            $tables['name'] = self::buildNameTable($fontName);
        }

        // optional glyf + loca
        if ($includeGlyf) {
            [$glyf, $loca] = self::buildGlyfAndLoca($numGlyphs, $compositeGlyph, $compositeFlags, $locaFormat);
            $tables['glyf'] = $glyf;
            $tables['loca'] = $loca;
        }

        return self::assembleFont($tables);
    }

    /**
     * Builds a TTC container wrapping a minimal sub-font.
     *
     * @param array<string, bool|int> $opts
     */
    public static function buildTtc(array $opts = [], int $numFontsInHeader = 1): string
    {
        $subFontOffset = 16; // TTC header = 16 bytes
        $subFont = self::assembleFont(
            self::getDefaultTables($opts),
            "\x00\x01\x00\x00",
            $subFontOffset,
        );

        // TTC header: 'ttcf' + majorVersion(2) + minorVersion(2) + numFonts(4) + [offset0](4)
        $ttcHeader = 'ttcf'
                   . pack('nn', 2, 0)
                   . pack('N', $numFontsInHeader)
                   . pack('N', $subFontOffset);

        return $ttcHeader . $subFont;
    }

    /**
     * Builds a minimal CFF/OpenType font (sfVersion='OTTO').
     *
     * @param array<string, bool|int> $opts
     */
    public static function buildCff(array $opts = []): string
    {
        return self::assembleFont(self::getDefaultTables($opts), 'OTTO');
    }

    // -------------------------------------------------------------------------
    // Table builders
    // -------------------------------------------------------------------------

    /**
     * Builds an hmtx where numberOfHMetrics < numGlyphs, so lsb-only records
     * exist (exercises the buildHmtx branch in TrueTypeSubsetter).
     */
    public static function buildHmtxSparse(int $numberOfHMetrics, int $numGlyphs, int $advanceWidth = 500): string
    {
        $data = '';

        // Full records up to numberOfHMetrics
        for ($i = 0; $i < $numberOfHMetrics; $i++) {
            $data .= pack('n', $advanceWidth) . pack('n', 0);
        }

        // lsb-only records for remaining glyphs
        for ($i = $numberOfHMetrics; $i < $numGlyphs; $i++) {
            $data .= pack('n', 0); // lsb only (2 bytes each)
        }

        return $data;
    }

    /**
     * Name table with ONLY platId=1 entries (no platId=3).
     * Tests the fallback branch in parseName().
     */
    public static function buildPlatform1NameTable(string $psName = '', string $fullName = ''): string
    {
        $records = [];
        $strings = '';
        $offset = 0;

        if ($psName !== '') {
            $records[] = [1, 0, 0, 6, strlen($psName), $offset];
            $strings .= $psName;
            $offset += strlen($psName);
        }

        if ($fullName !== '') {
            $records[] = [1, 0, 0, 4, strlen($fullName), $offset];
            $strings .= $fullName;
        }

        $count = count($records);
        $stringsOffset = 6 + $count * 12;

        $data = pack('n', 0) . pack('n', $count) . pack('n', $stringsOffset);

        foreach ($records as [$platId, $encId, $langId, $nameId, $len, $off]) {
            $data .= pack('nnnnnn', $platId, $encId, $langId, $nameId, $len, $off);
        }

        return $data . $strings;
    }

    /** Name table that has no nameId=4 or nameId=6 entries → parseName returns 'Font'. */
    public static function buildNameTableWithOnlyOtherIds(): string
    {
        $utf16 = mb_convert_encoding('Creator', 'UTF-16BE', 'UTF-8');
        $len = strlen($utf16);
        $stringsOffset = 6 + 12;

        return pack('n', 0)
             . pack('n', 1)
             . pack('n', $stringsOffset)
             . pack('nnnnnn', 3, 1, 0, 8, $len, 0) // nameId=8 (manufacturer), not 4 or 6
             . $utf16;
    }

    /**
     * Builds a complete font binary using default tables from $opts but replaces
     * the 'name' table with the given $nameTableData.
     *
     * This is a test helper for exercising the parseName() branches in TrueTypeFont.
     *
     * @param array<string, bool|int> $opts
     */
    public static function buildWithCustomNameTable(string $nameTableData, array $opts = []): string
    {
        $opts['includeName'] = false; // will be overridden below
        $unitsPerEm = self::optInt($opts, 'unitsPerEm', 1000);
        $numGlyphs = self::optInt($opts, 'numGlyphs', 3);
        $ascent = self::optInt($opts, 'ascent', 800);
        $descent = self::optInt($opts, 'descent', -200);
        $locaFormat = self::optInt($opts, 'locaFormat', 0);

        [$glyf, $loca] = self::buildGlyfAndLoca($numGlyphs, false, 0, $locaFormat);

        $tables = [
            'cmap' => self::buildCmapWithFormat4([0x41 => 1, 0x42 => 2]),
            'glyf' => $glyf,
            'head' => self::buildHead($unitsPerEm, $ascent, $locaFormat),
            'hhea' => self::buildHhea($ascent, $descent, $numGlyphs),
            'hmtx' => self::buildHmtx($numGlyphs, $unitsPerEm),
            'loca' => $loca,
            'maxp' => self::buildMaxp($numGlyphs),
            'name' => $nameTableData,
            'OS/2' => self::buildOs2(4, 400, $ascent, $descent, 0),
            'post' => self::buildPost(0.0),
        ];

        return self::assembleFont($tables);
    }

    /**
     * Builds a complete font binary using default tables from $opts but replaces
     * the 'cmap' table with the given $cmapData.
     *
     * This is a test helper for exercising parseCmap() branches in TrueTypeFont.
     *
     * @param array<string, bool|int> $opts
     */
    public static function buildWithCustomCmap(string $cmapData, array $opts = []): string
    {
        $unitsPerEm = self::optInt($opts, 'unitsPerEm', 1000);
        $numGlyphs = self::optInt($opts, 'numGlyphs', 5); // ≥4 so GID 3 is valid
        $ascent = self::optInt($opts, 'ascent', 800);
        $descent = self::optInt($opts, 'descent', -200);
        $locaFormat = self::optInt($opts, 'locaFormat', 0);

        [$glyf, $loca] = self::buildGlyfAndLoca($numGlyphs, false, 0, $locaFormat);

        $tables = [
            'cmap' => $cmapData,
            'glyf' => $glyf,
            'head' => self::buildHead($unitsPerEm, $ascent, $locaFormat),
            'hhea' => self::buildHhea($ascent, $descent, $numGlyphs),
            'hmtx' => self::buildHmtx($numGlyphs, $unitsPerEm),
            'loca' => $loca,
            'maxp' => self::buildMaxp($numGlyphs),
            'name' => self::buildNameTable('TestFont'),
            'OS/2' => self::buildOs2(4, 400, $ascent, $descent, 0),
            'post' => self::buildPost(0.0),
        ];

        return self::assembleFont($tables);
    }

    // -------------------------------------------------------------------------
    // cmap builders
    // -------------------------------------------------------------------------

    /**
     * Builds a cmap table containing a single format-4 subtable (platId=3,encId=1).
     *
     * @param array<int,int> $codeToGlyph code point → glyph ID
     */
    public static function buildCmapWithFormat4(array $codeToGlyph): string
    {
        $subtable = self::buildFormat4Subtable($codeToGlyph);

        return pack('n', 0) // version
             . pack('n', 1) // numSubtables
             . pack('n', 3) // platformId = 3
             . pack('n', 1) // encodingId = 1
             . pack('N', 12) // offset from start of cmap = 12 (4-byte header + 8-byte record)
             . $subtable;
    }

    /**
     * Builds a cmap table containing a format-12 subtable (platId=3,encId=10).
     *
     * @param array<int,int> $codeToGlyph
     */
    public static function buildCmapWithFormat12(array $codeToGlyph): string
    {
        $subtable = self::buildFormat12Subtable($codeToGlyph);

        return pack('n', 0) // version
             . pack('n', 1) // numSubtables
             . pack('n', 3) // platformId = 3
             . pack('n', 10) // encodingId = 10
             . pack('N', 12) // offset
             . $subtable;
    }

    /**
     * Builds a cmap with a subtable that has no platId=3/encId=1 or platId=3/encId=10 →
     * parseCmap() returns [].
     */
    public static function buildEmptyCmap(): string
    {
        $subtable = self::buildFormat4Subtable([]);

        return pack('n', 0)
             . pack('n', 1)
             . pack('n', 1) // platformId = 1 (Mac), not matched
             . pack('n', 0)
             . pack('N', 12)
             . $subtable;
    }

    /**
     * Builds a format-4 subtable. Uses direct-delta segments (idRangeOffset=0)
     * for each code point, plus the required 0xFFFF sentinel.
     *
     * @param array<int,int> $codeToGlyph
     */
    public static function buildFormat4Subtable(array $codeToGlyph): string
    {
        // One segment per code point (all direct-delta), plus sentinel
        $segments = [];

        foreach ($codeToGlyph as $cp => $gid) {
            $segments[] = [
                'delta' => $gid - $cp & 0xFFFF,
                'end' => $cp,
                'range' => 0,
                'start' => $cp,
            ];
        }

        // Sentinel
        $segments[] = ['start' => 0xFFFF, 'end' => 0xFFFF, 'delta' => 1, 'range' => 0];

        $segCount = count($segments);
        $segCountX2 = $segCount * 2;
        $log2 = $segCount > 1
            ? (int) floor(log($segCount, 2))
            : 0;
        $searchRange = (1 << $log2) * 2;
        $length = 14 + $segCount * 8; // no glyphIdArray (all range=0)

        $data = pack('n', 4) // format
              . pack('n', $length) // length
              . pack('n', 0) // language
              . pack('n', $segCountX2) // segCountX2
              . pack('n', $searchRange)
              . pack('n', $log2) // entrySelector
              . pack('n', $segCountX2 - $searchRange); // rangeShift

        $endCounts = $startCounts = $deltas = $ranges = '';

        foreach ($segments as $s) {
            $endCounts .= pack('n', $s['end']);
            $startCounts .= pack('n', $s['start']);
            $deltas .= pack('n', $s['delta']);
            $ranges .= pack('n', $s['range']);
        }

        return $data . $endCounts . pack('n', 0) . $startCounts . $deltas . $ranges;
    }

    /**
     * Builds a format-4 subtable that uses idRangeOffset != 0 (indirect
     * glyph-id lookup) to exercise that branch in parseCmapFormat4.
     *
     * One segment: start=0x41 ('A'), end=0x43 ('C'), range points into a
     * small glyphIdArray. GID 0 is placed for 'B' to exercise the `if ($gid !==0)` guard.
     */
    public static function buildFormat4SubtableWithRangeOffset(): string
    {
        // 2 segments: one "range" segment + sentinel
        $segCount = 2;
        $segCountX2 = 4;
        $length = 14 + $segCount * 8 + 3 * 2; // +3 glyphId entries

        $data = pack('n', 4)
              . pack('n', $length)
              . pack('n', 0) // language
              . pack('n', $segCountX2)
              . pack('n', 4) // searchRange
              . pack('n', 1) // entrySelector
              . pack('n', 0); // rangeShift

        // endCount
        $data .= pack('n', 0x43) // seg0 end = 'C'
               . pack('n', 0xFFFF); // seg1 end = sentinel

        $data .= pack('n', 0); // reservedPad

        // startCount
        $data .= pack('n', 0x41) // seg0 start = 'A'
               . pack('n', 0xFFFF); // sentinel

        // idDelta
        $data .= pack('n', 0) // seg0 delta = 0 (not used when range != 0)
               . pack('n', 1); // sentinel delta

        // idRangeOffset — the offset for seg0 points into glyphIdArray.
        // The TrueType spec says the offset is relative to the address of idRangeOffset[seg].
        //   address(idRangeOffset[0]) = rangeBase + 0
        //   address(glyphIdArray[0])  = rangeBase + 4   (2 segments × 2 bytes each)
        // So idRangeOffset[0] = 4.
        $data .= pack('n', 4) // seg0 rangeOffset: 4 bytes forward → glyphIdArray[0]
               . pack('n', 0); // sentinel rangeOffset = 0

        // glyphIdArray[0..2]: A=1, B=0 (will be excluded), C=3
        $data .= pack('n', 1) // A → gid 1
               . pack('n', 0) // B → gid 0 (excluded, exercises the gid===0 guard)
               . pack('n', 3); // C → gid 3

        return $data;
    }

    /**
     * Builds a composite glyph with one component referencing $componentGid.
     *
     * $flags controls the composite-record flags, allowing coverage of all
     * the skip-bytes branches in expandComposites:
     *   0x0001 = ARG_1_AND_2_ARE_WORDS → +4 bytes args
     *   0x0008 = WE_HAVE_A_SCALE → +2 bytes
     *   0x0040 = WE_HAVE_AN_X_AND_Y_SCALE → +4 bytes
     *   0x0080 = WE_HAVE_A_TWO_BY_TWO → +8 bytes
     *   0x0020 = MORE_COMPONENTS (not used here; always single-component)
     */
    public static function buildCompositeGlyph(int $componentGid, int $flags = 0x0000): string
    {
        $header = pack('n', 0xFFFF) // numberOfContours = -1 → composite
                . pack('n', 0) // xMin
                . pack('n', 0) // yMin
                . pack('n', 100) // xMax
                . pack('n', 100); // yMax

        // Component record: flags + glyphIndex + args + optional transform
        $record = pack('n', $flags) // component flags
                . pack('n', $componentGid); // referenced glyph

        // args: 4 bytes if ARG_1_AND_2_ARE_WORDS, else 2 bytes
        $record .= $flags & 0x0001
            ? pack('nn', 0, 0)
            : pack('n', 0);

        // optional transform fields
        if ($flags & 0x0008) {
            $record .= pack('n', 0); // scale (2 bytes)
        } elseif ($flags & 0x0040) {
            $record .= pack('nn', 0, 0); // x+y scale (4 bytes)
        } elseif ($flags & 0x0080) {
            $record .= pack('nnnn', 0, 0, 0, 0); // 2×2 matrix (8 bytes)
        }

        return $header . $record;
    }

    /**
     * Builds a composite glyph with two components, the first having the
     * MORE_COMPONENTS flag set so the do-while loop iterates twice.
     */
    public static function buildTwoComponentComposite(int $gid1, int $gid2): string
    {
        $header = pack('n', 0xFFFF) . pack('n', 0) . pack('n', 0) . pack('n', 100) . pack('n', 100);

        // First component: MORE_COMPONENTS flag set
        $rec1 = pack('n', 0x0020) // MORE_COMPONENTS
              . pack('n', $gid1)
              . pack('n', 0); // 2-byte args

        // Second component: no more
        $rec2 = pack('n', 0x0000)
              . pack('n', $gid2)
              . pack('n', 0);

        return $header . $rec1 . $rec2;
    }

    /** Composite glyph that is intentionally too short to contain a complete record. */
    public static function buildTruncatedComposite(): string
    {
        // Header (10 bytes) + start of composite flags but NO complete record
        return pack('n', 0xFFFF) // numberOfContours = -1 → composite
             . pack('n', 0) // xMin
             . pack('n', 0) // yMin
             . pack('n', 100) // xMax
             . pack('n', 100); // yMax (10 bytes only, pos+4 > glyfLen will break)
    }

    /**
     * Assembles a complete TrueType-style font binary from table map.
     *
     * @param array<string,string> $tables     tag → raw table data
     * @param string $sfVersion  4-byte sfVersion header
     * @param int $offsetBase added to all table offsets (for TTC sub-fonts)
     */
    public static function assembleFont(
        array $tables,
        string $sfVersion = "\x00\x01\x00\x00",
        int $offsetBase = 0,
    ): string {
        ksort($tables);
        $n = count($tables);

        if ($n === 0) {
            return '';
        }

        $log2 = (int) floor(log($n, 2));
        $searchRange = (1 << $log2) * 16;
        $entrySelector = $log2;
        $rangeShift = $n * 16 - $searchRange;
        $dataStart = $offsetBase + 12 + $n * 16;

        // Pad tables and compute absolute offsets
        $padded = [];
        $offsets = [];
        $cursor = $dataStart;

        foreach ($tables as $tag => $data) {
            $pad = (4 - (strlen($data) % 4)) % 4;
            $padded[$tag] = $data . str_repeat("\x00", $pad);
            $offsets[$tag] = $cursor;
            $cursor += strlen($padded[$tag]);
        }

        // Offset table (12 bytes)
        $result = $sfVersion
                . pack('n', $n)
                . pack('n', $searchRange)
                . pack('n', $entrySelector)
                . pack('n', $rangeShift);

        // Table directory
        foreach ($tables as $tag => $data) {
            $result .= pack('a4', $tag)
                     . pack('N', 0) // checksum (0 is fine for parsing tests)
                     . pack('N', $offsets[$tag])
                     . pack('N', strlen($data));
        }

        // Table data
        foreach (array_keys($tables) as $tag) {
            $result .= $padded[$tag];
        }

        return $result;
    }





    /**
     * @param array<string, mixed> $opts
     */
    private static function optInt(array $opts, string $key, int $default): int
    {
        $value = $opts[$key] ?? $default;

        return is_int($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function optFloat(array $opts, string $key, float $default): float
    {
        $value = $opts[$key] ?? $default;

        return is_float($value) || is_int($value) ? (float) $value : $default;
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function optBool(array $opts, string $key, bool $default): bool
    {
        $value = $opts[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function optString(array $opts, string $key, string $default): string
    {
        $value = $opts[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    // -------------------------------------------------------------------------
    // Public builders
    // -------------------------------------------------------------------------

    private static function buildCmapForFormat(int $format): string
    {
        if ($format === 4) {
            return self::buildCmapWithFormat4([0x41 => 1, 0x42 => 2]);
        }

        if ($format === 12) {
            return self::buildCmapWithFormat12([0x41 => 1, 0x42 => 2]);
        }

        return self::buildEmptyCmap();
    }

    private static function buildHead(int $unitsPerEm, int $ascent, int $locaFormat = 0): string
    {
        return pack('nn', 1, 0) // majorVersion, minorVersion
             . pack('N', 0) // fontRevision
             . pack('N', 0) // checkSumAdjustment
             . pack('N', 0x5F0F3CF5) // magicNumber
             . pack('n', 0x000B) // flags
             . pack('n', $unitsPerEm) // unitsPerEm (offset 18)
             . str_repeat("\x00", 16) // created + modified (16 bytes)
             . pack('n', 0) // xMin (offset 36)
             . pack('n', 0xFE00) // yMin (offset 38) ≈ −512
             . pack('n', $unitsPerEm) // xMax (offset 40)
             . pack('n', $ascent & 0xFFFF) // yMax (offset 42)
             . str_repeat("\x00", 6) // macStyle, lowestRecPPEM, fontDirectionHint
             . pack('n', $locaFormat) // indexToLocFormat (offset 50)
             . pack('n', 0); // glyphDataFormat
    }

    private static function buildMaxp(int $numGlyphs): string
    {
        return pack('N', 0x00005000) // version 0.5
             . pack('n', $numGlyphs); // numGlyphs (offset 4)
    }

    private static function buildHhea(int $ascent, int $descent, int $numberOfHMetrics): string
    {
        return pack('nn', 1, 0) // version 1.0
             . pack('n', $ascent & 0xFFFF) // ascender (offset 4)
             . pack('n', $descent & 0xFFFF) // descender (offset 6)
             . str_repeat("\x00", 24) // lineGap … reserved (12 fields × 2 bytes)
             . pack('n', 0) // metricDataFormat (offset 32)
             . pack('n', $numberOfHMetrics); // numberOfHMetrics (offset 34)
    }

    private static function buildHmtx(int $numGlyphs, int $advanceWidth = 500): string
    {
        $data = '';

        for ($i = 0; $i < $numGlyphs; $i++) {
            $data .= pack('n', $advanceWidth) // advanceWidth
                   . pack('n', 0); // lsb
        }

        return $data;
    }

    private static function buildOs2(int $version, int $weight, int $ascent, int $descent, int $capHeight,): string
    {
        $data = str_repeat("\x00", 96);
        $data = self::p16($data, 0, $version);
        $data = self::p16($data, 4, $weight);
        $data = self::p16($data, 68, $ascent & 0xFFFF);
        $data = self::p16($data, 70, $descent & 0xFFFF);
        $data = self::p16($data, 90, $capHeight & 0xFFFF);

        return $data;
    }

    private static function buildPost(float $italicAngle): string
    {
        $ia = (int) round($italicAngle * 65536.0);

        return pack('N', 0x00030000) // version 3.0
             . pack('N', $ia & 0xFFFFFFFF) // italicAngle as Fixed (offset 4)
             . str_repeat("\x00", 24); // rest
    }

    private static function buildNameTable(string $fontName): string
    {
        $utf16 = mb_convert_encoding($fontName, 'UTF-16BE', 'UTF-8');
        $len = strlen($utf16);
        // 1 record: platId=3, encId=1, langId=0, nameId=6, len, strOffset=0
        $stringsOffset = 6 + 1 * 12; // header(6) + 1 record(12) = 18

        return pack('n', 0) // format
             . pack('n', 1) // count = 1 record
             . pack('n', $stringsOffset) // stringOffset
             // Name record:
             . pack('n', 3) // platformId = 3
             . pack('n', 1) // encodingId = 1
             . pack('n', 0) // languageId = 0
             . pack('n', 6) // nameId = 6 (PostScript name)
             . pack('n', $len) // length
             . pack('n', 0) // offset within string area = 0
             // String area:
             . $utf16;
    }

    /**
     * Builds a format-12 subtable.
     *
     * @param array<int,int> $codeToGlyph
     */
    private static function buildFormat12Subtable(array $codeToGlyph): string
    {
        ksort($codeToGlyph);
        $groups = [];

        foreach ($codeToGlyph as $cp => $gid) {
            $groups[] = [$cp, $cp, $gid]; // [startCharCode, endCharCode, startGlyphID]
        }

        $nGroups = count($groups);
        $length = 16 + $nGroups * 12;

        $data = pack('n', 12) // format
              . pack('n', 0) // reserved
              . pack('N', $length)
              . pack('N', 0) // language
              . pack('N', $nGroups);

        foreach ($groups as [$start, $end, $gid]) {
            $data .= pack('NNN', $start, $end, $gid);
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // glyf + loca builders
    // -------------------------------------------------------------------------

    /** @return array{string, string} [glyfData, locaData] */
    private static function buildGlyfAndLoca(
        int $numGlyphs,
        bool $compositeGlyph,
        int $compositeFlags,
        int $locaFormat,
    ): array {
        // Glyph layout:
        //   GID 0 = .notdef (empty, 0 bytes in glyf)
        //   GID 1 = simple glyph (12 bytes)
        //   GID 2 = composite referencing GID 1 (if $compositeGlyph)
        //         = simple glyph (12 bytes) otherwise

        $glyph1 = self::buildSimpleGlyph();
        $glyph2 = $compositeGlyph
            ? self::buildCompositeGlyph(1, $compositeFlags)
            : self::buildSimpleGlyph();

        $glyfParts = ['', $glyph1]; // GID0 empty, GID1

        if ($numGlyphs > 2) {
            $glyfParts[] = $glyph2; // GID2
        }

        // Pad each glyph to 4-byte alignment
        $glyf = '';
        $locaOffsets = [0];

        foreach ($glyfParts as $g) {
            $pad = (4 - (strlen($g) % 4)) % 4;
            $glyf .= $g . str_repeat("\x00", $pad);
            $locaOffsets[] = strlen($glyf);
        }

        // Fill remaining glyph slots (if numGlyphs > count($glyfParts))
        for ($i = count($glyfParts); $i < $numGlyphs; $i++) {
            $locaOffsets[] = strlen($glyf);
        }

        $loca = self::encodeLoca($locaOffsets, $locaFormat);

        return [$glyf, $loca];
    }

    /** Builds a trivial simple glyph: 1 contour, 1 on-curve point at (0, 0). */
    private static function buildSimpleGlyph(): string
    {
        return pack('n', 1) // numberOfContours = 1
             . pack('n', 0) // xMin
             . pack('n', 0) // yMin
             . pack('n', 100) // xMax
             . pack('n', 100) // yMax
             . pack('n', 0) // endPtsOfContours[0] = 0
             . pack('n', 0) // instructionLength = 0
             . chr(0x07) // flags[0]: on-curve + x-short + y-short
             . chr(0) // xCoordinate: 0
             . chr(0); // yCoordinate: 0
    }

    /** @param array<int> $offsets */
    private static function encodeLoca(array $offsets, int $format): string
    {
        $data = '';
        $isShort = $format === 0;

        foreach ($offsets as $off) {
            $data .= $isShort
                ? pack('n', (int) ($off / 2))
                : pack('N', $off);
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns a standard set of tables using $opts.
     *
     * @param array<string, bool|int> $opts
     * @return array<string, string>
     */
    private static function getDefaultTables(array $opts): array
    {
        $unitsPerEm = self::optInt($opts, 'unitsPerEm', 1000);
        $numGlyphs = self::optInt($opts, 'numGlyphs', 3);
        $ascent = self::optInt($opts, 'ascent', 800);
        $descent = self::optInt($opts, 'descent', -200);
        $locaFormat = self::optInt($opts, 'locaFormat', 0);
        $includeGlyf = self::optBool($opts, 'includeGlyf', true);

        $tables = [
            'cmap' => self::buildCmapWithFormat4([0x41 => 1, 0x42 => 2]),
            'head' => self::buildHead($unitsPerEm, $ascent, $locaFormat),
            'hhea' => self::buildHhea($ascent, $descent, $numGlyphs),
            'hmtx' => self::buildHmtx($numGlyphs, $unitsPerEm),
            'maxp' => self::buildMaxp($numGlyphs),
            'name' => self::buildNameTable('TestFont'),
            'OS/2' => self::buildOs2(4, 400, $ascent, $descent, 0),
            'post' => self::buildPost(0.0),
        ];

        if ($includeGlyf) {
            [$glyf, $loca] = self::buildGlyfAndLoca($numGlyphs, false, 0, $locaFormat);
            $tables['glyf'] = $glyf;
            $tables['loca'] = $loca;
        }

        return $tables;
    }

    /** Patches a 16-bit big-endian value at $offset in $data. */
    private static function p16(string $data, int $offset, int $value): string
    {
        return substr($data, 0, $offset) . pack('n', $value) . substr($data, $offset + 2);
    }
}
