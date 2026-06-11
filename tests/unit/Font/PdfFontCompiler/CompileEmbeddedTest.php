<?php

declare(strict_types=1);

namespace PhpPdf\Font\PdfFontCompiler;

use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeSubsetter;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfToUnicodeCMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function assert;

#[CoversClass(PdfFontCompiler::class)]
#[CoversMethod(PdfFontCompiler::class, 'compileEmbedded')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfToUnicodeCMap::class)]
#[UsesClass(TrueTypeFont::class)]
#[UsesClass(TrueTypeSubsetter::class)]
final class CompileEmbeddedTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    #[Test]
    public function compileEmbeddedReturnsPdfIndirectReference(): void
    {
        // Arrange — CFF font so subset() returns rawData immediately
        $registry = new PdfObjectRegistry();
        $font = self::makeFont(isCff: true);

        // Act
        $ref = PdfFontCompiler::compileEmbedded($registry, $font, [1 => 65]);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileEmbeddedWithCffFontUsesFontFile3AndCIDFontType0(): void
    {
        // Arrange — isCff=true: FontFile3 key, CIDFontType0C subtype, no CIDToGIDMap
        $registry = new PdfObjectRegistry();
        $font = self::makeFont(isCff: true);

        // Act
        PdfFontCompiler::compileEmbedded($registry, $font, [1 => 65]);

        // Assert — multiple objects registered (Type0, CIDFont, FontDescriptor, FontFile, ToUnicode)
        self::assertGreaterThan(4, count($registry->all()));
    }

    #[Test]
    public function compileEmbeddedWithTrueTypeFontUsesFontFile2AndCIDFontType2(): void
    {
        // Arrange — isCff=false: FontFile2 key, CIDFontType2 subtype, CIDToGIDMap=Identity
        // TrueTypeSubsetter falls back to returning rawData when given garbage binary.
        $registry = new PdfObjectRegistry();
        $font = self::makeFont(isCff: false);

        // Act
        PdfFontCompiler::compileEmbedded($registry, $font, [1 => 65]);

        // Assert
        self::assertInstanceOf(
            PdfIndirectReference::class,
            PdfFontCompiler::compileEmbedded(new PdfObjectRegistry(), $font, [1 => 65]),
        );
    }

    #[Test]
    public function compileEmbeddedWithEmptyGlyphsReturnsEmptyWidthsArray(): void
    {
        // Arrange — empty usedGlyphs covers the early-return path in buildWidthsArray
        // and the empty-glyphs path (no bfchar blocks) in PdfToUnicodeCMap
        $registry = new PdfObjectRegistry();
        $font = self::makeFont(isCff: true);

        // Act
        $ref = PdfFontCompiler::compileEmbedded($registry, $font, []);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileEmbeddedWithConsecutiveGlyphsBuildsCompactWidthRun(): void
    {
        // Arrange — glyphs 1 and 2 are consecutive (gid === prev + 1 path in buildWidthsArray)
        // so they form a single run without flushing an intermediate group.
        $registry = new PdfObjectRegistry();
        $font = self::makeFont(isCff: true, advanceWidths: [0 => 500, 1 => 600, 2 => 650]);

        // Act
        $ref = PdfFontCompiler::compileEmbedded($registry, $font, [1 => 65, 2 => 66]);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileEmbeddedWithNonConsecutiveGlyphsFlushesIntermediateGroups(): void
    {
        // Arrange — glyphs 1 and 5 are non-consecutive, forcing a group flush
        // (the inner `if ($groupStart !== null)` branch in buildWidthsArray)
        $registry = new PdfObjectRegistry();
        $font = self::makeFont(isCff: true, advanceWidths: [0 => 500, 1 => 600, 5 => 700]);

        // Act
        $ref = PdfFontCompiler::compileEmbedded($registry, $font, [1 => 65, 5 => 69]);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    /**
     * Creates a TrueTypeFont with properties set via reflection, bypassing the
     * private constructor.
     *
     * For CFF fonts (isCff=true) TrueTypeSubsetter::subset() returns rawData
     * unchanged (CFF subsetting not implemented), so any byte string is safe.
     *
     * For TrueType fonts (isCff=false) we set rawData to a 6-byte stub:
     *   bytes 0-3 sfVersion = 0x00010000 (valid TrueType, not OTTO/ttcf)
     *   bytes 4-5 numTables = 0
     * parseTables() returns an empty array, subsetTrueType() then throws
     * RuntimeException ("Required table 'glyf' not found"), which is caught by
     * TrueTypeSubsetter::subset()'s catch(\Throwable) and returns rawData — no
     * PHP warnings, no invalid unpack calls.
     *
     * @param array<int,int> $advanceWidths [glyphId => fontUnits]
     */
    private static function makeFont(bool $isCff, array $advanceWidths = [0 => 500, 1 => 600]): TrueTypeFont
    {
        $refClass = new ReflectionClass(TrueTypeFont::class);
        $font = $refClass->newInstanceWithoutConstructor();
        assert($font instanceof TrueTypeFont);

        $set = static function (string $prop, mixed $value) use ($refClass, $font): void {
            $refClass->getProperty($prop)->setValue($font, $value);
        };

        // CFF  → any bytes are fine (returned as-is by TrueTypeSubsetter)
        // TTF  → sfVersion(4) + numTables=0(2): parseTables returns [], then
        //         subsetTrueType throws RuntimeException (caught, fallback used)
        $rawData = $isCff
            ? 'fake_cff_binary'
            : "\x00\x01\x00\x00\x00\x00"; // TrueType header, 0 tables
        $set('rawData', $rawData);
        $set('fontName', 'TestFont-Regular');
        $set('unitsPerEm', 1000);
        $set('numGlyphs', 2);
        $set('fontIndex', 0);
        $set('cmap', [65 => 1, 66 => 2]); // 'A' → glyph 1, 'B' → glyph 2
        $set('advanceWidths', $advanceWidths);
        $set('ascent', 800);
        $set('descent', -200);
        $set('capHeight', 700);
        $set('italicAngle', 0.0);
        $set('xMin', -10);
        $set('yMin', -200);
        $set('xMax', 1000);
        $set('yMax', 800);
        $set('flags', 32);
        $set('stemV', 80);
        $set('isCff', $isCff);

        return $font;
    }
}
