<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeFont;

use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TrueTypeFont::class)]
#[CoversMethod(TrueTypeFont::class, 'fromData')]
final class FromDataTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Basic construction
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataReturnsFontInstance(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build();

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertInstanceOf(TrueTypeFont::class, $font);
    }

    #[Test]
    public function fromDataStoresRawData(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build();

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame($data, $font->getRawData());
    }

    // -------------------------------------------------------------------------
    // isCff detection
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataDetectsTrueTypeFont(): void
    {
        // Arrange — default build produces sfVersion=0x00010000 (TrueType)
        $data = MinimalFontBuilder::build();

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertFalse($font->isCff());
    }

    #[Test]
    public function fromDataDetectsCffFont(): void
    {
        // Arrange — 'OTTO' sfVersion → CFF
        $data = MinimalFontBuilder::buildCff();

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertTrue($font->isCff());
    }

    // -------------------------------------------------------------------------
    // head table parsing
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesUnitsPerEm(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['unitsPerEm' => 2048]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame(2048, $font->getUnitsPerEm());
    }

    // -------------------------------------------------------------------------
    // OS/2 table parsing
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesAscentAndDescent(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['ascent' => 900, 'descent' => -300]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame(900, $font->getAscent());
        self::assertSame(-300, $font->getDescent());
    }

    #[Test]
    public function fromDataUsesCapHeightFromOs2WhenVersionAtLeast2AndPositive(): void
    {
        // Arrange — OS/2 version=4, capHeight=700 (positive)
        $data = MinimalFontBuilder::build([
            'ascent' => 800,
            'capHeight' => 700,
            'os2Version' => 4,
        ]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame(700, $font->getCapHeight());
    }

    #[Test]
    public function fromDataFallsBackToAscentMultiplierWhenCapHeightZero(): void
    {
        // Arrange — OS/2 version=4, capHeight=0 → fallback to (int)(ascent * 0.7)
        $data = MinimalFontBuilder::build([
            'ascent' => 800,
            'capHeight' => 0,
            'os2Version' => 4,
        ]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — (int)(800 * 0.7) = 560
        self::assertSame(560, $font->getCapHeight());
    }

    #[Test]
    public function fromDataFallsBackToAscentMultiplierWhenOs2VersionLessThan2(): void
    {
        // Arrange — OS/2 version=0 → capHeight always falls back
        $data = MinimalFontBuilder::build([
            'ascent' => 800,
            'capHeight' => 700, // would be used only for version >= 2
            'os2Version' => 0,
        ]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — (int)(800 * 0.7) = 560
        self::assertSame(560, $font->getCapHeight());
    }

    #[Test]
    public function fromDataSetsStemV120ForBoldWeight(): void
    {
        // Arrange — usWeightClass >= 700 → stemV = 120
        $data = MinimalFontBuilder::build(['weight' => 700]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame(120, $font->getStemV());
    }

    #[Test]
    public function fromDataSetsStemV80ForRegularWeight(): void
    {
        // Arrange — usWeightClass < 700 → stemV = 80
        $data = MinimalFontBuilder::build(['weight' => 400]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame(80, $font->getStemV());
    }

    // -------------------------------------------------------------------------
    // post table — italicAngle
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesItalicAngleFromPostTable(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['italicAngle' => -12.0]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertEqualsWithDelta(-12.0, $font->getItalicAngle(), 0.01);
    }

    #[Test]
    public function fromDataDefaultsItalicAngleToZeroWhenNoPostTable(): void
    {
        // Arrange — no post table
        $data = MinimalFontBuilder::build(['includePost' => false]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame(0.0, $font->getItalicAngle());
    }

    #[Test]
    public function fromDataSetsItalicFlagWhenAngleNonZero(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['italicAngle' => -12.0]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — flags should have bit 0x40 (italic) set in addition to baseline 32
        self::assertSame(32 | 0x40, $font->getFlags());
    }

    #[Test]
    public function fromDataDoesNotSetItalicFlagWhenAngleZero(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['italicAngle' => 0.0]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — only the baseline 32 (Nonsymbolic) flag
        self::assertSame(32, $font->getFlags());
    }

    // -------------------------------------------------------------------------
    // name table
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesFontNameFromNameTable(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['fontName' => 'MyTestFont']);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame('MyTestFont', $font->getFontName());
    }

    #[Test]
    public function fromDataDefaultsToUnknownFontWhenNoNameTable(): void
    {
        // Arrange — no name table
        $data = MinimalFontBuilder::build(['includeName' => false]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert
        self::assertSame('UnknownFont', $font->getFontName());
    }

    #[Test]
    public function fromDataParsesNameFromPlatform1Entry(): void
    {
        // Arrange — font with only platId=1 name records (no platId=3)
        $nameTable = MinimalFontBuilder::buildPlatform1NameTable('Platform1Font', '');
        $customData = MinimalFontBuilder::buildWithCustomNameTable($nameTable);

        // Act
        $font = TrueTypeFont::fromData($customData);

        // Assert
        self::assertSame('Platform1Font', $font->getFontName());
    }

    #[Test]
    public function fromDataUsesPlatform1FullNameWhenNoPsName(): void
    {
        // Arrange — platform-1 with only fullName (nameId=4), no psName
        $nameTable = MinimalFontBuilder::buildPlatform1NameTable('', 'FullNameOnly');
        $customData = MinimalFontBuilder::buildWithCustomNameTable($nameTable);

        // Act
        $font = TrueTypeFont::fromData($customData);

        // Assert
        self::assertSame('FullNameOnly', $font->getFontName());
    }

    #[Test]
    public function fromDataFallsBackToFontLiteralWhenNoUsableNameIds(): void
    {
        // Arrange — name table with no nameId=4 or nameId=6 entries
        $nameTable = MinimalFontBuilder::buildNameTableWithOnlyOtherIds();
        $customData = MinimalFontBuilder::buildWithCustomNameTable($nameTable);

        // Act
        $font = TrueTypeFont::fromData($customData);

        // Assert — parseName falls back to 'Font'
        self::assertSame('Font', $font->getFontName());
    }

    // -------------------------------------------------------------------------
    // cmap parsing
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesCmapFormat4(): void
    {
        // Arrange — default build uses format 4
        $data = MinimalFontBuilder::build(['cmapFormat' => 4]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — 'A' (0x41) maps to GID 1
        self::assertSame(1, $font->getGlyphId(0x41));
    }

    #[Test]
    public function fromDataParsesCmapFormat12(): void
    {
        // Arrange — build with format-12 cmap (full Unicode)
        $data = MinimalFontBuilder::build(['cmapFormat' => 12]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — 'A' (0x41) maps to GID 1
        self::assertSame(1, $font->getGlyphId(0x41));
    }

    #[Test]
    public function fromDataReturnsEmptyCmapWhenNoMatchingSubtable(): void
    {
        // Arrange — cmap with only platId=1 (Mac), not matched by parseCmap
        $data = MinimalFontBuilder::build(['cmapFormat' => 0]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — no code point is mapped → GID 0
        self::assertSame(0, $font->getGlyphId(0x41));
    }

    #[Test]
    public function fromDataParsesCmapFormat4WithNonZeroRangeOffset(): void
    {
        // Arrange — subtable uses idRangeOffset != 0 (indirect glyph-id lookup)
        $cmapTable = pack('n', 0) // version
                    . pack('n', 1) // numSubtables
                    . pack('n', 3) // platformId = 3
                    . pack('n', 1) // encodingId = 1
                    . pack('N', 12) // offset from start of cmap = 12
                    . MinimalFontBuilder::buildFormat4SubtableWithRangeOffset();

        $customData = MinimalFontBuilder::buildWithCustomCmap($cmapTable);

        // Act
        $font = TrueTypeFont::fromData($customData);

        // Assert — A=1, B=0 (excluded), C=3
        self::assertSame(1, $font->getGlyphId(0x41));
        self::assertSame(0, $font->getGlyphId(0x42)); // GID 0 guard excludes 'B'
        self::assertSame(3, $font->getGlyphId(0x43));
    }

    // -------------------------------------------------------------------------
    // TTC support
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesValidTtcAtIndex0(): void
    {
        // Arrange
        $data = MinimalFontBuilder::buildTtc();

        // Act
        $font = TrueTypeFont::fromData($data, 0);

        // Assert
        self::assertInstanceOf(TrueTypeFont::class, $font);
        self::assertSame('TestFont', $font->getFontName());
    }

    #[Test]
    public function fromDataThrowsWhenTtcFontIndexOutOfRange(): void
    {
        // Arrange — TTC header says numFonts=1, but we request index 5
        $data = MinimalFontBuilder::buildTtc([], 1);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Font index 5 out of range');

        // Act
        TrueTypeFont::fromData($data, 5);
    }

    // -------------------------------------------------------------------------
    // hmtx / advance widths
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesAdvanceWidths(): void
    {
        // Arrange — 3 glyphs, each with advance width = unitsPerEm (1000)
        $data = MinimalFontBuilder::build(['unitsPerEm' => 1000, 'numGlyphs' => 3]);

        // Act
        $font = TrueTypeFont::fromData($data);

        // Assert — GID 1 has advance width 1000
        self::assertSame(1000, $font->getAdvanceWidth(1));
    }
}
