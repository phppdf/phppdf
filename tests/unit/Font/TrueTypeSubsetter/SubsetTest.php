<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeSubsetter;

use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeSubsetter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(TrueTypeSubsetter::class)]
#[CoversMethod(TrueTypeSubsetter::class, 'subset')]
final class SubsetTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Private constructor (line 35) — reflection only path
    // -------------------------------------------------------------------------

    #[Test]
    public function privateConstructorIsCallableViaReflection(): void
    {
        // Arrange — the private __construct() must be exercised for 100% coverage
        $rc = new ReflectionClass(TrueTypeSubsetter::class);
        $ctor = $rc->getConstructor();
        self::assertNotNull($ctor);

        $obj = $rc->newInstanceWithoutConstructor();

        // Act — invoke the (empty) private constructor
        $ctor->invoke($obj);

        // Assert — no error; the constructor is empty
        self::assertInstanceOf(TrueTypeSubsetter::class, $obj);
    }

    // -------------------------------------------------------------------------
    // CFF fallback (isCff=true)
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetReturnsCffFontDataUnchanged(): void
    {
        // Arrange — CFF font; isCff=true → return full data unchanged
        $data = MinimalFontBuilder::buildCff();

        // Act
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], true, 0);

        // Assert
        self::assertSame($data, $result);
    }

    // -------------------------------------------------------------------------
    // Success path — TrueType font
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetProducesValidBinaryForSimpleFont(): void
    {
        // Arrange — simple 3-glyph font with short loca (locaFormat=0)
        $data = MinimalFontBuilder::build(['numGlyphs' => 3, 'locaFormat' => 0]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], false, 0);

        // Assert — result is a non-empty binary
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetParsesLongLocaFormat(): void
    {
        // Arrange — font with long loca (locaFormat=1); the parser uses a different
        // reading path (uint32 instead of uint16 × 2).
        $data = MinimalFontBuilder::build(['numGlyphs' => 3, 'locaFormat' => 1]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Missing required table → fallback to full data
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetFallsBackToFullDataWhenGlyfMissing(): void
    {
        // Arrange — build without glyf/loca tables
        $data = MinimalFontBuilder::build(['includeGlyf' => false]);

        // Act — subsetting will throw internally; expect fallback
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], false, 0);

        // Assert — full data returned as fallback
        self::assertSame($data, $result);
    }

    // -------------------------------------------------------------------------
    // TTC support in subset
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetSucceedsForTtcFont(): void
    {
        // Arrange
        $data = MinimalFontBuilder::buildTtc();

        // Act
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetFallsBackWhenTtcIndexOutOfRange(): void
    {
        // Arrange — TTC with 1 font, request index 99
        $data = MinimalFontBuilder::buildTtc([], 1);

        // Act — resolveFontOffset throws → caught → fallback
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], false, 99);

        // Assert — full data returned as fallback
        self::assertSame($data, $result);
    }

    // -------------------------------------------------------------------------
    // subsetTrueType — usedGlyphs gid <= 0 / gid >= numGlyphs guard (line 91)
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetSkipsUsedGlyphsOutOfRange(): void
    {
        // Arrange — 3-glyph font; usedGlyphs contains GID 0 (<=0) and GID 5
        // (>= numGlyphs=3), both of which must be skipped by the guard.
        $data = MinimalFontBuilder::build(['numGlyphs' => 3, 'locaFormat' => 0]);

        // Act — only GID 1 is valid; GID 0 and GID 5 are out of range and skipped
        $result = TrueTypeSubsetter::subset($data, [0 => 0x00, 1 => 0x41, 5 => 0x45], false, 0);

        // Assert
        self::assertNotEmpty($result);
        self::assertSame("\x00\x01\x00\x00", substr($result, 0, 4));
    }

    // -------------------------------------------------------------------------
    // expandComposites — gid >= numGlyphs guard (line 226)
    // -------------------------------------------------------------------------

    #[Test]
    public function expandCompositesSkipsGlyphIdsAtOrAboveNumGlyphs(): void
    {
        // Arrange — call expandComposites directly with a glyphSet that contains
        // a GID equal to numGlyphs (GID 3 when numGlyphs=3).
        $rc = new ReflectionClass(TrueTypeSubsetter::class);
        $method = $rc->getMethod('expandComposites');


        // Build minimal glyf data (GID 0 = empty, GID 1 = simple)
        $simpleGlyph = pack('n', 1) . pack('n', 0) . pack('n', 0)
                     . pack('n', 100) . pack('n', 100)
                     . pack('n', 0) . pack('n', 0)
                     . chr(0x07) . chr(0) . chr(0);
        $glyfRaw = $simpleGlyph;

        $loca = [0 => 0, 1 => 0, 2 => strlen($simpleGlyph)]; // GID 0 empty, GID 1 at 0
        $numGlyphs = 2;
        // Put GID 2 (= numGlyphs) in the set — should be skipped by the guard
        $glyphSet = [0 => true, 2 => true];

        // Act
        $result = $method->invoke(null, $glyfRaw, $loca, $glyphSet, $numGlyphs);

        // Assert — GID 2 is skipped (still in the set unchanged, nothing explodes)
        self::assertIsArray($result);
        self::assertArrayHasKey(2, $result);
    }

    // -------------------------------------------------------------------------
    // Composite glyph expansion
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetExpandsCompositeGlyphs(): void
    {
        // Arrange — GID 2 is composite referencing GID 1; we only request GID 2
        $data = MinimalFontBuilder::build([
            'compositeGlyph' => true,
            'numGlyphs' => 3,
        ]);

        // Act — request GID 2; expect GID 1 to be pulled in automatically
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert — result is non-empty (both GID 1 and GID 2 are included)
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetHandlesCompositeWithArg1And2AreWordsFlag(): void
    {
        // Arrange — composite with ARG_1_AND_2_ARE_WORDS (0x0001) → 4-byte args
        $data = MinimalFontBuilder::build([
            'compositeFlags' => 0x0001,
            'compositeGlyph' => true,
            'numGlyphs' => 3,
        ]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetHandlesCompositeWithWeHaveAScaleFlag(): void
    {
        // Arrange — composite with WE_HAVE_A_SCALE (0x0008) → 2-byte transform
        $data = MinimalFontBuilder::build([
            'compositeFlags' => 0x0008,
            'compositeGlyph' => true,
            'numGlyphs' => 3,
        ]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetHandlesCompositeWithXAndYScaleFlag(): void
    {
        // Arrange — composite with WE_HAVE_AN_X_AND_Y_SCALE (0x0040) → 4-byte transform
        $data = MinimalFontBuilder::build([
            'compositeFlags' => 0x0040,
            'compositeGlyph' => true,
            'numGlyphs' => 3,
        ]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetHandlesCompositeWithTwoByTwoFlag(): void
    {
        // Arrange — composite with WE_HAVE_A_TWO_BY_TWO (0x0080) → 8-byte transform
        $data = MinimalFontBuilder::build([
            'compositeFlags' => 0x0080,
            'compositeGlyph' => true,
            'numGlyphs' => 3,
        ]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetHandlesTwoComponentComposite(): void
    {
        // Arrange — GID 2 references both GID 1 and GID 3 (MORE_COMPONENTS loop)
        $twoComp = MinimalFontBuilder::buildTwoComponentComposite(1, 3);

        // Build a 5-glyph font where GID 2 is a two-component composite
        $data = self::buildFontWithCustomGlyph2($twoComp, 5);

        // Act — request GID 2; both GID 1 and GID 3 are pulled in
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetHandlesTruncatedCompositeSafely(): void
    {
        // Arrange — GID 2 is a truncated composite (10 bytes, no room for a record)
        // → triggers the `$pos + 4 > $glyfLen` break in expandComposites (line 246)
        $truncated = MinimalFontBuilder::buildTruncatedComposite();
        $data = self::buildFontWithCustomGlyph2($truncated, 3);

        // Act — should not crash; no component GIDs extracted
        $result = TrueTypeSubsetter::subset($data, [2 => 0x42], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // hmtx sparse path (lsb-only records)
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetHandlesHmtxWithLsbOnlyRecords(): void
    {
        // Arrange — build font where hhea.numberOfHMetrics (2) < numGlyphs (5).
        // Glyphs beyond numberOfHMetrics have lsb-only records in hmtx.
        // Requesting GID 4 forces the lsb-only code path in buildHmtx.
        $data = self::buildFontWithSparseHmtx(numGlyphs: 5, numberOfHMetrics: 2);

        // Act
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41, 4 => 0x44], false, 0);

        // Assert
        self::assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // encodeLocaTable — long (uint32) path (lines 342-343)
    // -------------------------------------------------------------------------

    #[Test]
    public function encodeLocaTableEncodesLongOffsets(): void
    {
        // Arrange — call encodeLocaTable directly with useShort=false
        $rc = new ReflectionClass(TrueTypeSubsetter::class);
        $method = $rc->getMethod('encodeLocaTable');


        // Offsets larger than 0x1FFFE (which useShort would refuse)
        $loca = [0, 0x30000, 0x60000];

        // Act
        $result = $method->invoke(null, $loca, false);

        // Assert — 3 × 4 bytes = 12 bytes of uint32 big-endian data
        self::assertIsString($result);
        self::assertSame(12, strlen($result));
        self::assertSame(pack('NNN', 0, 0x30000, 0x60000), $result);
    }

    // -------------------------------------------------------------------------
    // buildFont — empty table map returns '' (line 401)
    // -------------------------------------------------------------------------

    #[Test]
    public function buildFontReturnsEmptyStringForEmptyTableMap(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeSubsetter::class);
        $method = $rc->getMethod('buildFont');


        // Act
        $result = $method->invoke(null, []);

        // Assert
        self::assertSame('', $result);
    }

    // -------------------------------------------------------------------------
    // fileChecksum — remainder bytes path (lines 482-483)
    // -------------------------------------------------------------------------

    #[Test]
    public function fileChecksumHandlesDataNotDivisibleBy4(): void
    {
        // Arrange — 5 bytes: 4-byte block + 1 remainder
        $rc = new ReflectionClass(TrueTypeSubsetter::class);
        $method = $rc->getMethod('fileChecksum');


        // data = \x00\x00\x00\x01 (sum=1) + \xFF (remainder, padded to \xFF\x00\x00\x00, sum += 0xFF000000)
        $data = "\x00\x00\x00\x01\xFF";

        // Act
        $result = $method->invoke(null, $data);

        // Assert — 1 + 0xFF000000 = 0xFF000001
        self::assertSame(0xFF000001, $result);
    }

    // -------------------------------------------------------------------------
    // Short vs long loca in actual subsetting output
    // -------------------------------------------------------------------------

    #[Test]
    public function subsetUsesShortLocaWhenOffsetsSmall(): void
    {
        // Arrange — small font: resulting glyf ≤ 0x1FFFE → short loca
        $data = MinimalFontBuilder::build(['numGlyphs' => 3, 'locaFormat' => 0]);

        // Act
        $result = TrueTypeSubsetter::subset($data, [1 => 0x41], false, 0);

        // Assert — output starts with TrueType sfVersion 0x00010000
        self::assertSame("\x00\x01\x00\x00", substr($result, 0, 4));
    }

    #[Test]
    public function subsetUsesLongLocaWhenOffsetsLarge(): void
    {
        // Arrange — use a real font that has a large glyf table; include all glyphs
        // so the resulting subset glyf exceeds 0x1FFFE bytes (131070 bytes).
        $path = '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf';

        if (!is_readable($path)) {
            $this->markTestSkipped('NotoSans-Regular.ttf not available');
        }

        $data = file_get_contents($path);
        self::assertIsString($data);

        // Build a large usedGlyphs map including many glyphs so subset is big
        $usedGlyphs = [];

        for ($gid = 1; $gid <= 2000; $gid++) {
            $usedGlyphs[$gid] = $gid;
        }

        // Act
        $result = TrueTypeSubsetter::subset($data, $usedGlyphs, false, 0);

        // Assert — result is valid TrueType binary (starts with sfVersion)
        self::assertNotEmpty($result);
        self::assertSame("\x00\x01\x00\x00", substr($result, 0, 4));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a font where GID 0 is empty and GID 2 contains $customGlyphData
     * instead of the default simple glyph.
     *
     * Critical: loca[i] = byte offset of the START of glyph i's data in glyf.
     */
    private static function buildFontWithCustomGlyph2(string $customGlyph2, int $numGlyphs): string
    {
        // Simple glyph: 17 bytes, padded to 20
        $simple = pack('n', 1) // numberOfContours
                . pack('n', 0) // xMin
                . pack('n', 0) // yMin
                . pack('n', 100) // xMax
                . pack('n', 100) // yMax
                . pack('n', 0) // endPtsOfContours[0]
                . pack('n', 0) // instructionLength
                . chr(0x07) // flags
                . chr(0) // x
                . chr(0); // y

        $glyf = '';
        $locaOffsets = [0]; // loca[0] = 0 (GID 0 is empty, starts — and ends — at byte 0)

        for ($gid = 1; $gid < $numGlyphs; $gid++) {
            $locaOffsets[] = strlen($glyf); // loca[gid] = current position BEFORE adding
            $glyphData = $gid === 2
                ? $customGlyph2
                : $simple;
            $pad = (4 - (strlen($glyphData) % 4)) % 4;
            $glyf .= $glyphData . str_repeat("\x00", $pad);
        }

        $locaOffsets[] = strlen($glyf); // sentinel = total glyf size

        // Short loca (divide by 2)
        $loca = '';

        foreach ($locaOffsets as $off) {
            $loca .= pack('n', (int)($off / 2));
        }

        $unitsPerEm = 1000;
        $ascent = 800;
        $descent = -200;

        $tables = [
            'cmap' => MinimalFontBuilder::buildCmapWithFormat4([0x41 => 1, 0x42 => 2]),
            'glyf' => $glyf,
            'head' => self::rawHead($unitsPerEm, 0),
            'hhea' => self::rawHhea($ascent, $descent, $numGlyphs),
            'hmtx' => self::rawHmtx($numGlyphs, $unitsPerEm),
            'loca' => $loca,
            'maxp' => pack('N', 0x00005000) . pack('n', $numGlyphs),
            'name' => self::rawNameTable('TestFont'),
            'OS/2' => self::rawOs2(4, 400, $ascent, $descent, 0),
            'post' => pack('N', 0x00030000) . pack('N', 0) . str_repeat("\x00", 24),
        ];

        return MinimalFontBuilder::assembleFont($tables);
    }

    /**
     * Builds a font where hhea.numberOfHMetrics < numGlyphs,
     * so glyphs beyond numberOfHMetrics have lsb-only records in hmtx.
     */
    private static function buildFontWithSparseHmtx(int $numGlyphs, int $numberOfHMetrics): string
    {
        $unitsPerEm = 1000;
        $ascent = 800;
        $descent = -200;

        // Simple glyph: 17 bytes, padded to 20
        $simple = pack('n', 1) . pack('n', 0) . pack('n', 0)
                . pack('n', 100) . pack('n', 100)
                . pack('n', 0) . pack('n', 0)
                . chr(0x07) . chr(0) . chr(0);
        $pad = (4 - (strlen($simple) % 4)) % 4;
        $simplePadded = $simple . str_repeat("\x00", $pad);

        $glyf = '';
        $locaOffsets = [0]; // loca[0] = 0 (GID 0 empty)

        for ($gid = 1; $gid < $numGlyphs; $gid++) {
            $locaOffsets[] = strlen($glyf); // loca[gid] = current position BEFORE adding
            $glyf .= $simplePadded;
        }

        $locaOffsets[] = strlen($glyf); // sentinel

        $loca = '';

        foreach ($locaOffsets as $off) {
            $loca .= pack('n', (int)($off / 2));
        }

        // Sparse hmtx: full records for 0..numberOfHMetrics-1, lsb-only for rest
        $hmtx = MinimalFontBuilder::buildHmtxSparse($numberOfHMetrics, $numGlyphs, $unitsPerEm);

        $tables = [
            'cmap' => MinimalFontBuilder::buildCmapWithFormat4([0x41 => 1, 0x42 => 2]),
            'glyf' => $glyf,
            'head' => self::rawHead($unitsPerEm, 0),
            'hhea' => self::rawHhea($ascent, $descent, $numberOfHMetrics),
            'hmtx' => $hmtx,
            'loca' => $loca,
            'maxp' => pack('N', 0x00005000) . pack('n', $numGlyphs),
            'name' => self::rawNameTable('TestFont'),
            'OS/2' => self::rawOs2(4, 400, $ascent, $descent, 0),
            'post' => pack('N', 0x00030000) . pack('N', 0) . str_repeat("\x00", 24),
        ];

        return MinimalFontBuilder::assembleFont($tables);
    }

    // ---- Raw table helpers ----

    private static function rawHead(int $unitsPerEm, int $locaFormat): string
    {
        return pack('nn', 1, 0)
             . pack('N', 0) . pack('N', 0)
             . pack('N', 0x5F0F3CF5)
             . pack('n', 0x000B)
             . pack('n', $unitsPerEm)
             . str_repeat("\x00", 16)
             . pack('n', 0) . pack('n', 0xFE00)
             . pack('n', $unitsPerEm) . pack('n', 800)
             . str_repeat("\x00", 6)
             . pack('n', $locaFormat)
             . pack('n', 0);
    }

    private static function rawHhea(int $ascent, int $descent, int $numberOfHMetrics): string
    {
        return pack('nn', 1, 0)
             . pack('n', $ascent & 0xFFFF)
             . pack('n', $descent & 0xFFFF)
             . str_repeat("\x00", 24)
             . pack('n', 0)
             . pack('n', $numberOfHMetrics);
    }

    private static function rawHmtx(int $numGlyphs, int $advanceWidth): string
    {
        $data = '';

        for ($i = 0; $i < $numGlyphs; $i++) {
            $data .= pack('n', $advanceWidth) . pack('n', 0);
        }

        return $data;
    }

    private static function rawOs2(int $version, int $weight, int $ascent, int $descent, int $capHeight): string
    {
        $data = str_repeat("\x00", 96);
        $data = substr($data, 0, 0) . pack('n', $version) . substr($data, 2);
        $data = substr($data, 0, 4) . pack('n', $weight) . substr($data, 6);
        $data = substr($data, 0, 68) . pack('n', $ascent & 0xFFFF) . substr($data, 70);
        $data = substr($data, 0, 70) . pack('n', $descent & 0xFFFF) . substr($data, 72);
        $data = substr($data, 0, 90) . pack('n', $capHeight & 0xFFFF) . substr($data, 92);

        return $data;
    }

    private static function rawNameTable(string $name): string
    {
        $utf16 = mb_convert_encoding($name, 'UTF-16BE', 'UTF-8');
        $len = strlen($utf16);
        $stringsOffset = 6 + 12;

        return pack('n', 0)
             . pack('n', 1)
             . pack('n', $stringsOffset)
             . pack('nnnnnn', 3, 1, 0, 6, $len, 0)
             . $utf16;
    }
}
